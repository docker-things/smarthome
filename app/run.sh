#!/bin/ash

DEFAULT_CONFIG_DIR="/app/config"

DATA_DIR="/app/data"
CONFIG_DIR="$DATA_DIR/config"

WEB_DIR="/app/web"

# Data dir
if [ ! -d "$DATA_DIR" ]; then
    echo " > Creating data dir: $DATA_DIR"
    mkdir -p "$DATA_DIR"
fi

# Config dir
if [ ! -d "$CONFIG_DIR" ]; then
    echo " > Creating config dir: $CONFIG_DIR"
    mkdir -p "$CONFIG_DIR"
fi

# Config contents
if [ "`ls -1 "$CONFIG_DIR" | wc -l`" == "0" ]; then
    echo " > Populating config dir from $DEFAULT_CONFIG_DIR to $CONFIG_DIR"
    rmdir "$CONFIG_DIR"
    cp -R "$DEFAULT_CONFIG_DIR" "$CONFIG_DIR"

# TODO: Remove in prod - sync modules config from default
else
    echo "[DEV] > Setting default modules config from $DEFAULT_CONFIG_DIR/Module to $CONFIG_DIR/Module"
    rsync --delete-after --update -rvz "$DEFAULT_CONFIG_DIR/Module/" "$CONFIG_DIR/Module"
fi

# Permissions
echo " > Make sure we've got permissions"
chown $DOCKER_USERNAME:$DOCKER_GROUPID -R \
    "$DATA_DIR"
chmod 775 -R \
    "$DATA_DIR" \
    "$WEB_DIR"

# Set hostname as the screen daemon name
SCREEN_NAME="`hostname`"

# Services to launch
if [ "`cat /app/data/.env`" == "prod" ]; then
    SERVICES='
        mosquitto
        mariadb
        apache
        mqtt-listener
        full-state-provider
        state-setter
        function-run-listener
        zigbee2mqtt
        cec-client-mqtt-bridge
        evdev2mqtt
        broadlink2mqtt
        cron
        core/mqtt-forward
        '
        # bluetooth-scan
else
    SERVICES='
        mosquitto
        mariadb
        core/config
        core/state
        '
fi

# Launch daemon
echo " > Launch screen daemon"
screen -dmS "$SCREEN_NAME" -t "smarthome"

# Keep the process id
SCREEN_PID="`ps ax | grep screen | grep -v grep | awk '{print $1}'`"

# Launch services
i=0
for service in $SERVICES ; do
    i=$((i+1))
    echo "   > Launch $service screen [$i]"
    screen -S "$SCREEN_NAME" -X screen -t "$service"
    screen -S "$SCREEN_NAME" -p $i -X stuff $'ash services/'${service}$'.service;exit\r'
done

# Close first unused screen
echo " > Close the first unused screen"
screen -S "$SCREEN_NAME" -p 0 -X stuff $'exit\r'

# Safe stop when receiving the stop signal
safed=0
function safety() {
	if [ $safed -eq 0 ]; then
		safed=1
		echo -e "\nAttempt safe stop...\n"
		kill $SCREEN_PID &
        while [ "`ps ax | grep screen | grep -v grep`" != "" ]; do
            sleep 1s
        done
	fi
}
trap safety INT TERM EXIT

# Keep self alive while the screen daemon is alive
echo " > Will stay alive for the screen daemon"
while [ "`ps ax | grep screen | grep -v grep`" != "" ]; do
    sleep 300s
done
