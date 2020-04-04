#!/bin/bash

# Normal usage
IP="$1"
TOKEN="$2"
ACTION="$3"
PARAM="$4"
PARAM="$5"

# Command base
CMD="mirobo --id-file /tmp/python-mirobo.seq --ip $IP --token $TOKEN"

# TOGGLE (clean/home)
if [ "$ACTION" == "toggle" ]; then
	STATUS="`$CMD status | grep 'State: ' | awk '{print $2}'`"
	if [ "$STATUS" == "Cleaning" ]; then
		ACTION="pause"
	else
		ACTION="start"
	fi
fi

# Find
if [ "$ACTION" == "find" ]; then
	OUTPUT="`$CMD find`"
	if [ "echo -e \"$OUTPUT\" | grep \"\['ok'\]\"" != "" ]; then
		echo "{\"status\":\"ok\"}"
		exit
	fi
	echo "{\"status\":\"$OUTPUT\"}"
	exit
fi

# Go to
if [ "$ACTION" == "goto" ]; then
	OUTPUT="`$CMD goto $PARAM $PARAM2`"
	if [ "echo -e \"$OUTPUT\" | grep \"\['ok'\]\"" != "" ]; then
		echo "{\"status\":\"ok\"}"
		exit
	fi
	echo "{\"status\":\"$OUTPUT\"}"
	exit
fi

# Zone cleanup
if [ "$ACTION" == "zoned-cleanup" ]; then
	OUTPUT="`miiocli vacuum --id-file /tmp/python-mirobo.seq --ip "$IP" --token "$TOKEN" zoned_clean "$PARAM"`"
	if [ "echo -e \"$OUTPUT\" | grep 'Running' | grep \"\['ok'\]\"" != "" ]; then
		echo "{\"status\":\"ok\"}"
		exit
	fi
	echo "{\"status\":\"$OUTPUT\"}"
	exit
fi

# Last cleaning details
if [ "$ACTION" == "cleaning-history" ]; then
	OUTPUT="`$CMD cleaning-history | head -n 6`"
	OUTPUT_LAST="`echo -e "$OUTPUT" | grep "Clean #0: "`"

	TOTAL_CLEAN_COUNT="`echo -e "$OUTPUT" | grep "Total clean count: " | awk '{print $4}'`"
	if [ "$TOTAL_CLEAN_COUNT" == "" ]; then
		printf "{\"total_clean_count\":0}\n"
		exit
	fi

	TOTAL_CLEANED_FOR="`echo -e "$OUTPUT" | grep "Cleaned for: " | sed -e 's/^Cleaned for: //g'`"
	LAST_START_DATE="`echo -e "$OUTPUT_LAST" | awk '{print $3}'`"
	LAST_START_TIME="`echo -e "$OUTPUT_LAST" | awk '{print $4}' | awk -F'-' '{print $1}'`"
	LAST_END_DATE="`echo -e "$OUTPUT_LAST" | awk '{print $4}' | sed -e 's/^'$LAST_START_TIME'-//g'`"
	LAST_END_TIME="`echo -e "$OUTPUT_LAST" | awk '{print $5}'`"
	LAST_COMPLETE="`echo -e "$OUTPUT_LAST" | awk '{print $7}' | sed -e 's/,//g'`"
	LAST_ERROR="`echo -e "$OUTPUT_LAST" | awk '{print $9}' | sed -e 's/,//g'`"
	LAST_AREA_CLEANED="`echo -e "$OUTPUT" | grep "Area cleaned: " | awk '{print $3}'`"
	LAST_DURATION="`echo -e "$OUTPUT" | grep "Duration: " | awk '{print $2}' | sed -e 's/(//g' -e 's/)//g'`"

	printf "{"
	printf "\"total_clean_count\":$TOTAL_CLEAN_COUNT,"
	printf "\"total_cleaned_for\":\"$TOTAL_CLEANED_FOR\","
	printf "\"last_start_date\":\"$LAST_START_DATE\","
	printf "\"last_start_time\":\"$LAST_START_TIME\","
	printf "\"last_end_date\":\"$LAST_END_DATE\","
	printf "\"last_end_time\":\"$LAST_END_TIME\","
	printf "\"last_complete\":\"$LAST_COMPLETE\","
	printf "\"last_error\":\"$LAST_ERROR\","
	printf "\"last_area_cleaned\":\"$LAST_AREA_CLEANED\","
	printf "\"last_duration\":\"$LAST_DURATION\""
	printf "}\n"
	exit
fi

# Take action
printf "{"
$CMD $ACTION $PARAM $PARAM2 | \
	sed \
		-e 's/: /": "/g' \
		-e 's/$/"/g' \
		-e 's/^/"/g' | \
	tr '\n' ',' | \
	sed \
		-e 's/",$/"/g' \
		-e 's/%//g' \
		-e 's/m²//g' \
		-e 's/[ \t]\+/ /g' \
		-e 's/ "/"/g' \
		-e 's/" /"/g' \
		-e 's/\([a-zA-Z]\) \([a-zA-Z]\)/\1_\2/g' \
		-e 's/"\[\x27ok\x27\]"/"ok"/g'
printf "}\n"
