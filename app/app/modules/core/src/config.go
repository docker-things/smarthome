package main

import (
  "encoding/json"
  "fmt"
  "strings"

  config "./helpers/config"
  mqtt "./helpers/mqtt"
)

const serviceName = "core/config"
const broker = "tcp://mqtt:1883"
const configPath = "/app/data/config"

// IN
var topicRequest = strings.Join([]string{serviceName, "request"}, "/")

// OUT
var topicAnnounce = strings.Join([]string{serviceName, "announce"}, "/")

// var publishTopic string

func main() {

  // Compile all regular expressions that will be used
  config.CompileRegexp()

  // Set config path
  config.SetPath(configPath)

  // Set publish method
  config.SetOnChangeCallback(func(configJson string) {
    fmt.Println("Sending config")
    mqtt.PublishOn(topicAnnounce, configJson)
  })

  // Connect to MQTT
  mqtt.Connect(serviceName, broker)

  // Get config
  config.Load()

  // Listen for incoming MQTT requests
  listenForIncomingRequests()

  // Check for config changes every 5 seconds
  config.LoopReloadOnChange(5)
}

func listenForIncomingRequests() {
  mqtt.Subscribe(topicRequest, func(msg string) {
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
