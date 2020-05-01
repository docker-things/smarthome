package config

import (
  "errors"
  "fmt"
  "os"
  "reflect"
  "regexp"
  "strconv"
  //     // "path"
  //     // "path/filepath"
  "strings"
  //     "io/ioutil"
  //     "github.com/ghodss/yaml"

  json "../json"
)

var regex map[string]*regexp.Regexp

func processConfig(config map[string]interface{}) {
  preProcessVariablesIn(config, []string{}, config, []string{ /* EXCEPT FOR */
    "PARAMS",
    "RESPONSE",
    "ARGS",
    "Properties",
  })
  preProcessObjectsIn(config)
  // preProcessVariablesIn(config, []string{}, config, []string{ /* EXCEPT FOR */
  //   "PARAMS",
  //   "RESPONSE",
  //   "ARGS",
  // })

  // TODO: Do the rest of the processing!!!

  // TODO: Remove debug stuff
  // where := []string{"Module", "Device", "CEC", "Functions", "functions", "on()", "run"}
  where := []string{"Objects", "SystemNotify"}
  value, _ := getAbsoluteTreeValue(where, config)
  fmt.Printf("\n>>>>>\n")
  fmt.Printf("\n%s = %s\n", strings.Join(where, "."), json.Encode(value))
  fmt.Printf("\n<<<<<\n")
  os.Exit(1)
}

func preProcessObjectsIn(fullConfig map[string]interface{}) {

  // Get objects
  result, wtfErr := getAbsoluteTreeValue([]string{"Objects"}, fullConfig)
  if wtfErr != nil {
    fmt.Println("config:processor:preProcessObjectsIn(): " + wtfErr.Error())
    panic("config:processor:preProcessObjectsIn(): WTF! This should... like... NEVER HAPPEN!!!")
  }
  objects := result.(map[string]interface{})

  // For each object
  for objectName, objectInterface := range objects {
    // fmt.Println("\nObject = " + objectName)

    object := objectInterface.(map[string]interface{})

    // Skip if there's no base
    if _, ok := object["base"]; !ok {
      panic("config.processor.preProcessObjectsIn(): Object " + objectName + " has no base!")
    }

    // Get base config
    // fmt.Println(" > Base = " + json.Encode(object["base"]))
    baseConfig, err := getAbsoluteTreeValue(strings.Split(object["base"].(string), "."), fullConfig)
    if err != nil {
      fmt.Println("config.processor.preProcessObjectsIn(): Couldn't find the base config for object '" + objectName + "'")
      panic("config:processor:preProcessObjectsIn(): " + err.Error())
    }

    // Set base config
    delete(object, "base")
    object["base"] = baseConfig.(map[string]interface{})
    base := object["base"].(map[string]interface{})

    // Get current properties
    var properties map[string]interface{}
    if _, ok := base["Properties"]; ok {
      properties = base["Properties"].(map[string]interface{})
    } else {
      properties = make(map[string]interface{}, 0)
      base["Properties"] = properties
    }
    // fmt.Println(" > Properties = " + json.Encode(properties))

    // If there are properties to set
    if _, ok := object["with"]; ok {

      // For each property
      for key, value := range object["with"].(map[string]interface{}) {
        // fmt.Println("   > SET: " + json.Encode(key) + " = " + json.Encode(value))
        if _, ok := properties[key]; ok {
          properties[key] = value
        }
      }
    }

    // Replace object definition with the newly built one
    delete(objects, objectName)
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

    if reflect.ValueOf(value).Kind().String() != "string" {
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
    requiredPathValue, err := getAbsoluteTreeValue(requiredPath, currentPathConfig.(map[string]interface{}))

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

func getAbsoluteTreeValue(requiredPath []string, config map[string]interface{}) (interface{}, error) {
  // fmt.Printf("\nconfig:processor:getAbsoluteTreeValue(): %s\n%s\n", json.Encode(requiredPath), json.Encode(config))
  if len(requiredPath) == 0 {
    // fmt.Printf("len(requiredPath) == 0\n")
    return config, nil
  }
  for true {
    // fmt.Println("requiredPath = " + json.Encode(requiredPath))
    // Break if next key doesn't exist
    if _, ok := config[requiredPath[0]]; !ok {
      // fmt.Println(" > not found")
      break
    }

    // If there's a single key left
    if len(requiredPath) == 1 {
      // fmt.Println(" > FOUND!")
      return config[requiredPath[0]], nil
    }

    // Otherwise go deeper
    config = config[requiredPath[0]].(map[string]interface{})
    requiredPath = requiredPath[1:]
  }

  // fmt.Println("requiredPath = " + json.Encode(requiredPath))

  err := errors.New("config:processor:getAbsoluteTreeValue(): Path not found: " + json.Encode(requiredPath))
  return nil, err
}
