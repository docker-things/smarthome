package incoming

import (
  "fmt"

  // json "../helpers/json"
  // // mqtt "../helpers/mqtt"
  config "../config"
  state "../state"
  // db "../helpers/mysql"
)

func CreateStateClient() {
  state.CreateClient(ServiceName)
}

func CreateConfigClient() {
  config.SetOnChangeCallback(onConfigChange)
  config.CreateClient(ServiceName)
}

const defaultTopic = "mqtt-loopback"

// data[topic][][object][][param] == topic

func onConfigChange(data map[string]interface{}) {
  newTopicRules := make(topicToObjectRulesType)

  // For each object
  objects := config.GetPath([]string{"Objects"}).(map[string]interface{})
  for objectName, objectInterface := range objects {

    // Check for 'Incoming' config
    object := objectInterface.(map[string]interface{})
    if _, ok := object["Incoming"]; !ok {
      continue
    }

    // Check for 'recognize-by-comparing' config
    incoming := object["Incoming"].(map[string]interface{})
    if _, ok := incoming["recognize-by-comparing"]; !ok {
      continue
    }

    // For each rule group
    rules := incoming["recognize-by-comparing"].([]interface{})
    for _, ruleInterface := range rules {
      rule := ruleInterface.(map[string]interface{})

      // Set topic in which the object will be added
      topic := defaultTopic // TODO: Should be replaced with actual source (ex: incoming-web)
      if topicInterface, ok := rule["${PARAMS.mqtt-topic}"]; ok {
        topic = topicInterface.(string)
      }

      // Assure structure
      if _, ok := newTopicRules[topic]; !ok {
        newTopicRules[topic] = make(objectRulesType)
      }
      if _, ok := newTopicRules[topic][objectName]; !ok {
        newTopicRules[topic][objectName] = make(rulesType, 0)
      }

      // Create new rule
      newRule := make(ruleType)
      for left, right := range rule {
        newRule[left] = right.(string)
      }

      // Add to main map
      newTopicRules[topic][objectName] = append(newTopicRules[topic][objectName], newRule)
    }
  }

  topicRules.mutex.Lock()
  defer topicRules.mutex.Unlock()
  topicRules.value = newTopicRules
}

func GetObjectFromTopic(topic string) string {
  // return ""
  topicRules.mutex.Lock()
  defer topicRules.mutex.Unlock()

  fmt.Printf("\n > CHECK TOPIC: %s\n", topic)

  // if _, ok := topicRules.value[topic]; ok {
  //   fmt.Printf("\n%s > %s\n", topic, topicRules.value[topic])
  //   // for objectName, rules := range topicRules.value[topic] {
  //   for objectName, _ := range topicRules.value[topic] {
  //     fmt.Printf(" > %s\n", objectName)
  //     // for _, rule := range rules {
  //     //   fmt.Printf("   > X\n")
  //     //   for left, right := range rule {
  //     //     fmt.Printf("     > %s: %s\n", left, right)
  //     //   }
  //     // }
  //   }
  // }

  if _, ok := topicRules.value[defaultTopic]; !ok {
    return ""
  }

  // fmt.Println(topicRules.value[defaultTopic])

  return ""
}
