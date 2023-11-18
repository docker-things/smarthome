package functions

import (
  "strings"
  // "sync"
)

const ServiceName = "core/functions"
const MqttBroker = "tcp://mqtt-core:18830" // TODO: READ FROM ENV

// IN
var TopicRunSync = strings.Join([]string{ServiceName, "run"}, "/")
var TopicRunAsync = strings.Join([]string{ServiceName, "run-async"}, "/")
