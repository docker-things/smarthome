package state

import (
  "sync"
  "strings"

  db "../helpers/mysql"
)

const ServiceName = "core/state"
const MqttBroker = "tcp://mqtt:1883"

// IN
var TopicSet = strings.Join([]string{ServiceName, "set"}, "/")
var TopicRequest = strings.Join([]string{ServiceName, "request"}, "/")

// OUT
var TopicAnnounce = strings.Join([]string{ServiceName, "announce"}, "/")

type stateType struct {
  value map[string]map[string]db.StateType
  json string
  mutex sync.Mutex
}

var state stateType
