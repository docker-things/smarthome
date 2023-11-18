package config

import (
  "fmt"
  "os"
  "time"

  json "app/helpers/json"
  mqtt "app/helpers/mqtt"
)

func StartService(monolith bool) {

  // Compile all regular expressions that will be used
  compileRegexp()

  // Set config path
  setPath(ConfigPath)

  // Set publish method
  setOnChangeJsonCallback(func(configJson string) {
    fmt.Println("Announcing config")
    mqtt.PublishOn(TopicAnnounce, configJson)
  })

  // Connect to MQTT
  mqtt.Connect(ServiceName, "tcp://"+os.Getenv("MQTT_HOST_CORE"))

  // Get config
  load()

  // Listen for incoming MQTT requests
  listenForIncomingRequests()

  // Check for config changes every 5 seconds
  loopReloadOnChange(5)
}

func listenForIncomingRequests() {
  mqtt.Subscribe(TopicRequest, func(msg string) {
    fmt.Println("REQUEST: " + msg)

    var request map[string]string
    err := json.Unmarshal([]byte(msg), &request)
    if err != nil {
      panic(err.Error())
    }

    if _, ok := request["path"]; !ok {
      fmt.Println("WARN: Request contains no \"path\"!")
      return
    }

    if _, ok := request["responseTopic"]; !ok {
      fmt.Println("WARN: Request contains no \"responseTopic\"!")
      return
    }

    var configJson string

    if request["path"] == "" {
      configJson = getJSON()
    } else {
      fmt.Println("WARN: Deep path not implemented!")
      return
    }

    fmt.Println("Sending config to " + request["responseTopic"])
    mqtt.PublishOn(request["responseTopic"], configJson)
  })
}

func compileRegexp() {
  preProcessRegexp()
}

func setPath(path string) {
  configPath = path
}

func SetOnChangeCallback(callback onChangeCallbackType) {
  onChangeCallback = callback
}

func setOnChangeJsonCallback(callback onChangeJsonCallbackType) {
  onChangeJsonCallback = callback
}

func loopReloadOnChange(interval int) {
  for {
    time.Sleep(time.Duration(interval) * time.Second)
    load()
  }
}

func getJSON() string {
  config.mutex.Lock()
  defer config.mutex.Unlock()
  return config.json
}

func load() {
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
  config.json = json.Encode(config.value)
  config.mutex.Unlock()
  if onChangeCallback != nil {
    onChangeCallback(newConfig)
  }
  if onChangeJsonCallback != nil {
    onChangeJsonCallback(config.json)
  }
}
