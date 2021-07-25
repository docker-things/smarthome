package mqtt

import (
  // "fmt"
  "strings"

  mqtt "github.com/eclipse/paho.mqtt.golang"
)

var serviceName string
var lastBroker string
var connections map[string]mqtt.Client

func debug(text string) {
  // fmt.Println(text)
}

func Connect(newServiceName string, mqttBroker string) {
  debug("\n\nmqtt.Connect(): " + newServiceName + " @ " + mqttBroker)

  // Make sure the connections map is initialized
  if connections == nil {
    debug("mqtt.Connect(): nil connections")
    connections = make(map[string]mqtt.Client, 1)
  }

  if _, ok := connections[mqttBroker]; ok {
    debug("mqtt.Connect(): connection already exists!")
    return
  }
  // Set service name locally
  serviceName = newServiceName

  // Config mqtt connection
  opts := mqtt.NewClientOptions().AddBroker(mqttBroker).SetClientID(serviceName)
  opts.SetCleanSession(true)

  // Connect
  connections[mqttBroker] = mqtt.NewClient(opts)
  lastBroker = mqttBroker
  if token := connections[mqttBroker].Connect(); token.Wait() && token.Error() != nil {
    panic("mqtt.Connect(): " + token.Error().Error())
  }
  debug("mqtt.Connect(): connected")
}

func SubscribeWithTopic(topic string, callback func(string, string)) {
  debug("mqtt.SubscribeWithTopic(): " + topic)
  BrokerSubscribeWithTopic(lastBroker, topic, callback)
}

func Subscribe(topic string, callback func(string)) {
  debug("mqtt.Subscribe(): " + topic)
  BrokerSubscribe(lastBroker, topic, callback)
}

func Publish(msg string) {
  debug("mqtt.Publish(): " + msg)
  BrokerPublish(lastBroker, msg)
}

func PublishOn(topic string, msg string) {
  debug("mqtt.PublishOn(): " + topic)
  BrokerPublishOn(lastBroker, topic, msg)
}

func BrokerSubscribeWithTopic(broker string, topic string, callback func(string, string)) {
  debug("mqtt.BrokerSubscribeWithTopic(): [" + broker + "] " + topic)
  if token := connections[broker].Subscribe(topic, 0, func(client mqtt.Client, msg mqtt.Message) {
    callback(msg.Topic(), string(msg.Payload()))
  }); token.Wait() && token.Error() != nil {
    panic("mqtt.Subscribe(): " + token.Error().Error())
  }
}

func BrokerSubscribe(broker string, topic string, callback func(string)) {
  debug("mqtt.BrokerSubscribe(): [" + broker + "] " + topic)
  BrokerSubscribeWithTopic(broker, topic, func(foo string, msg string) {
    callback(msg)
  })
}

func BrokerPublish(broker string, msg string) {
  debug("mqtt.BrokerPublish(): [" + broker + "] " + msg)
  BrokerPublishOn(broker, strings.Join([]string{serviceName, "read"}, "/"), msg)
}

func BrokerPublishOn(broker string, topic string, msg string) {
  debug("mqtt.BrokerPublishOn(): [" + broker + "] " + topic)
  connections[broker].Publish(topic, 0, false, msg)
}
