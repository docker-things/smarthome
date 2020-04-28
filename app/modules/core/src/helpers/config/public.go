package config

import (
  "encoding/json"
  "fmt"
  "log"
  "time"
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

func Load() {
  fmt.Printf("\nloadConfig()\n")

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

func configToJsonString(config map[string]interface{}) string {
  configJson, err := json.Marshal(config)
  if err != nil {
    log.Fatal("ERROR: setNewConfig(): Cannot encode to JSON ", err)
  }
  return string(configJson)
}

func setNewConfig(newConfig map[string]interface{}) {
  config.mutex.Lock()
  config.value = newConfig
  configJsonString := configToJsonString(config.value)
  config.mutex.Unlock()

  onChangeCallback(configJsonString)
}
