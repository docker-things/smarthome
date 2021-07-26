package functions

import (
  "strings"
  "sync"
)

const ServiceName = "core/functions"
const MqttBroker = "tcp://mqtt:1883"

// IN
var TopicRunSync = strings.Join([]string{ServiceName, "run"}, "/")
var TopicRunAsync = strings.Join([]string{ServiceName, "run-async"}, "/")
