package incoming

import (
  "sync"
)

const ServiceName = "core/incoming"

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
