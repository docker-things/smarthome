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

func LoopReloadOnChange(interval int) {
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

  // Get current files with their modified time
  newConfigFiles := getConfigFilesIn(configPath)

  if thereAreChanges(newConfigFiles) {

    setNewConfigFiles(newConfigFiles)

    newConfig := make(map[string]interface{})
    for path, _ := range configFiles.value {
      loadFileIntoConfig(path, newConfig)
    }

    processConfig(newConfig)
    dropBaseModules(newConfig)
    setNewConfig(newConfig)
  }
}

func dropBaseModules(newConfig map[string]interface{}) {
  if _, ok := newConfig["Module"]; ok {
    delete(newConfig, "Module")
  }
}

func setNewConfig(newConfig map[string]interface{}) {
  config.mutex.Lock()
  config.value = newConfig
  configJsonString := json.Encode(config.value)
  config.mutex.Unlock()
  onChangeCallback(configJsonString)
}
