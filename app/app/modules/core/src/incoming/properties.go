package incoming

import (
  "strings"
  "sync"
)

const ServiceName = "core/incoming"
const MqttBroker = "tcp://mqtt:1883"

// IN
var TopicRunSync = strings.Join([]string{ServiceName, "run"}, "/")
var TopicRunAsync = strings.Join([]string{ServiceName, "run-async"}, "/")
