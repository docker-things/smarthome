#!/bin/bash

# Normal usage
IP="$1"
TOKEN="$2"
ACTION="$3"
PARAM="$4"

# Command base
CMD="miiocli airpurifiermiot --ip $IP --token $TOKEN"

# Last cleaning details
if [ "$ACTION" != "status" ]; then
	$CMD $ACTION $PARAM
	exit
fi

OUTPUT="`$CMD status`"

POWER="`echo -e "$OUTPUT" | grep "^Power: " | sed -e 's/^Power: //g'`"
AQI="`echo -e "$OUTPUT" | grep "^AQI: " | sed -e 's/^AQI: //g' -e 's/ μg\\/m³//g'`"
AVERAGE_AQI="`echo -e "$OUTPUT" | grep "^Average AQI: " | sed -e 's/^Average AQI: //g' -e 's/ μg\\/m³//g'`"
HUMIDITY="`echo -e "$OUTPUT" | grep "^Humidity: " | sed -e 's/^Humidity: //g' -e 's/ \%//g'`"
TEMPERATURE="`echo -e "$OUTPUT" | grep "^Temperature: " | sed -e 's/^Temperature: //g' -e 's/ °C//g'`"
FAN_LEVEL="`echo -e "$OUTPUT" | grep "^Fan Level: " | sed -e 's/^Fan Level: //g'`"
MODE="`echo -e "$OUTPUT" | grep "^Mode: " | sed -e 's/^Mode: //g' -e 's/OperationMode\.//g'`"
LED="`echo -e "$OUTPUT" | grep "^LED: " | sed -e 's/^LED: //g'`"
LED_BRIGHTNESS="`echo -e "$OUTPUT" | grep "^LED brightness: " | sed -e 's/^LED brightness: //g' -e 's/LedBrightness\.//g'`"
BUZZER="`echo -e "$OUTPUT" | grep "^Buzzer: " | sed -e 's/^Buzzer: //g'`"
BUZZER_VOL="`echo -e "$OUTPUT" | grep "^Buzzer vol.: " | sed -e 's/^Buzzer vol.: //g'`"
CHILD_LOCK="`echo -e "$OUTPUT" | grep "^Child lock: " | sed -e 's/^Child lock: //g'`"
FAVORITE_LEVEL="`echo -e "$OUTPUT" | grep "^Favorite level: " | sed -e 's/^Favorite level: //g'`"
FILTER_LIFE_REMAINING="`echo -e "$OUTPUT" | grep "^Filter life remaining: " | sed -e 's/^Filter life remaining: //g' -e 's/ \%//g'`"
FILTER_HOURS_USED="`echo -e "$OUTPUT" | grep "^Filter hours used: " | sed -e 's/^Filter hours used: //g'`"
USE_TIME="`echo -e "$OUTPUT" | grep "^Use time: " | sed -e 's/^Use time: //g' -e 's/ s//g'`"
PURIFY_VOLUME="`echo -e "$OUTPUT" | grep "^Purify volume: " | sed -e 's/^Purify volume: //g' -e 's/ m³//g'`"
MOTOR_SPEED="`echo -e "$OUTPUT" | grep "^Motor speed: " | sed -e 's/^Motor speed: //g' -e 's/ rpm//g'`"
FILTER_RFID_PRODUCT_ID="`echo -e "$OUTPUT" | grep "^Filter RFID product id: " | sed -e 's/^Filter RFID product id: //g'`"
FILTER_RFID_TAG="`echo -e "$OUTPUT" | grep "^Filter RFID tag: " | sed -e 's/^Filter RFID tag: //g'`"
FILTER_TYPE="`echo -e "$OUTPUT" | grep "^Filter type: " | sed -e 's/^Filter type: //g' -e 's/FilterType\.//g'`"

printf "{"
printf "\"power\":\"${POWER,,}\","
printf "\"aqi\":$AQI,"
printf "\"average_aqi\":$AVERAGE_AQI,"
printf "\"humidity\":$HUMIDITY,"
printf "\"temperature\":$TEMPERATURE,"
printf "\"fan_level\":$FAN_LEVEL,"
printf "\"mode\":\"${MODE,,}\","
printf "\"led\":\"${LED,,}\","
printf "\"led_brightness\":\"${LED_BRIGHTNESS,,}\","
printf "\"buzzer\":\"${BUZZER,,}\","
printf "\"buzzer_vol\":\"${BUZZER_VOL,,}\","
printf "\"child_lock\":\"${CHILD_LOCK,,}\","
printf "\"favorite_level\":$FAVORITE_LEVEL,"
printf "\"filter_life_remaining\":$FILTER_LIFE_REMAINING,"
printf "\"filter_hours_used\":$FILTER_HOURS_USED,"
printf "\"use_time\":$USE_TIME,"
printf "\"purify_volume\":$PURIFY_VOLUME,"
printf "\"motor_speed\":$MOTOR_SPEED,"
printf "\"filter_rfid_product_id\":\"${FILTER_RFID_PRODUCT_ID,,}\","
printf "\"filter_rfid_tag\":\"${FILTER_RFID_TAG,,}\","
printf "\"filter_type\":\"${FILTER_TYPE,,}\""
printf "}\n"