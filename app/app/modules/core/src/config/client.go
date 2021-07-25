package config

import (
  "encoding/json"
  "strings"

  mqtt "../helpers/mqtt"
  randomString "../helpers/randomString"
)

var clientID string

func CreateClient(serviceName string, onChange func()) {
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
  })
}

func requestInitialConfig(responseTopic string) {
  mqtt.PublishOn(TopicRequest, "{\"path\":\"\",\"responseTopic\":\""+responseTopic+"\"}")
}

func GetPath(requiredPath []string) interface{} {
  config.mutex.Lock()
  defer config.mutex.Unlock()
  value, _ := getAbsoluteTreeValue(requiredPath, config.value)
  return value
}
