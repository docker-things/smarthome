package mysql

import (
  "database/sql"
  "encoding/json"
  "fmt"
  _ "github.com/go-sql-driver/mysql"
  "strings"
  "time"
)

const mysqlDB = "smarthome"
const mysqlHost = "root:@tcp(127.0.0.1:3306)"

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
    }
    break
  }

  db = connection

  createTables()
}

func Disconnect() {
  db.Close()
}

func dbExec(query string) {
  fmt.Println("mysql.dbExec(): " + query)
  queryStatement, err := db.Prepare(query)
  if err != nil {
    panic("mysql.dbExec(): " + err.Error())
  }
  defer queryStatement.Close()
  _, err = queryStatement.Exec()
  if err != nil {
    panic("mysql.dbExec(): " + err.Error())
  }
}

func getRows(query string) []map[string]interface{} {
  // Query
  rows, err := db.Query(query)
  if err != nil {
    panic("mysql.getRows(): " + err.Error())
  }

  // Make sure result is closed
  defer rows.Close()

  // Fetch columns
  columns, err := rows.Columns()
  if err != nil {
    panic("mysql.getRows(): " + err.Error())
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
}

func ResultToJSON(result []map[string]interface{}) string {
  jsonData, err := json.Marshal(result)
  if err != nil {
    panic("mysql.ResultToJSON(): " + err.Error())
  }
  return string(jsonData)
}

func tableExists(table string) bool {
  fmt.Println("mysql.tableExists(): " + table)
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

func GetCurrentState() []map[string]interface{} {
  return getRows("SELECT * FROM `current` ORDER BY `source`, `name`")
}
