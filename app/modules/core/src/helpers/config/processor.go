package config

import (
  "errors"
  "fmt"
  "os"
  "reflect"
  "regexp"
  "strconv"
  // "path"
  // "path/filepath"
  // "github.com/ghodss/yaml"
  // "io/ioutil"
  "strings"

  deepcopy "../deepcopy"
  json "../json"
)

var regex map[string]*regexp.Regexp

func dumpPath(path string, tree map[string]interface{}) {
  where := strings.Split(path, ".")
  value, err := getAbsoluteTreeValue(where, tree)
  if err != nil {
    fmt.Printf("\n[DUMP] %s: %s\n", path, err.Error())
  } else {
    fmt.Printf("\n[DUMP][%p] %s:\n%s\n", value, path, json.Encode(value))
  }
}

func getPointer(value interface{}) string {
  return fmt.Sprintf("%p", value)
}

func processConfig(config map[string]interface{}) {
  preProcessVariablesIn(config, []string{}, config, []string{ /* EXCEPT FOR */
    "PARAMS",
    "RESPONSE",
    "ARGS",
    "Properties",
  })
  preProcessObjectsIn(config)
  preProcessVariablesIn(config, []string{}, config, []string{ /* EXCEPT FOR */
    "PARAMS",
    "RESPONSE",
    "ARGS",
  })

  // TODO: Do the rest of the processing!!!

  // TODO: Remove debug stuff
  // where := []string{"Module", "Device", "CEC", "Functions", "functions", "on()", "run"}
  fmt.Printf("\n>>>>>\n")
  dumpPath("Objects.SystemNotify", config)
  dumpPath("Objects.SystemWarn", config)
  fmt.Printf("\n<<<<<\n")
  os.Exit(1)
}

func preProcessObjectsIn(fullConfig map[string]interface{}) {

  // Get objects
  objectsInterface, wtfErr := getAbsoluteTreeValue([]string{"Objects"}, fullConfig)
  if wtfErr != nil {
    fmt.Println("config:processor:preProcessObjectsIn(): " + wtfErr.Error())
    panic("config:processor:preProcessObjectsIn(): WTF! This should... like... NEVER HAPPEN!!!")
  }
  objects := objectsInterface.(map[string]interface{})

  // For each object
  for objectName, objectInterface := range objects {
    object := objectInterface.(map[string]interface{})

    // Why no base?
    if _, ok := object["base"]; !ok {
      panic("config.processor.preProcessObjectsIn(): Object " + objectName + " has no base!")
    }

    // Get base config
    baseConfig, err := getAbsoluteTreeValue(strings.Split(object["base"].(string), "."), fullConfig)
    if err != nil {
      fmt.Println("config.processor.preProcessObjectsIn(): Couldn't find the base config for object '" + objectName + "'")
      panic("config:processor:preProcessObjectsIn(): " + err.Error())
    }

    // Set base config
    object["base"] = make(map[string]interface{}, 0)
    deepcopy.Map(baseConfig.(map[string]interface{}), object["base"].(map[string]interface{}))
    base := object["base"].(map[string]interface{})

    // Get current properties
    if _, ok := base["Properties"]; !ok {
      base["Properties"] = make(map[string]interface{}, 0)
    }
    properties := base["Properties"].(map[string]interface{})

    // If there are properties to set
    if _, ok := object["with"]; ok {
      for key, value := range object["with"].(map[string]interface{}) {
        properties[key] = value
      }
    }

    // Set object name in properties
    properties["selfObjectName"] = objectName

    // Replace object definition with the newly built one
    objects[objectName] = base
  }
}

func preProcessVariablesIn(config interface{}, path []string, fullConfig map[string]interface{}, except []string) {
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
      fmt.Printf("UNKNOWN TYPE: '%s' @ %s\n", reflect.ValueOf(config).Kind().String(), json.Encode(path))
    }
  }
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

func preProcessString(str string, path []string, fullConfig map[string]interface{}, except []string) string {
  // strings.Join(path, ".") == "Module.Heating.Cron.jobs.0.run.0"
  fmt.Println("preProcessString(): " + str + " in " + json.Encode(path))
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
      panic("config:processor:preProcessString(): VARIABLE NOT FOUND!\nSEARCH = " + match[1] + "\nPATH = " + strings.Join(path, "."))
    }
    if reflect.ValueOf(value).Kind().String() == "float64" {
      value = fmt.Sprintf("%g", value)
    } else if reflect.ValueOf(value).Kind().String() != "string" {
      panic("config:processor:preProcessString(): Variable found is not a string! It's '" + reflect.ValueOf(value).String() + "'\nSEARCH = " + match[1] + "\nPATH = " + strings.Join(path, "."))
    }

    newStr = strings.Replace(newStr, match[0], value.(string), -1)

    // fmt.Printf("\nPATH: %s\n", strings.Join(path, "."))
    // fmt.Printf("FROM: \"%s\"\nTO:   \"%s\"\n", str, newStr)
  }

  return newStr
}

