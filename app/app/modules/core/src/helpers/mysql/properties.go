package mysql

type StateType struct {
  Source       string // `default:""`
  Name         string // `default:""`
  Value        string // `default:""`
  PrevValue    string // `default:""`
  Timestamp    int64  // `default:0`
  TmpValue     string // `default:""`
  TmpTimes     int16  // `default:0`
  TmpTimestamp int64  // `default:0`
}

type HistoryType struct {
  Source    string
  Name      string
  Value     string
  Timestamp int64
}

func StateFromMap(data map[string]interface{}) StateType {
  
  return StateType{
    Source:       data["source"].(string),
    Name:         data["name"].(string),
    Value:        data["value"].(string),
    PrevValue:    data["prevValue"].(string),
    Timestamp:    data["timestamp"].(int64),
    TmpValue:     data["tmpValue"].(string),
    TmpTimes:     data["tmpTimes"].(int16),
    TmpTimestamp: data["tmpTimestamp"].(int64),
  }
}
