package incoming

import (
  "sync"
)

const ServiceName = "core/incoming"
const MqttBroker = "tcp://mqtt:1883"

// IN
const TopicIncoming = "#"

type ruleType map[string]string
type rulesType []ruleType
type objectRulesType map[string]rulesType
type topicToObjectRulesType map[string]objectRulesType

type topicRulesType struct {
  value topicToObjectRulesType
  mutex sync.Mutex
}

var topicRules topicRulesType
