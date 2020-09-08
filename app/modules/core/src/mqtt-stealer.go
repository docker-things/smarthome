package main

import (
  "fmt"
  "os"
  "os/signal"
  "syscall"

  mqtt "./helpers/mqtt"
)

const serviceName = "core/mqtt-stealer"

const sourceBroker = "tcp://192.168.0.100:1883"
const destinationBroker = "tcp://localhost:1883"

func main() {
  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  // Connect
  mqtt.Connect(serviceName, sourceBroker)
  mqtt.Connect(serviceName, destinationBroker)

  // Steal
  mqtt.BrokerSubscribeWithTopic(sourceBroker, "#", func(topic string, msg string) {
    fmt.Println(topic + ": " + msg)
    mqtt.BrokerPublishOn(destinationBroker, topic, msg)
  })

  // Keep alive until interrupt is received
  <-c
}
