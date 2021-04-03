package config

import (
	"sync"
)

// CONFIG
type configType struct {
	value map[string]interface{}
	mutex sync.Mutex
}

var config configType

// CONFIG PATH
var configPath string

// On change callback
type onChangeCallbackType func(string)

var onChangeCallback onChangeCallbackType
