package main

import (
	"fmt"
	"os"
	"os/signal"
	"syscall"

	mqttDestination "./helpers/mqtt"
	mqttSource "./helpers/mqtt"
)

const serviceName = "core/mqtt-stealer"

const sourceBroker = "tcp://192.168.0.100:1883"
const destinationBroker = "tcp://localhost:1883"

func main() {
	// Create channel to monitor interrupt signals
	c := make(chan os.Signal, 1)
	signal.Notify(c, os.Interrupt, syscall.SIGTERM)

	// Connect
	mqttSource.Connect(serviceName, sourceBroker)
	mqttDestination.Connect(serviceName, destinationBroker)

	// Steal
	mqttSource.SubscribeWithTopic("#", func(topic string, msg string) {
		fmt.Println(topic + ": " + msg)
		mqttDestination.PublishOn(topic, msg)
	})

	// Keep alive until interrupt is received
	<-c
}
