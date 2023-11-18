package state

import (
  "sync"
  "strings"

  db "app/helpers/mysql"
)

const ServiceName = "core/state"

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
