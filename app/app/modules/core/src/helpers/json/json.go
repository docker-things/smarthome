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

func Unmarshal(data []byte, v any) error {
	return json.Unmarshal(data, &v)
}
