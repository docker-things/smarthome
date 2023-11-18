package state

import (
  "fmt"
  "os"
  "os/signal"
  "reflect"
  "syscall"
  "time"

  config "app/config"
  json "app/helpers/json"
  mqtt "app/helpers/mqtt"
  db "app/helpers/mysql"
)

/**
 * TODO:
 * - populate a dirty list of maps when changes are made
 * - dump dirty changes to db every 30 seconds
 * - dump dirty changes on sig kill
 */

func StartService(monolith bool) {

  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  mqtt.Connect(ServiceName, "tcp://"+os.Getenv("MQTT_HOST_CORE"))

  connect()
  defer disconnect()

  initConfigClient()

  load(announceFullState)

  listenForIncomingRequests()
  listenForSetRequests()

  // Keep alive until interrupt is received
  <-c

  // TODO: DUMP DIRTY DATA!
  saveDirtyData()
}

func announceFullState() {
  fmt.Println("Announcing state")
  stateJson := getJSON()
  mqtt.PublishOn(TopicAnnounce, stateJson)
}

func listenForSetRequests() {
  requiredParams := []string{
    "source",
    "name",
    "value",
  }

  mqtt.Subscribe(TopicSet, func(msg string) {
    fmt.Println("SET: " + msg)

    var request map[string]interface{}
    err := json.Unmarshal([]byte(msg), &request)
    if err != nil {
      panic(err.Error())
    }

    if !mapHasAllKeys(request, requiredParams) {
      fmt.Printf("[WARN] Request must contain these params: %s", requiredParams)
      return
    }

    source := request["source"].(string)
    name := request["name"].(string)
    value := request["value"].(string)

    set(source, name, value, func() {
      fmt.Println("[DEBUG] Announcing change")
      variable := getVariable(source, name)
      mqtt.PublishOn(TopicAnnounce, json.Encode(variable))
    })
  })
}

func listenForIncomingRequests() {
  requiredParams := []string{
    "source",
    "name",
    "responseTopic",
  }

  mqtt.Subscribe(TopicRequest, func(msg string) {
    fmt.Println("REQUEST: " + msg)

    var request map[string]interface{}
    err := json.Unmarshal([]byte(msg), &request)
    if err != nil {
      panic(err.Error())
    }

    if !mapHasAllKeys(request, requiredParams) {
      fmt.Printf("[WARN] Request must contain these params: %s", requiredParams)
      return
    }

    var stateJson string

    source := request["source"].(string)
    responseTopic := request["responseTopic"].(string)

    if source == "" {
      stateJson = getJSON()
    } else {
      fmt.Println("WARN: Granular requests not implemented!")
      return
    }

    fmt.Println("Sending config to " + responseTopic)
    mqtt.PublishOn(responseTopic, stateJson)
  })
}

func getJSON() string {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  return state.json
}

func saveDirtyData() {
  fmt.Println("[WARN] saveDirtyData() NOT IMPLEMENTED!")
}

func connect() {
  db.Connect()
}
func disconnect() {
  db.Disconnect()
}

func initConfigClient() {
  config.CreateClient(ServiceName)
}

func load(callback func()) {
  getCurrentState()
  callback()
}

func getCurrentState() {
  response := db.GetCurrentState()

  objects := 0
  variables := 0

  newState := make(map[string]map[string]db.StateType, 0)
  for _, row := range response {
    // fmt.Println(row)

    if _, ok := newState[row.Source]; !ok {
      newState[row.Source] = make(map[string]db.StateType, 0)
      objects++
    }

    newState[row.Source][row.Name] = row
    variables++
  }

  setNewState(newState)

  fmt.Printf("loaded %d variables for %d objects\n", variables, objects)
}

func set(source string, name string, value string, callback func()) {
  currentVar := getVariable(source, name)
  prevValue := ""
  if currentVar.Source == "" {
    prevValue = currentVar.Value
  }

  newVar := db.StateType{
    Source:       source,
    Name:         name,
    Value:        value,
    PrevValue:    prevValue,
    Timestamp:    time.Now().Unix(),
    TmpValue:     "",
    TmpTimes:     int16(0),
    TmpTimestamp: int64(0),
  }

  if shouldSet(currentVar, newVar) {
    setVariableState(newVar)
    go db.SetState(newVar)
    callback()
  }
}

func shouldSet(currentVar db.StateType, newVar db.StateType) bool {
  return currentVar.Source == "" ||
    currentVar.Value != newVar.Value ||
    currentVar.TmpValue != "" && newVar.TmpValue == "" ||
    currentVar.TmpTimes != newVar.TmpTimes ||
    shouldAlwaysBeSet(newVar.Source, newVar.Name)
}

func shouldAlwaysBeSet(source string, name string) bool {
  value := config.GetPath([]string{
    "Objects", source, "Incoming", "alwaysSetWhenReceived",
  })
  if value != nil && reflect.ValueOf(value).Kind().String() == "slice" {
    for _, element := range value.([]interface{}) {
      if element.(string) == name {
        return true
      }
    }
  }
  return false
}

func setVariableState(newVar db.StateType) {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  if _, ok := state.value[newVar.Source]; !ok {
    state.value[newVar.Source] = make(map[string]db.StateType, 0)
  }
  state.value[newVar.Source][newVar.Name] = newVar
}

func getVariable(source string, name string) db.StateType {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  if sourceData, ok := state.value[source]; ok {
    if variableData, ok := sourceData[name]; ok {
      return variableData
    }
  }
  return db.StateType{}
}

func getStringParam(source string, name string, param string) string {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  if sourceData, ok := state.value[source]; ok {
    if variableData, ok := sourceData[name]; ok {
      return variableData.Value
    }
  }
  return ""
}

func setNewState(newState map[string]map[string]db.StateType) {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  state.value = newState
  state.json = json.Encode(state.value)
}

func setNewPartialState(newState map[string]map[string]db.StateType) {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  for source, variables := range newState {
    if _, ok := state.value[source]; !ok {
      state.value[source] = variables
      continue
    }
    for name, variable := range variables {
      state.value[source][name] = variable
    }
  }
  state.value = newState
  state.json = json.Encode(state.value)
}

func mapHasAllKeys(data map[string]interface{}, keys []string) bool {
  return db.MapHasAllKeys(data, keys)
}
