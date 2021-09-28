package main

import (
  "encoding/json"
  "fmt"
  "os"
  "os/signal"
  "syscall"

  // jsonHelper "./helpers/json"
  mqtt "./helpers/mqtt"
  incoming "./incoming"
)

/*package main

import (
  "fmt"
  "os"
  "os/signal"
  "syscall"

  mqtt "./helpers/mqtt"
)

const serviceName = "core/mqtt-listener"
const broker = "tcp://mqtt:1883"

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
*/

func main() {
  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  mqtt.Connect(incoming.ServiceName, incoming.MqttBroker)

  incoming.CreateConfigClient()
  incoming.CreateStateClient()

  listenForIncomingData()

  // Keep alive until interrupt is received
  <-c
}

func listenForIncomingData() {
  mqtt.SubscribeWithTopic(incoming.TopicIncoming, func(topic string, msg string) {
    if shouldIgnoreTopic(topic) {
      return
    }

    object := incoming.GetObjectFromTopic(topic)
    if object == "" {
      return
    }

    fmt.Printf("%s: %s\n", topic, msg)

    var data map[string]interface{}
    err := json.Unmarshal([]byte(msg), &data)
    if err != nil {
      data = make(map[string]interface{})
      data["RAW"] = msg
    }
    data["mqtt-topic"] = topic

    // state.Set(source, name, value, func() {
    //   fmt.Println("[DEBUG] Announcing change")
    //   variable := state.GetVariable(source, name)
    //   mqtt.PublishOn(state.TopicAnnounce, jsonHelper.Encode(variable))
    // })
  })
}

func shouldIgnoreTopic(topic string) bool {
  topicStart := topic[0:5]
  if topicStart == "core-" || topicStart == "core/" {
    return true
  }
  return false
}
