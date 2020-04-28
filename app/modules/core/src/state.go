package main

import (
	// "bufio"
	"fmt"
	"time"
	// "io"
	"os"
	// "regexp"

	"database/sql"

	MQTT "github.com/eclipse/paho.mqtt.golang"
	_ "github.com/go-sql-driver/mysql"
)

func setState(client MQTT.Client, msg MQTT.Message) {
	fmt.Printf("%s\n", msg.Payload())

	// if changed - publish
	client.Publish("state/changed", 0, false, msg)
}

func main() {

	// Config MQTT
	opts := MQTT.NewClientOptions().AddBroker("tcp://localhost:1883").SetClientID("state-service")
	opts.SetCleanSession(true)

	// Connect
	client := MQTT.NewClient(opts)
	if token := client.Connect(); token.Wait() && token.Error() != nil {
		panic(token.Error())
	}

	// MQTT Listener - Set state
	if token := client.Subscribe("state/set", 0, setState); token.Wait() && token.Error() != nil {
		fmt.Println(token.Error())
		os.Exit(1)
	}

	testConnection()
}

func tableExists(db *sql.DB, table string) bool {
	rows, err := db.Query(`
        SELECT table_name
        FROM   information_schema.tables
        WHERE  table_schema = 'smarthome' AND table_name = '` + table + `'
        LIMIT  1
        `)
	if err != nil {
		panic(err.Error()) // proper error handling instead of panic in your app
	}

	return rows.Next()
}

func dbExec(db *sql.DB, query string) {
	queryStatement, err := db.Prepare(query)
	if err != nil {
		panic(err.Error())
	}
	defer queryStatement.Close()
	_, err = queryStatement.Exec()
	if err != nil {
		panic(err.Error())
	}
}

func createTables(db *sql.DB) {

	if !tableExists(db, "current") {
		dbExec(db, `
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

		dbExec(db, `
        CREATE UNIQUE INDEX source_name_unique ON current (
            source ASC,
            name ASC
        )`)
	}

	if !tableExists(db, "history") {
		dbExec(db, `
        CREATE TABLE history (
            source    VARCHAR(255) NOT NULL,
            name      VARCHAR(255) NOT NULL,
            value     VARCHAR(255) NOT NULL,
            timestamp INT          NOT NULL
        )`)

		dbExec(db, `
        CREATE INDEX source_name_time_unique ON history (
            source ASC,
            name ASC,
            timestamp DESC
        )`)
	}
}

func connect() *sql.DB {

	// Get connection handle
	db, err := sql.Open("mysql", "root:@tcp(127.0.0.1:3306)/smarthome")
	// for true {
	// db, err := sql.Open("mysql", "root:@localhost/smarthome")
	if err != nil {
		fmt.Println(err.Error())
		time.Sleep(5 * time.Second)
	}
	// break
	// }
	defer db.Close()

	// Establish connection
	for true {
		err = db.Ping()
		if err != nil {
			fmt.Println(err.Error())
			time.Sleep(5 * time.Second)
		}
		break
	}

	return db
}

func getCurrentState(db *sql.DB) {

	// Execute the query
	rows, err := db.Query("SELECT * FROM `current` ORDER BY `source`, `name`")
	if err != nil {
		panic(err.Error()) // proper error handling instead of panic in your app
	}

	// Get column names
	columns, err := rows.Columns()
	if err != nil {
		panic(err.Error()) // proper error handling instead of panic in your app
	}

	// Make a slice for the values
	values := make([]sql.RawBytes, len(columns))

	// rows.Scan wants '[]interface{}' as an argument, so we must copy the
	// references into such a slice
	// See http://code.google.com/p/go-wiki/wiki/InterfaceSlice for details
	scanArgs := make([]interface{}, len(values))
	for i := range values {
		scanArgs[i] = &values[i]
	}

	// Fetch rows
	for rows.Next() {
		// get RawBytes from data
		err = rows.Scan(scanArgs...)
		if err != nil {
			panic(err.Error()) // proper error handling instead of panic in your app
		}

		// Now do something with the data.
		// Here we just print each column as a string.
		var value string
		for i, col := range values {
			// Here we can check if the value is nil (NULL value)
			if col == nil {
				value = "NULL"
			} else {
				value = string(col)
			}
			fmt.Println(columns[i], ": ", value)
		}
		fmt.Println("-----------------------------------")
	}
	if err = rows.Err(); err != nil {
		panic(err.Error()) // proper error handling instead of panic in your app
	}
}

func testConnection() {
	db := connect()
	createTables(db)

	getCurrentState(db)

	// // Prepare statement for inserting data
	// stmtIns, err := db.Prepare("INSERT INTO squareNum VALUES( ?, ? )") // ? = placeholder
	// if err != nil {
	//     panic(err.Error())
	// }
	// defer stmtIns.Close()

	// // Insert square numbers for 0-24 in the database
	// for i := 0; i < 25; i++ {
	//     _, err = stmtIns.Exec(i, (i * i)) // Insert tuples (i, i^2)
	//     if err != nil {
	//         panic(err.Error())
	//     }
	// }

}