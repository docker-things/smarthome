package mqtt

import (
  "fmt"
  "strings"

  mqtt "github.com/eclipse/paho.mqtt.golang"
)

const mqttBroker = "tcp://localhost:1883"

var serviceName string
var connection mqtt.Client

func Connect(newServiceName string) {
  fmt.Printf("\nMQTT: Connect(%s)\n", newServiceName)

  // Set service name locally
  serviceName = newServiceName

  // Config mqtt connection
  opts := mqtt.NewClientOptions().AddBroker(mqttBroker).SetClientID(serviceName)
  opts.SetCleanSession(true)

  // Connect
  connection = mqtt.NewClient(opts)
  if token := connection.Connect(); token.Wait() && token.Error() != nil {
    panic(token.Error())
  }
}

func Subscribe(topic string, callback func(string)) {
  fmt.Printf("\nMQTT: Subscribe(%s)\n", topic)

  if token := connection.Subscribe(topic, 0, func(client mqtt.Client, msg mqtt.Message) {
    callback(string(msg.Payload()))
  }); token.Wait() && token.Error() != nil {
    panic(token.Error())
  }
}

func Publish(msg string) {
  PublishOn(strings.Join([]string{serviceName, "read"}, "/"), msg)
}

func PublishOn(topic string, msg string) {
  // fmt.Printf("\nMQTT: PublishOn(%s, %s)\n", topic, msg)

  connection.Publish(topic, 0, false, msg)
}
