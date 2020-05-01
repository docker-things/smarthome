package config

import (
  "errors"
  "fmt"
  // "os"
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
  preProcessVariablesIn(config, make([]string, 0), config)

  // TODO: Do the rest of the processing!!!

  // TODO: Remove debug stuff
  // where := []string{"Module","Device","CEC","Functions","cmd"}
  where := []string{"Module", "Device", "CEC", "Functions", "functions", "on()", "run"}
  value, _ := getAbsoluteTreeValue(where, config)
  fmt.Printf("\n>>>>>\n")
  fmt.Printf("\n%s = %s\n", strings.Join(where, "."), value)
  fmt.Printf("\n<<<<<\n")

}

func preProcessVariablesIn(config interface{}, path []string, fullConfig map[string]interface{}) {
  switch reflect.ValueOf(config).Kind().String() {
  case "map":
    for k, v := range config.(map[string]interface{}) {
      subPath := append(path, k)
      if reflect.ValueOf(v).Kind().String() == "string" {
        config.(map[string]interface{})[k] = preProcessString(v.(string), subPath, fullConfig)
      } else {
        preProcessVariablesIn(v, subPath, fullConfig)
      }
    }
  case "slice":
    for k, v := range config.([]interface{}) {
      subPath := append(path, strconv.Itoa(k))
      if reflect.ValueOf(v).Kind().String() == "string" {
        config.([]interface{})[k] = preProcessString(v.(string), subPath, fullConfig)
      } else {
        preProcessVariablesIn(v, subPath, fullConfig)
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

func preProcessString(str string, path []string, fullConfig map[string]interface{}) string {
  matches := regex["variable"].FindAllStringSubmatch(str, -1)
  if len(matches) == 0 || matches[0][1] == "" {
    return str
  }

  newStr := str

  for _, match := range matches {
    param := strings.Split(match[1], ".")

    // Skip runtime params
    switch param[0] {
    case "PARAMS", "RESPONSE", "ARGS", "Properties":
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
      // fmt.Println("config:processor:getClosestTreeValue(): " + wtfErr.Error())
      panic("config:processor:getClosestTreeValue(): This should... like... NEVER HAPPEN!!!")
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
