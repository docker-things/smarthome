package state

import (
  "encoding/json"
  "strings"
  "time"

  mqtt "../helpers/mqtt"
  db "../helpers/mysql"
  randomString "../helpers/randomString"
)

var clientID string

var gotInitialResponse bool

func CreateClient(serviceName string) {
  gotInitialResponse = false

  id := randomString.RandomString(16)
  responseTopic := strings.Join([]string{serviceName, "state-client", id}, "/")

  mqtt.Connect(serviceName, MqttBroker)

  listenForAnnouncements()
  listenForRequestResponse(responseTopic)

  requestInitialState(responseTopic)
}

func listenForAnnouncements() {
  mqtt.Subscribe(TopicAnnounce, func(msg string) {
    var data map[string]map[string]db.StateType
    err := json.Unmarshal([]byte(msg), &data)
    if err != nil {
      panic(err.Error())
    }
    setNewPartialState(data)
  })
}

func listenForRequestResponse(responseTopic string) {
  mqtt.Subscribe(responseTopic, func(msg string) {
    var data map[string]map[string]db.StateType
    err := json.Unmarshal([]byte(msg), &data)
    if err != nil {
      panic(err.Error())
    }
    setNewState(data)
    gotInitialResponse = true
  })
}

func requestInitialState(responseTopic string) {
  for {
    mqtt.PublishOn(TopicRequest, "{\"source\":\"\",\"name\":\"\",\"responseTopic\":\""+responseTopic+"\"}")
    for i := 0; i < 5; i++ {
      if gotInitialResponse {
        return
      }
      time.Sleep(1 * time.Second)
    }
  }
}
