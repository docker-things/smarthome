package main

import (
	"fmt"
	"os"
	"os/signal"
	"strings"
	"syscall"

	mqtt "./helpers/mqtt"
	db "./helpers/mysql"
)

const serviceName = "core/state"

// IN
var topicSet = strings.Join([]string{serviceName, "set"}, "/")
var topicGetFullstate = strings.Join([]string{serviceName, "get-full-state"}, "/")

// OUT
var topicChange = strings.Join([]string{serviceName, "change"}, "/")
var topicProvideFullState = strings.Join([]string{serviceName, "full-state"}, "/")

func main() {
	c := make(chan os.Signal, 1)
	signal.Notify(c, os.Interrupt, syscall.SIGTERM)

	mqtt.Connect(serviceName)
	db.Connect()
	defer db.Disconnect()

	// Set default publish topic
	// publishTopic = strings.Join([]string{serviceName, "read"}, "/")

	mqtt.Subscribe(topicSet, func(msg string) {
		fmt.Printf("%s: %s\n", topicSet, msg)
		// mqtt.PublishOn(topicChange, msg)
	})

	mqtt.Subscribe(topicGetFullstate, func(msg string) {
		fmt.Printf("%s: %s\n", topicGetFullstate, msg)
		result := db.GetCurrentState()
		json := db.ResultToJSON(result)
		mqtt.PublishOn(topicProvideFullState, json)
	})

	<-c
}

/*func testConnection() {

	// Prepare statement for inserting data
	stmtIns, err := db.Prepare("INSERT INTO squareNum VALUES( ?, ? )") // ? = placeholder
	if err != nil {
		panic(err.Error())
	}
	defer stmtIns.Close()

	// Insert square numbers for 0-24 in the database
	for i := 0; i < 25; i++ {
		_, err = stmtIns.Exec(i, (i * i)) // Insert tuples (i, i^2)
		if err != nil {
			panic(err.Error())
		}
	}
}*/
