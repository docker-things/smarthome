package functions

import (
// "fmt"

// json "../helpers/json"
// // mqtt "../helpers/mqtt"
// config "../config"
// db "../helpers/mysql"

// "encoding/json"
// "fmt"
// "os"
// "os/signal"
// "syscall"

// jsonHelper "./helpers/json"
// mqtt "./helpers/mqtt"
// state "./state"
// config "./config"
// functions "./functions"

)


func StartService(monolith bool) {

  // // Create channel to monitor interrupt signals
  // c := make(chan os.Signal, 1)
  // signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  // mqtt.Connect(ServiceName, "tcp://"+os.Getenv("MQTT_HOST_CORE"))

  // state.CreateClient(functions.ServiceName)

  // listenForRunAsyncRequests()
  // listenForRunSyncRequests()

  // // Keep alive until interrupt is received
  // <-c
}

func listenForRunSyncRequests() {/*
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
*/}

func listenForRunAsyncRequests() {/*
  mqtt.Subscribe(state.TopicRequest, func(msg string) {
    fmt.Println("REQUEST: " + msg)

    var request map[string]string
    err := json.Unmarshal([]byte(msg), &request)
    if err != nil {
      panic(err.Error())
    }

    if _, ok := request["key"]; !ok {
      fmt.Println("WARN: Request contains no \"key\"!")
      return
    }

    if _, ok := request["responseTopic"]; !ok {
      fmt.Println("WARN: Request contains no \"responseTopic\"!")
      return
    }

    var stateJson string

    if request["key"] == "" {
      stateJson = state.GetJSON()
    } else {
      fmt.Println("WARN: Deep key not implemented!")
      return
    }

    fmt.Println("Sending config to " + request["responseTopic"])
    mqtt.PublishOn(request["responseTopic"], stateJson)
  })
*/}
