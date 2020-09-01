package main

import (
	// "fmt"
	"os"
	"os/signal"
	"syscall"

	mqttDestination "./helpers/mqtt"
	mqttSource "./helpers/mqtt"
)

const serviceName = "core/mqttForward"

const sourceBroker = "tcp://192.168.0.100:1883"
const destinationBroker = "tcp://192.168.0.113:1883"

func main() {
	// Create channel to monitor interrupt signals
	c := make(chan os.Signal, 1)
	signal.Notify(c, os.Interrupt, syscall.SIGTERM)

	// Connect
	mqttSource.Connect(serviceName, sourceBroker)
	mqttDestination.Connect(serviceName, destinationBroker)

	// Forward
	mqttSource.SubscribeWithTopic("#", func(topic string, msg string) {
		// fmt.Println("RECEIVED: " + topic + ": " + msg)
		mqttDestination.PublishOn(topic, msg)
	})

	// Keep alive until interrupt is received
	<-c
}
