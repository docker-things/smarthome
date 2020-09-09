package main

import (
  "fmt"
  "strings"

  config "./helpers/config"
  mqtt "./helpers/mqtt"
)

const serviceName = "core/config"
const broker = "tcp://localhost:1883"
const configPath = "/app/data/config"

// IN
var topicRequest = strings.Join([]string{serviceName, "request"}, "/")

// OUT
var topicPublish = strings.Join([]string{serviceName, "get"}, "/")

// var publishTopic string

func main() {

  // Compile all regular expressions that will be used
  config.CompileRegexp()

  // Set config path
  config.SetPath(configPath)

  // Set publish method
  config.SetOnChangeCallback(func(configJson string) {
    fmt.Println("Sending config")
    mqtt.PublishOn(topicPublish, configJson)
  })

  // Connect to MQTT
  mqtt.Connect(serviceName, broker)

  // Get config
  config.Load()

  // Listen to incoming MQTT requests
  mqtt.Subscribe(topicRequest, func(msg string) {
    fmt.Println("RECEIVED: " + msg)
    // TODO: Send config per service channel with custom restricted format
    // NEVER expose the actual config format in order to be able to later change it
    //
    // configJson := config.GetJSON()
    // fmt.Println("Sending config per service in custom restricted format")
    // mqtt.PublishOn(topicPublish, configJson)
  })

  // Check for config changes every 5 seconds
  config.ReloadOnChange(5)
}