func getClosestTreeValue(requiredPath []string, currentPath []string, fullConfig map[string]interface{}) (interface{}, error) {
  // fmt.Printf("\nconfig:processor:getClosestTreeValue(): %s, %s\n", json.Encode(requiredPath), json.Encode(currentPath))
  // fmt.Println("currentPath = " + json.Encode(currentPath))
  for len(currentPath) != 0 {
    // Go up a level
    currentPath = currentPath[:len(currentPath)-1]
    // fmt.Println("currentPath = " + json.Encode(currentPath))

    // Get current path in which we should search
    currentPathConfig, wtfErr := getAbsoluteTreeValue(currentPath, fullConfig)
    if wtfErr != nil {
      fmt.Println("config:processor:getClosestTreeValue(): " + wtfErr.Error())
      panic("config:processor:getClosestTreeValue(): WTF! This should... like... NEVER HAPPEN!!!")
    }

    // Search for the required path
    requiredPathValue, err := getAbsoluteTreeValue(requiredPath, currentPathConfig)

    // If value found return it
    if err == nil {
      // fmt.Println("FOUND " + json.Encode(currentPath) + "." + json.Encode(requiredPath) + " = " + json.Encode(requiredPathValue))
      return requiredPathValue, nil
    }
    // fmt.Println("Not found: " + strings.Join(requiredPath, "."))
  }

  // fmt.Println("config:processor:getClosestTreeValue(): Path not found: " + strings.Join(requiredPath, "."))
  // Shit happens
  err := errors.New("config:processor:getClosestTreeValue(): Path not found: " + strings.Join(requiredPath, "."))
  return nil, err
}

func getAbsoluteTreeValue(requiredPath []string, config interface{}) (interface{}, error) {
  // fmt.Printf("\nconfig:processor:getAbsoluteTreeValue(): %s\n%s\n", json.Encode(requiredPath), json.Encode(config))
  fmt.Printf("\nconfig:processor:getAbsoluteTreeValue(): %s\n", json.Encode(requiredPath))
  if len(requiredPath) == 0 {
    fmt.Printf("len(requiredPath) == 0\n")
    return config, nil
  }

  for true {
    fmt.Printf("requiredPath = [%s] %s\n", reflect.ValueOf(config).Kind().String(), strings.Join(requiredPath, "."))
    switch reflect.ValueOf(config).Kind().String() {
    case "map":
      mapConfig := config.(map[string]interface{})

      // Break if next key doesn't exist
      if _, ok := mapConfig[requiredPath[0]]; !ok {
        return nil, errors.New("config:processor:getAbsoluteTreeValue(): Path not found: " + json.Encode(requiredPath))
        break
      }

      // If there's a single key left
      if len(requiredPath) == 1 {
        fmt.Println(" > FOUND!")
        return mapConfig[requiredPath[0]], nil
      }

      // Otherwise go deeper
      config = mapConfig[requiredPath[0]]

    case "slice":
      sliceConfig := config.([]interface{})

      // Make int key
      fmt.Println(requiredPath[0])
      key, err := strconv.Atoi(requiredPath[0])
      if err != nil {
        return nil, errors.New("config:processor:getAbsoluteTreeValue(): Couldn't make int out of string from '" + requiredPath[0] + "'")
      }

      // Break if next key doesn't exist
      if len(sliceConfig) < key {
        fmt.Println(" > not found")
        return nil, errors.New("config:processor:getAbsoluteTreeValue(): Path not found: " + json.Encode(requiredPath))
      }

      // If there's a single key left
      if len(requiredPath) == 1 {
        fmt.Println(" > FOUND!")
        return sliceConfig[key], nil
      }

      // Otherwise go deeper
      config = sliceConfig[key]
    default:
      return nil, errors.New("config:processor:getAbsoluteTreeValue(): Got type [" + reflect.ValueOf(config).Kind().String() + "]")
    }

    // Shift required path
    requiredPath = requiredPath[1:]
  }

  return nil, errors.New("config:processor:getAbsoluteTreeValue(): Path not found: " + json.Encode(requiredPath))
}
