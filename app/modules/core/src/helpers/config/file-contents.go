package config

import (
  // "fmt"
  // "path"
  "encoding/json"
  "io/ioutil"
  // "path/filepath"
  // "strings"

  "github.com/ghodss/yaml"
)

func loadFileIntoConfig(absolutePath string, config map[string]interface{}) {

  // TODO: REMOVE: FILTER BY DIR
  // filenameWithExt := filepath.Base(absolutePath)
  // filedir := strings.TrimSuffix(absolutePath, filenameWithExt)
  // if filedir != "/app/data/config/Module/Device/CEC/" {
  //   return
  // }

  // TODO: REMOVE: FILTER BY FILE
  // if absolutePath != "/app/data/config/Module/Device/WakeOnLan/Functions.yaml" &&
  //   absolutePath != "/app/data/config/Module/Device/WakeOnLan/Properties.yaml" &&
  //   absolutePath != "/app/data/config/Base.yaml" {
  //   return
  // }

  // fmt.Printf("READING: %s\n", absolutePath)

  // // Get config type (file name)
  // filenameWithExt := filepath.Base(absolutePath)
  // filename := strings.TrimSuffix(filenameWithExt, path.Ext(filenameWithExt))

  // Get file contents
  data, err := ioutil.ReadFile(absolutePath)
  if err != nil {
    panic(err)
  }

  // Convert YAML to JSON
  jsonDoc, err := yaml.YAMLToJSON(data)
  if err != nil {
    panic(err.Error())
  }

  // Parse JSON
  var fileContents map[string]interface{}
  err = json.Unmarshal(jsonDoc, &fileContents)
  if err != nil {
    panic(err.Error())
  }

  tree := getConfigTreeFromPath(absolutePath)

  setContents(fileContents, tree, config)
}

func setContents(fileContents map[string]interface{}, tree []string, config map[string]interface{}) {
  step := tree[0]
  if _, ok := config[step]; !ok {
    config[step] = make(map[string]interface{})
  }
  if len(tree) != 1 {
    setContents(fileContents, tree[1:], config[step].(map[string]interface{}))
  } else {
    config[step] = fileContents
  }
}
