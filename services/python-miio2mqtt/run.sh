#!/bin/bash

while [ true ]; do
  echo "Listen to mqtt"

  while read CMD; do
    # echo "Received: $CMD"

    # Allowed commands
    BIN=$(echo "$CMD" | awk '{print $1}')
    if [ "$BIN" != "miiocli" -a "$BIN" != "mirobo" ]; then
      mosquitto_pub -h "$MQTT_HOST" -t "$MQTT_TOPIC/$IP/rx" -m '{"error":"Command not allowed!"}'
      continue
    fi

    # Extract ip
    IP=$(echo "$CMD" | awk -F'--ip' '{print $2}' | awk '{print $1}')
    if [ "$IP" == "" ]; then
      mosquitto_pub -h "$MQTT_HOST" -t "$MQTT_TOPIC/$IP/rx" -m '{"error":"IP not found in command! [--ip]"}'
      continue
    fi

    # Extract token
    TOKEN=$(echo "$CMD" | awk -F'--token' '{print $2}' | awk '{print $1}')
    if [ "$TOKEN" == "" ]; then
      mosquitto_pub -h "$MQTT_HOST" -t "$MQTT_TOPIC/$IP/rx" -m '{"error":"TOKEN not found in command! [--ip]"}'
      continue
    fi

    # AirPurifier
    if [ "`echo "$CMD" | awk '{print $1,$2}'`" == "miiocli airpurifiermiot" ]; then

      # Run command if it's not requesting status
      if [ "`echo "$CMD" | grep ' status'`" == '' ]; then
        $CMD
      fi

      # Get status
      OUTPUT="`miiocli airpurifiermiot --ip $IP --token $TOKEN status`"

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

      # Report status
      mosquitto_pub -h "$MQTT_HOST" -t "$MQTT_TOPIC/$IP/rx" -m "{\"power\":\"${POWER,,}\",\"aqi\":$AQI,\"average_aqi\":$AVERAGE_AQI,\"humidity\":$HUMIDITY,\"temperature\":$TEMPERATURE,\"fan_level\":$FAN_LEVEL,\"mode\":\"${MODE,,}\",\"led\":\"${LED,,}\",\"led_brightness\":\"${LED_BRIGHTNESS,,}\",\"buzzer\":\"${BUZZER,,}\",\"buzzer_vol\":\"${BUZZER_VOL,,}\",\"child_lock\":\"${CHILD_LOCK,,}\",\"favorite_level\":$FAVORITE_LEVEL,\"filter_life_remaining\":$FILTER_LIFE_REMAINING,\"filter_hours_used\":$FILTER_HOURS_USED,\"use_time\":$USE_TIME,\"purify_volume\":$PURIFY_VOLUME,\"motor_speed\":$MOTOR_SPEED,\"filter_rfid_product_id\":\"${FILTER_RFID_PRODUCT_ID,,}\",\"filter_rfid_tag\":\"${FILTER_RFID_TAG,,}\",\"filter_type\":\"${FILTER_TYPE,,}\",\"error\":\"-\"}"
    else
      mosquitto_pub -h "$MQTT_HOST" -t "$MQTT_TOPIC/$IP/rx" -m '{"error":"Unknown command!"}'
    fi

  done < <(mosquitto_sub -h "$MQTT_HOST" -t "$MQTT_TOPIC/tx")

  sleep 1s
done
