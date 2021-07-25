package state

import (
  "fmt"
  "time"
  // "os"
  // "os/signal"
  // "strings"
  // "syscall"

  json "../json"
  // mqtt "../mqtt"
  db "../mysql"
)

func GetJSON() string {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  stateJson := json.Encode(state.value)
  return stateJson
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

  setState(newState)

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
  if currentVar.Source == "" {
    return true
  }
  if currentVar.Value != newVar.Value {
    return true
  }
  if currentVar.TmpValue != "" && newVar.TmpValue == "" {
    return true
  }
  if currentVar.TmpTimes != newVar.TmpTimes {
    return true
  }
  // TODO: REQUIRED !!!! FUCKS UP THE LOGIC OTHERWISE !!!!
  // if force from incoming config to be always set {
  //   return true
  // }
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

func setState(newState map[string]map[string]db.StateType) {
  state.mutex.Lock()
  defer state.mutex.Unlock()
  state.value = newState
}

func MapHasAllKeys(data map[string]interface{}, keys []string) bool {
  return db.MapHasAllKeys(data, keys)
}
