package main

import (
  "fmt"
  "strings"

  config "./helpers/config"
  mqtt "./helpers/mqtt"
)

const serviceName = "core/config"

const configPath = "/app/data/config"

// var publishTopic string

func main() {

  // Compile all regular expressions that will be used
  config.CompileRegexp()

  // Set config path
  config.SetPath(configPath)

  // Set publish method
  config.SetOnChangeCallback(func(configJson string) {
    mqtt.Publish(configJson)
  })

  // Connect to MQTT
  mqtt.Connect(serviceName)

  // Set publish topic
  // publishTopic = strings.Join([]string{serviceName, "read"}, "/")

  // Get config
  config.Load()

  // Listen to incoming MQTT requests
  mqtt.Subscribe(strings.Join([]string{serviceName, "write"}, "/"), func(msg string) {
    fmt.Printf("\nGOT THIS: %s\n", msg)
  })

  // Check for config changes every 5 seconds
  config.ReloadOnChange(1)
}
