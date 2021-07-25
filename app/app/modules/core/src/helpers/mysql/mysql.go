package mysql

import (
  "database/sql"
  // "database/sql/driver"
  "fmt"
  "strings"
  "time"

  _ "github.com/go-sql-driver/mysql"
)

const mysqlDB = "smarthome"
const mysqlHost = "root:@tcp(db:3306)"

var mysqlConnection = strings.Join([]string{mysqlHost, mysqlDB}, "/")

var db *sql.DB

func Connect() {
  var connection *sql.DB
  var err error
  for true {
    connection, err = sql.Open("mysql", mysqlConnection)
    if err != nil {
      fmt.Println("mysql.Connect(): " + err.Error())
      fmt.Println("mysql.Connect(): Waiting 5 seconds...")
      time.Sleep(5 * time.Second)
    } else {
      break
    }
  }

  // Establish connection
  for true {
    err = connection.Ping()
    if err != nil {
      fmt.Println("mysql.Connect(): " + err.Error())
      fmt.Println("mysql.Connect(): Waiting 5 seconds...")
      time.Sleep(5 * time.Second)
    }
    break
  }

  db = connection

  createTables()
  go optimizeEvery24Hours()
}

func Disconnect() {
  db.Close()
}

func GetCurrentState() []StateType {
  return getStateRows(`
    SELECT
      source,
      name,
      value,
      prevValue,
      tmpValue,
      timestamp,
      tmpTimes,
      tmpTimestamp
    FROM current
    ORDER BY
      source,
      name
    `)
}

func SetState(data StateType) {
  queryStatement, err := db.Prepare(`
      REPLACE INTO current (
        source,
        name,
        value,
        prevValue,
        timestamp,
        tmpValue,
        tmpTimes,
        tmpTimestamp
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      `)
  if err != nil {
    panic("mysql.SetState(): Prepare: " + err.Error())
  }
  defer queryStatement.Close()
  _, err = queryStatement.Exec(
    data.Source,
    data.Name,
    data.Value,
    data.PrevValue,
    data.Timestamp,
    data.TmpValue,
    data.TmpTimes,
    data.TmpTimestamp,
  )
  if err != nil {
    panic("mysql.SetState(): Exec: " + err.Error())
  }
}

func dbExec(query string) {
  // fmt.Println("mysql.dbExec(): " + query)
  _, err := db.Exec(query)
  if err != nil {
    panic("mysql.dbExec(): Exec: " + err.Error())
  }
}

func getStateRows(query string) []StateType {
  rows, err := db.Query(query)
  if err != nil {
    panic("mysql.getRows(): Query: " + err.Error())
  }
  defer rows.Close()

  data := make([]StateType, 0)
  for rows.Next() {
    entry := StateType{}
    rows.Scan(
      &entry.Source,
      &entry.Name,
      &entry.Value,
      &entry.PrevValue,
      &entry.TmpValue,
      &entry.Timestamp,
      &entry.TmpTimes,
      &entry.TmpTimestamp,
    )
    data = append(data, entry)
  }

  return data
}

/*func getUnknownRows(query string) []map[string]interface{} {
  // Query
  rows, err := db.Query(query)
  if err != nil {
    panic("mysql.getRows(): Query: " + err.Error())
  }

  // Make sure result is closed
  defer rows.Close()

  // Fetch columns
  columns, err := rows.Columns()
  if err != nil {
    panic("mysql.getRows(): Columns: " + err.Error())
  }
  columnsCount := len(columns)

  // Fetch rows
  tableData := make([]map[string]interface{}, 0)
  values := make([]interface{}, columnsCount)
  valuePtrs := make([]interface{}, columnsCount)
  for rows.Next() {
    for i := 0; i < columnsCount; i++ {
      valuePtrs[i] = &values[i]
    }
    rows.Scan(valuePtrs...)
    entry := make(map[string]interface{})
    for i, col := range columns {
      var v interface{}
      val := values[i]
      b, ok := val.([]byte)
      if ok {
        v = string(b)
      } else {
        v = val
      }
      entry[col] = v
    }
    tableData = append(tableData, entry)
  }

  return tableData
}*/

func tableExists(table string) bool {
  // fmt.Println("mysql.tableExists(): " + table)
  rows, err := db.Query(`
    SELECT table_name
    FROM   information_schema.tables
    WHERE  table_schema = '` + mysqlDB + `' AND table_name = '` + table + `'
    LIMIT  1
    `)
  if err != nil {
    panic("mysql.tableExists(): " + err.Error())
  }
  return rows.Next()
}

func ValidStateRow(row map[string]interface{}) bool {
  return MapHasAllKeys(row, []string{
    "source",
    "name",
    "value",
    "prevValue",
    "timestamp",
    "tmpValue",
    "tmpTimes",
    "tmpTimestamp",
  })
}

func ValidHistoryRow(row map[string]interface{}) bool {
  return MapHasAllKeys(row, []string{
    "source",
    "name",
    "value",
    "timestamp",
  })
}

func MapHasAllKeys(data map[string]interface{}, keys []string) bool {
  for _, key := range keys {
    if _, ok := data[key]; !ok {
      return false
    }
  }
  return true
}

func createTables() {
  if !tableExists("current") {
    dbExec(`
      CREATE TABLE current (
        source       VARCHAR(255) NOT NULL,
        name         VARCHAR(255) NOT NULL,
        value        VARCHAR(255) NOT NULL,
        prevValue    VARCHAR(255) NOT NULL,
        timestamp    INT          NOT NULL,
        tmpValue     VARCHAR(255) NOT NULL,
        tmpTimes     INT          NOT NULL,
        tmpTimestamp INT          NOT NULL
      )`)

    dbExec(`
      CREATE UNIQUE INDEX source_name_unique ON current (
        source ASC,
        name ASC
      )`)
  }

  if !tableExists("history") {
    dbExec(`
      CREATE TABLE history (
        source    VARCHAR(255) NOT NULL,
        name      VARCHAR(255) NOT NULL,
        value     VARCHAR(255) NOT NULL,
        timestamp INT          NOT NULL
      )`)

    dbExec(`
      CREATE INDEX source_name_time_unique ON history (
        source ASC,
        name ASC,
        timestamp DESC
      )`)
  }
}

func optimize() {
  fmt.Println("DB Optimization started")
  dbExec("OPTIMIZE TABLE `current`")
  dbExec("OPTIMIZE TABLE `history`")
  dbExec("PURGE BINARY LOGS BEFORE DATE(NOW() - INTERVAL 1 DAY) + INTERVAL 0 SECOND")
  fmt.Println("DB Optimization ended")
}

func optimizeEvery24Hours() {
  for {
    optimize()
    time.Sleep(time.Duration(24) * time.Hour)
  }
}
