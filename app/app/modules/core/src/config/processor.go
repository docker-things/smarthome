package config

import (
  "errors"
  "fmt"
  // "os"
  "reflect"
  "regexp"
  "strconv"
  // "path"
  // "path/filepath"
  // "github.com/ghodss/yaml"
  // "io/ioutil"
  "strings"

  deepcopy "app/helpers/deepcopy"
  json "app/helpers/json"
  log "app/helpers/log"
)

var regex map[string]*regexp.Regexp

func dumpPath(path string, tree map[string]interface{}) {
  where := strings.Split(path, ".")
  value, err := getAbsoluteTreeValue(where, tree)
  if err != nil {
    log.Dump(path, err.Error())
  } else {
    log.Dump(value, path)
  }
}

func processConfig(config map[string]interface{}) {
  log.Init()

  preProcessVariablesIn(config, []string{}, config, []string{ /* EXCEPT FOR */
    "PARAMS",
    "RESPONSE",
    "ARGS",
    "Properties",
  })
  preProcessObjectsIn(config)

  // TODO: Do the rest of the processing!!!

  // TODO: Remove debug stuff
  // where := []string{"Module", "Device", "CEC", "Functions", "functions", "on()", "run"}
  // fmt.Printf("\n>>>>>\n")
  // dumpPath("Objects.SystemNotify", config)
  // dumpPath("Objects.SystemWarn", config)
  // fmt.Printf("\n<<<<<\n")
  // os.Exit(1)
}

func preProcessObjectsIn(fullConfig map[string]interface{}) {

  // Get objects
  objectsInterface, wtfErr := getAbsoluteTreeValue([]string{"Objects"}, fullConfig)
  if wtfErr != nil {
    log.Panic("WTF! This should... like... NEVER HAPPEN!!!\n" + wtfErr.Error())
  }
  objects := objectsInterface.(map[string]interface{})

  // For each object
  for objectName, objectInterface := range objects {
    object := objectInterface.(map[string]interface{})

    // Why no base?
    if _, ok := object["base"]; !ok {
      log.Panic("Object " + objectName + " has no base!")
    }

    // Get base config
    baseConfig, err := getAbsoluteTreeValue(strings.Split(object["base"].(string), "."), fullConfig)
    if err != nil {
      log.Panic("Couldn't find the base config for object '" + objectName + "'\nError: " + err.Error())
    }

    // Set base config
    // object["base"] = make(map[string]interface{}, 0)
    // deepcopy.Map(baseConfig.(map[string]interface{}), object["base"].(map[string]interface{}))
    // base := object["base"].(map[string]interface{})
    base := make(map[string]interface{}, 0)
    deepcopy.Map(baseConfig.(map[string]interface{}), base)

    // Get current properties
    if _, ok := base["Properties"]; !ok {
      base["Properties"] = make(map[string]interface{}, 0)
    }
    properties := base["Properties"].(map[string]interface{})

    // If there are properties to set
    if _, ok := object["with"]; ok {
      // log.Info("deepOverwrite properties for " + objectName)
      deepOverwrite(properties, object["with"].(map[string]interface{}))
    }

    // Set object name in properties
    properties["selfObjectName"] = objectName

    // Replace object definition with the newly built one
    objects[objectName] = base

    preProcessVariablesIn(objects, []string{"Objects"}, fullConfig, []string{ /* EXCEPT FOR */
      "PARAMS",
      "RESPONSE",
      "ARGS",
    })
  }
}

func deepOverwrite(to map[string]interface{}, from map[string]interface{}) {
  for key, value := range from {
    switch reflect.ValueOf(value).Kind().String() {
    case "map":
      if _, ok := to[key]; !ok {
        log.Panic("deepOverwrite(): key = \"" + key + "\"")
      }
      deepOverwrite(to[key].(map[string]interface{}), from[key].(map[string]interface{}))
    default:
      to[key] = value
    }
  }
}

func preProcessVariablesIn(config interface{}, path []string, fullConfig map[string]interface{}, except []string) {
  log.PushPath("preProcessVariablesIn")
  defer log.PopPath()

  // log.Info(strings.Join(path, "."))

  switch reflect.ValueOf(config).Kind().String() {
  case "map":
    for k, v := range config.(map[string]interface{}) {
      subPath := append(path, k)
      if reflect.ValueOf(v).Kind().String() == "string" {
        config.(map[string]interface{})[k] = preProcessString(v.(string), subPath, fullConfig, except)
      } else {
        preProcessVariablesIn(v, subPath, fullConfig, except)
      }
    }
  case "slice":
    for k, v := range config.([]interface{}) {
      subPath := append(path, strconv.Itoa(k))
      if reflect.ValueOf(v).Kind().String() == "string" {
        config.([]interface{})[k] = preProcessString(v.(string), subPath, fullConfig, except)
      } else {
        preProcessVariablesIn(v, subPath, fullConfig, except)
      }
    }
  default: // TODO: REMOVE
    if reflect.ValueOf(config).Kind().String() != "float64" {
      log.Warn("UNKNOWN TYPE: '" + reflect.ValueOf(config).Kind().String() + "' @ " + json.Encode(path))
    }
  }
}

