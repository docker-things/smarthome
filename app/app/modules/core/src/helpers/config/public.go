package config

import (
  "fmt"
  "time"

  json "../json"
)

func CompileRegexp() {
  preProcessRegexp()
}

func SetPath(path string) {
  configPath = path
}

func SetOnChangeCallback(callback onChangeCallbackType) {
  onChangeCallback = callback
}

func ReloadOnChange(interval int) {
  for {
    time.Sleep(time.Duration(interval) * time.Second)
    Load()
  }
}

func GetJSON() string {
  config.mutex.Lock()
  configJsonString := json.Encode(config.value)
  config.mutex.Unlock()
  return configJsonString
}

func Load() {
  fmt.Println("loadConfig()")

  // Get current files with modified time
  newConfigFiles := getConfigFilesIn(configPath)

  // If there are changes
  if thereAreChanges(newConfigFiles) {

    // Set new files map
    setNewConfigFiles(newConfigFiles)

    // Initialize new config
    newConfig := make(map[string]interface{})

    // Load each file
    for path, _ := range configFiles.value {
      loadFileIntoConfig(path, newConfig)
    }

    // Process config
    processConfig(newConfig)

    // Set new config
    setNewConfig(newConfig)
  }
}

func setNewConfig(newConfig map[string]interface{}) {
  config.mutex.Lock()
  config.value = newConfig
  configJsonString := json.Encode(config.value)
  config.mutex.Unlock()
  onChangeCallback(configJsonString)
}
