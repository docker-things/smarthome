package config

import (
  "encoding/json"
  "strings"
  "time"

  mqtt "../helpers/mqtt"
  randomString "../helpers/randomString"
)

var clientID string

var gotInitialResponse bool

func CreateClient(serviceName string, onChange func()) {
  gotInitialResponse = false

  id := randomString.RandomString(16)
  responseTopic := strings.Join([]string{serviceName, "config-client", id}, "/")

  mqtt.Connect(serviceName, Broker)

  listenForAnnouncements()
  listenForRequestResponse(responseTopic)

  requestInitialConfig(responseTopic)
}

func listenForAnnouncements() {
  mqtt.Subscribe(TopicAnnounce, func(msg string) {
    var data map[string]interface{}
    err := json.Unmarshal([]byte(msg), &data)
    if err != nil {
      panic(err.Error())
    }
    setNewConfig(data)
  })
}

func listenForRequestResponse(responseTopic string) {
  mqtt.Subscribe(responseTopic, func(msg string) {
    var data map[string]interface{}
    err := json.Unmarshal([]byte(msg), &data)
    if err != nil {
      panic(err.Error())
    }
    setNewConfig(data)
    gotInitialResponse = true
  })
}

func requestInitialConfig(responseTopic string) {
  for {
    mqtt.PublishOn(TopicRequest, "{\"path\":\"\",\"responseTopic\":\""+responseTopic+"\"}")
    for i := 0; i < 5; i++ {
      if gotInitialResponse {
        return
      }
      time.Sleep(1 * time.Second)
    }
  }
}

func GetPath(requiredPath []string) interface{} {
  config.mutex.Lock()
  defer config.mutex.Unlock()
  value, _ := getAbsoluteTreeValue(requiredPath, config.value)
  return value
}