func preProcessString(str string, path []string, fullConfig map[string]interface{}, except []string) string {
  log.PushPath("preProcessString")
  defer log.PopPath()

  // strings.Join(path, ".") == "Module.Heating.Cron.jobs.0.run.0"
  // log.Info("\"" + str + "\" in " + json.Encode(path))
  matches := regex["variable"].FindAllStringSubmatch(str, -1)
  if len(matches) == 0 || matches[0][1] == "" {
    return str
  }

  newStr := str

  for _, match := range matches {
    param := strings.Split(match[1], ".")

    // Skip excluded params
    if contains(param[0], except) {
      continue
    }

    value, err := getClosestTreeValue(strings.Split(match[1], "."), path, fullConfig)
    if err != nil {
      log.Panic("VARIABLE NOT FOUND!\nSEARCH = " + match[1] + "\nPATH = " + strings.Join(path, ".") + "\nERROR = " + err.Error())
    }
    if reflect.ValueOf(value).Kind().String() == "float64" {
      value = fmt.Sprintf("%g", value)
    } else if reflect.ValueOf(value).Kind().String() != "string" {
      log.Panic("Variable found is not a string! It's '" + reflect.ValueOf(value).String() + "'\nSEARCH = " + match[1] + "\nPATH = " + strings.Join(path, "."))
    }

    newStr = strings.Replace(newStr, match[0], value.(string), -1)
  }

  return newStr
}

func getClosestTreeValue(requiredPath []string, currentPath []string, fullConfig map[string]interface{}) (interface{}, error) {
  log.PushPath("getClosestTreeValue")
  defer log.PopPath()
  // log.Info("Search for \"" + strings.Join(requiredPath, ".") + "\"")

  log.Deactivate()

  for len(currentPath) != 0 {
    // Go up a level
    currentPath = currentPath[:len(currentPath)-1]
    // log.Info("In: " + strings.Join(currentPath, "."))

    // Get current path in which we should search
    log.IgnoreFrom()
    currentPathConfig, wtfErr := getAbsoluteTreeValue(currentPath, fullConfig)
    log.IgnoreTo()
    if wtfErr != nil {
      log.Panic("WTF! This should... like... NEVER HAPPEN!!!\n" + wtfErr.Error())
    }

    // Search for the required path
    requiredPathValue, err := getAbsoluteTreeValue(requiredPath, currentPathConfig)

    // If value found return it
    if err == nil {
      // log.Info("FOUND " + json.Encode(currentPath) + "." + json.Encode(requiredPath) + " = " + json.Encode(requiredPathValue))
      log.Activate()
      return requiredPathValue, nil
    }
    // log.Warn("Not found")
  }

  log.ShowDeactivatedMessages()
  log.Activate()

  // Shit happens
  log.Error("Path not found: " + strings.Join(requiredPath, "."))
  err := errors.New("Path not found: " + strings.Join(requiredPath, "."))
  return nil, err
}

func getAbsoluteTreeValue(requiredPath []string, config interface{}) (interface{}, error) {
  // log.PushPath("getAbsoluteTreeValue")
  // defer log.PopPath()

  if len(requiredPath) == 0 {
    // log.Info("len(requiredPath) == 0")
    return config, nil
  }

  for true {
    // log.Info("[" + reflect.ValueOf(config).Kind().String() + "] " + strings.Join(requiredPath, "."))
    switch reflect.ValueOf(config).Kind().String() {
    case "map":
      mapConfig := config.(map[string]interface{})

      // Break if next key doesn't exist
      if _, ok := mapConfig[requiredPath[0]]; !ok {
        // log.Warn("Path not found: " + strings.Join(requiredPath, "."))
        return nil, errors.New("Path not found: " + strings.Join(requiredPath, "."))
        break
      }

      // If there's a single key left
      if len(requiredPath) == 1 {
        // log.Info("FOUND!")
        return mapConfig[requiredPath[0]], nil
      }

      // Otherwise go deeper
      config = mapConfig[requiredPath[0]]

    case "slice":
      sliceConfig := config.([]interface{})

      // Make int key
      key, err := strconv.Atoi(requiredPath[0])
      if err != nil {
        // log.Error("Couldn't make int out of string from '" + requiredPath[0] + "'")
        return nil, errors.New("Couldn't make int out of string from '" + requiredPath[0] + "'")
      }

      // Break if next key doesn't exist
      if len(sliceConfig) < key {
        // log.Warn("Path not found: " + strings.Join(requiredPath, "."))
        return nil, errors.New("Path not found: " + strings.Join(requiredPath, "."))
      }

      // If there's a single key left
      if len(requiredPath) == 1 {
        // log.Info("FOUND!")
        return sliceConfig[key], nil
      }

      // Otherwise go deeper
      config = sliceConfig[key]
    default:
      // log.Warn("Got type [" + reflect.ValueOf(config).Kind().String() + "]")
      return nil, errors.New("Got type [" + reflect.ValueOf(config).Kind().String() + "]")
    }

    // Shift required path
    requiredPath = requiredPath[1:]
  }

  // log.Warn("Path not found: " + strings.Join(requiredPath, "."))
  return nil, errors.New("Path not found: " + strings.Join(requiredPath, "."))
}

func preProcessRegexp() {
  regex = make(map[string]*regexp.Regexp)
  regex["variable"] = regexp.MustCompile("\\${([a-zA-Z0-9.-]+)}")
}

func contains(searchTerm string, list []string) bool {
  for _, value := range list {
    if value == searchTerm {
      return true
    }
  }
  return false
}
