package deepcopy

import (
  "encoding/json"
  "errors"
)

// func Map(src map[string]interface{}, dest map[string]interface{}) {
//   for key, value := range src {
//     switch src[key].(type) {
//     case map[string]interface{}:
//       dest[key] = map[string]interface{}{}
//       Map(src[key].(map[string]interface{}), dest[key].(map[string]interface{}))
//     default:
//       dest[key] = value
//     }
//   }
// }

func Map(src map[string]interface{}, dest map[string]interface{}) error {
  if src == nil {
    return errors.New("src is nil. You cannot read from a nil map")
  }
  if dest == nil {
    return errors.New("dest is nil. You cannot insert to a nil map")
  }
  jsonStr, err := json.Marshal(src)
  if err != nil {
    return err
  }
  err = json.Unmarshal(jsonStr, &dest)
  if err != nil {
    return err
  }
  return nil
}
