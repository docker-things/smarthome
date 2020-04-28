package config

import (
  "fmt"
  "reflect"
  "regexp"
  //     // "path"
  //     // "path/filepath"
  "strings"
  //     "io/ioutil"
  //     "encoding/json"
  //     "github.com/ghodss/yaml"
)

var regex map[string]*regexp.Regexp

func processConfig(config map[string]interface{}) {
  preProcessVariablesIn(config)

  // where := []string{"Module","Device","CEC","Functions","cmd"}
  where := []string{"Module", "Device", "CEC", "Functions", "functions", "on()", "run"}
  value := getAbsoluteTreeValue(where, config)
  fmt.Printf("\n>>>>>\n")
  fmt.Printf("\n%s = %s\n", strings.Join(where, "."), value)
  fmt.Printf("\n<<<<<\n")

}

func preProcessVariablesIn(config interface{}) {
  switch reflect.ValueOf(config).Kind().String() {
  case "map":
    for k, v := range config.(map[string]interface{}) {
      if reflect.ValueOf(v).Kind().String() == "string" {
        config.(map[string]interface{})[k] = preProcessString(v.(string), config)
      } else {
        preProcessVariablesIn(v)
      }
    }
  case "slice":
    for k, v := range config.([]interface{}) {
      if reflect.ValueOf(v).Kind().String() == "string" {
        config.([]interface{})[k] = preProcessString(v.(string), config)
      } else {
        preProcessVariablesIn(v)
      }
    }
  default: // TODO: REMOVE
    if reflect.ValueOf(config).Kind().String() != "float64" {
      fmt.Printf("UNKNOWN TYPE: [%s]\n", reflect.ValueOf(config).Kind().String())
    }
  }
}

func preProcessRegexp() {
  regex = make(map[string]*regexp.Regexp)
  regex["variable"] = regexp.MustCompile("\\${([a-zA-Z0-9.-]+)}")
}

func preProcessString(str string, config interface{}) string {
  matches := regex["variable"].FindAllStringSubmatch(str, -1)
  if len(matches) == 0 || matches[0][1] == "" {
    return str
  }

  newStr := str

  for _, match := range matches {
    param := strings.Split(match[1], ".")

    // Skip runtime params
    switch param[0] {
    case "PARAMS", "ARGS", "Properties":
      continue
    }

    newStr = strings.Replace(newStr, match[0], "[[[GET CLOSEST TREE VALUE]]]", -1)
  }

  fmt.Printf("\"%s\" > \"%s\"\n", str, newStr)

  return newStr
}

func getClosestTreeValue(tree []string, config map[string]interface{}) interface{} {
  if len(tree) == 1 {
    return config[tree[0]]
  }
  return getClosestTreeValue(tree[1:len(tree)], config[tree[0]].(map[string]interface{}))
}

func getAbsoluteTreeValue(tree []string, config map[string]interface{}) interface{} {
  if len(tree) == 1 {
    return config[tree[0]]
  }
  return getAbsoluteTreeValue(tree[1:len(tree)], config[tree[0]].(map[string]interface{}))
}
