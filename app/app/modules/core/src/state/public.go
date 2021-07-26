package state

import (
  "fmt"
  "reflect"
  "time"
  // "os"
  // "os/signal"
  // "strings"
  // "syscall"

  json "../helpers/json"
  // mqtt "../helpers/mqtt"
  config "../config"
  db "../helpers/mysql"
)

func GetJSON() string {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  return state.json
}

func SaveDirtyData() {
  fmt.Println("[WARN] SaveDirtyData() NOT IMPLEMENTED!")
}

func Connect() {
  db.Connect()
}
func Disconnect() {
  db.Disconnect()
}

func InitConfigClient() {
  config.CreateClient(ServiceName)
}

func Load(callback func()) {
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

  fmt.Printf("Loaded %d variables for %d objects\n", variables, objects)
}

func Set(source string, name string, value string, callback func()) {
  currentVar := GetVariable(source, name)
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
    db.SetState(newVar)
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

func GetVariable(source string, name string) db.StateType {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  if sourceData, ok := state.value[source]; ok {
    if variableData, ok := sourceData[name]; ok {
      return variableData
    }
  }
  return db.StateType{}
}

func GetStringParam(source string, name string, param string) string {
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

func MapHasAllKeys(data map[string]interface{}, keys []string) bool {
  return db.MapHasAllKeys(data, keys)
}
