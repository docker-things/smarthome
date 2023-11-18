package main

import (
  "fmt"
  "os"
  "os/signal"
  "strings"
  "syscall"

  mqtt "app/helpers/mqtt"
)

const serviceName = "core/free-text-parser"
const broker = "tcp://mqtt:1883"

const topicPublishRun = "core-function/run"
const topicPublishSet = "core-state/set"

var topicParse = strings.Join([]string{serviceName, "parse"}, "/")

func main() {
  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  // Connect to MQTT
  mqtt.Connect(serviceName, broker)

  // Listen to incoming MQTT requests
  mqtt.Subscribe(topicParse, func(msg string) {
    parseText(msg)
  })

  <-c
}

func parseText(text string) {
  fmt.Println(text)
}
