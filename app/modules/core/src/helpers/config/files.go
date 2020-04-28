package config

import (
  "strings"
  "time"

  "io/ioutil"
  "log"
  "path/filepath"
  "sync"
)

// CONFIG FILES
type configFileValueType map[string]time.Time
type configFilesType struct {
  value configFileValueType
  mutex sync.Mutex
}

var configFiles configFilesType

func getConfigFilesIn(path string) configFileValueType {
  paths := make(configFileValueType)

  // Crash on error
  files, err := ioutil.ReadDir(path)
  if err != nil {
    log.Fatal(err)
  }

  for _, f := range files {

    // If it's a dir go deeper in the tree
    if f.IsDir() {

      // Get config files
      subpaths := getConfigFilesIn(strings.Join([]string{path, f.Name()}, "/"))

      // Merge them here
      for k, v := range subpaths {
        paths[k] = v
      }

      // If it's a config file
    } else if filepath.Ext(f.Name()) == ".yaml" {

      // Build it's full path
      filePath := strings.Join([]string{path, f.Name()}, "/")

      // Append it
      paths[filePath] = f.ModTime()
    }
  }

  return paths
}

func thereAreChanges(paths configFileValueType) bool {
  configFiles.mutex.Lock()
  defer configFiles.mutex.Unlock()

  // Check if the number of files has changed
  if len(paths) != len(configFiles.value) {
    return true
  }

  // Check if we have the same files and if there's' any changed file
  for newKey, newVal := range paths {
    if oldVal, ok := configFiles.value[newKey]; ok {
      if newVal != oldVal {
        // This file is changed
        return true
      }
    } else {
      // This file is new
      return true
    }
  }

  return false
}

func setNewConfigFiles(newConfigFiles configFileValueType) {
  configFiles.mutex.Lock()
  configFiles.value = newConfigFiles
  configFiles.mutex.Unlock()
}
