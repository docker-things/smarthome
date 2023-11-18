package main

import (
  "fmt"
  "os"
  "os/signal"
  "syscall"

  mqtt "app/helpers/mqtt"
)

const serviceName = "core/mqtt-stealer"

func main() {
  sourceBroker := "tcp://192.168.0.100:1883"
  destinationBroker := "tcp://" + os.Getenv("MQTT_HOST")

  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  fmt.Println("Connecting to source: " + sourceBroker)
  mqtt.Connect(serviceName, sourceBroker)

  fmt.Println("Connecting to destination: " + destinationBroker)
  mqtt.Connect(serviceName, destinationBroker)

  fmt.Println("Forwarding...")
  mqtt.BrokerSubscribeWithTopic(sourceBroker, "#", func(topic string, msg string) {
    fmt.Println(topic + ": " + msg)
    mqtt.BrokerPublishOn(destinationBroker, topic, msg)
  })

  // Keep alive until interrupt is received
  <-c
}
