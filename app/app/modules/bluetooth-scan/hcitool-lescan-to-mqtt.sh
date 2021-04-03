#!/bin/bash

while read LINE; do
	if [ "$LINE" == "LE Scan ..." ]; then
		continue
	fi

	# GET DATA FROM LINE
	MAC="`echo "$LINE" | cut -d' ' -f1`"
	NAME="`echo "$LINE" | cut -d' ' -f2-`"
	LAST_SEEN="`date +"%s"`"

	# PRETTIFY NAME
	if [ "$NAME" == "(unknown)" ]; then
		NAME="unknown"
	fi

	# BUILD JSON
	JSON="{\"mac\":\"$MAC\",\"name\":\"$NAME\",\"last_seen\":\"$LAST_SEEN\"}"

	# PUBLISH
	mosquitto_pub -h mqtt -t "bluetooth/device/$MAC" -m "$JSON"
done < /dev/stdin
