package config

import (
  "strings"
  "sync"
)

const ServiceName = "core/config"
const MqttBroker = "tcp://mqtt:1883"
const ConfigPath = "/app/data/config"

// IN
var TopicRequest = strings.Join([]string{ServiceName, "request"}, "/")

// OUT
var TopicAnnounce = strings.Join([]string{ServiceName, "announce"}, "/")

// CONFIG
type configType struct {
  value map[string]interface{}
  json string
  mutex sync.Mutex
}

var config configType

// CONFIG PATH
var configPath string

// On change callback
type onChangeCallbackType func(string)

var onChangeCallback onChangeCallbackType
