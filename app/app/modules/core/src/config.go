package main

import (
  "encoding/json"
  "fmt"

  config "./config"
  mqtt "./helpers/mqtt"
)

func main() {

  // Compile all regular expressions that will be used
  config.CompileRegexp()

  // Set config path
  config.SetPath(config.ConfigPath)

  // Set publish method
  config.SetOnChangeJsonCallback(func(configJson string) {
    fmt.Println("Announcing config")
    mqtt.PublishOn(config.TopicAnnounce, configJson)
  })

  // Connect to MQTT
  mqtt.Connect(config.ServiceName, config.MqttBroker)

  // Get config
  config.Load()

  // Listen for incoming MQTT requests
  listenForIncomingRequests()

  // Check for config changes every 5 seconds
  config.LoopReloadOnChange(5)
}

func listenForIncomingRequests() {
  mqtt.Subscribe(config.TopicRequest, func(msg string) {
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
      configJson = config.GetJSON()
    } else {
      fmt.Println("WARN: Deep path not implemented!")
      return
    }

    fmt.Println("Sending config to " + request["responseTopic"])
    mqtt.PublishOn(request["responseTopic"], configJson)
  })
}
