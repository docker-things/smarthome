package main

import (
  "fmt"
  "os"
  "os/signal"
  "syscall"

  mqtt "./helpers/mqtt"
)

const serviceName = "core/mqtt-listener"
const broker = "tcp://localhost:1883"

func main() {
  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  // Connect
  mqtt.Connect(serviceName, broker)

  // Steal
  mqtt.SubscribeWithTopic("#", func(topic string, msg string) {
    fmt.Println(topic + ": " + msg)
    // mqtt.PublishOn(destinationBroker, topic, msg)
  })

  // Keep alive until interrupt is received
  <-c
}
