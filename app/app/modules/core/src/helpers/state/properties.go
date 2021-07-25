package state

import (
  "sync"

  db "../mysql"
)

type stateType struct {
  value map[string]map[string]db.StateType
  mutex sync.Mutex
}

var state stateType
