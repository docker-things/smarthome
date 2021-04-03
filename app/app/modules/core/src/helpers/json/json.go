package json

import (
	"encoding/json"
)

func Encode(result interface{}) string {
	jsonData, err := json.Marshal(result)
	if err != nil {
		panic("json.Encode(): " + err.Error())
	}
	return string(jsonData)
}
