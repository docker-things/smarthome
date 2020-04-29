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
  fmt.Println("mqtt.Connect(): " + newServiceName)

  // Set service name locally
  serviceName = newServiceName

  // Config mqtt connection
  opts := mqtt.NewClientOptions().AddBroker(mqttBroker).SetClientID(serviceName)
  opts.SetCleanSession(true)

  // Connect
  connection = mqtt.NewClient(opts)
  if token := connection.Connect(); token.Wait() && token.Error() != nil {
    panic("mqtt.Connect(): " + token.Error().Error())
  }
}

func Subscribe(topic string, callback func(string)) {
  fmt.Println("mqtt.Subscribe(): " + topic)
  if token := connection.Subscribe(topic, 0, func(client mqtt.Client, msg mqtt.Message) {
    callback(string(msg.Payload()))
  }); token.Wait() && token.Error() != nil {
    panic("mqtt.Subscribe(): " + token.Error().Error())
  }
}

func Publish(msg string) {
  fmt.Println("mqtt.Publish(): " + msg)
  PublishOn(strings.Join([]string{serviceName, "read"}, "/"), msg)
}

func PublishOn(topic string, msg string) {
  fmt.Println("mqtt.PublishOn(): " + topic)
  connection.Publish(topic, 0, false, msg)
}
