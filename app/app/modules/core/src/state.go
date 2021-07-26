package main

import (
  "encoding/json"
  "fmt"
  "os"
  "os/signal"
  "syscall"

  jsonHelper "./helpers/json"
  mqtt "./helpers/mqtt"
  state "./state"
)

/**
 * TODO:
 * - populate a dirty list of maps when changes are made
 * - dump dirty changes to db every 30 seconds
 * - dump dirty changes on sig kill
 */

func main() {
  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  mqtt.Connect(state.ServiceName, state.MqttBroker)

  state.Connect()
  defer state.Disconnect()

  state.InitConfigClient()

  state.Load(announceFullState)

  listenForIncomingRequests()
  listenForSetRequests()

  // Keep alive until interrupt is received
  <-c

  // TODO: DUMP DIRTY DATA!
  state.SaveDirtyData()
}

func announceFullState() {
  fmt.Println("Announcing state")
  stateJson := state.GetJSON()
  mqtt.PublishOn(state.TopicAnnounce, stateJson)
}

func listenForSetRequests() {
  requiredParams := []string{
    "source",
    "name",
    "value",
  }

  mqtt.Subscribe(state.TopicSet, func(msg string) {
    fmt.Println("SET: " + msg)

    var request map[string]interface{}
    err := json.Unmarshal([]byte(msg), &request)
    if err != nil {
      panic(err.Error())
    }

    if !state.MapHasAllKeys(request, requiredParams) {
      fmt.Printf("[WARN] Request must contain these params: %s", requiredParams)
      return
    }

    source := request["source"].(string)
    name := request["name"].(string)
    value := request["value"].(string)

    state.Set(source, name, value, func() {
      fmt.Println("[DEBUG] Announcing change")
      variable := state.GetVariable(source, name)
      mqtt.PublishOn(state.TopicAnnounce, jsonHelper.Encode(variable))
    })
  })
}

func listenForIncomingRequests() {
  requiredParams := []string{
    "source",
    "name",
    "responseTopic",
  }

  mqtt.Subscribe(state.TopicRequest, func(msg string) {
    fmt.Println("REQUEST: " + msg)

    var request map[string]interface{}
    err := json.Unmarshal([]byte(msg), &request)
    if err != nil {
      panic(err.Error())
    }

    if !state.MapHasAllKeys(request, requiredParams) {
      fmt.Printf("[WARN] Request must contain these params: %s", requiredParams)
      return
    }

    var stateJson string

    source := request["source"].(string)
    responseTopic := request["responseTopic"].(string)

    if source == "" {
      stateJson = state.GetJSON()
    } else {
      fmt.Println("WARN: Granular requests not implemented!")
      return
    }

    fmt.Println("Sending config to " + responseTopic)
    mqtt.PublishOn(responseTopic, stateJson)
  })
}
