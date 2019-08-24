#!/bin/bash

while [ 1 == 1 ]; do
	timeout -t 2 -s SIGINT hcitool lescan | ash hcitool-lescan-to-mqtt.sh
	sleep 30
done
