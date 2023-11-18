package incoming

import (
  "fmt"
  "os"
  "os/signal"
  "syscall"

  config "app/config"
  json "app/helpers/json"
  mqtt "app/helpers/mqtt"
  state "app/state"
)

func StartService(monolith bool) {
  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  mqtt.Connect(ServiceName, "tcp://"+os.Getenv("MQTT_HOST"))

  createConfigClient()
  createStateClient()

  listenForIncomingData()

  // Keep alive until interrupt is received
  <-c
}

func listenForIncomingData() {
  mqtt.SubscribeWithTopic(TopicIncoming, func(topic string, msg string) {
    if shouldIgnoreTopic(topic) {
      return
    }

    object := getObjectFromTopic(topic)
    if object == "" {
      return
    }

    fmt.Printf("%s: %s\n", topic, msg)

    var data map[string]interface{}
    err := json.Unmarshal([]byte(msg), &data)
    if err != nil {
      data = make(map[string]interface{})
      data["RAW"] = msg
    }
    data["mqtt-topic"] = topic

    // state.Set(source, name, value, func() {
    //   fmt.Println("[DEBUG] Announcing change")
    //   variable := state.GetVariable(source, name)
    //   mqtt.PublishOn(state.TopicAnnounce, jsonHelper.Encode(variable))
    // })
  })
}

func shouldIgnoreTopic(topic string) bool {
  topicStart := topic[0:5]
  if topicStart == "core-" || topicStart == "core/" {
    return true
  }
  return false
}

func createStateClient() {
  state.CreateClient(ServiceName)
}

func createConfigClient() {
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

func getObjectFromTopic(topic string) string {
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
