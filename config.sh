#!/bin/bash

# Command used to launch docker
DOCKER_CMD="`which docker`"

# Where to store the backups
BACKUP_PATH=""

# Where to store the communication pipes
FIFO_PATH="/tmp/docker-things/fifo"

# The name of the docker image
PROJECT_NAME="smarthome"

# BUILD ARGS
BUILD_ARGS=(
    --build-arg DOCKER_USERID=$(id -u)
    --build-arg DOCKER_GROUPID=$(id -g)
    --build-arg DOCKER_USERNAME=$(whoami)

    --build-arg DOCKER_TIMEZONE="`timedatectl status | grep "Time zone" | awk '{print $3}'`"

    --build-arg APACHE_SERVER_NAME='smarthome'
    --build-arg APACHE_PORT=1122
    )

# LAUNCH ARGS
RUN_ARGS=(
    -h 'smarthome'

    --memory="1g"
    --cpu-shares=1024

    # TODO: add if things crash awkwardly - remove otherwise @ release
    # --shm-size "512m"

    # TODO: hopefully not needed @ release
    --privileged

    # needed for wakeonlan
    --network="host"

    # persistent changes will be stored in:
    -v $(pwd)/data:/app/data
    -v $(pwd)/db:/var/lib/mysql

    # TODO: remove mounts - used for development
    -v $(pwd)/app/web/Core:/app/web/Core
    -v $(pwd)/app/web/UI:/app/web/UI
    -v $(pwd)/app/web/res:/app/web/res

    # zigbee2mqtt device
    --device=/dev/ttyACM0

    # TODO: remove port forwarding if we stick with host network

    # # apache2 port
    # -p 1122:80

    # # mqtt port
    # -p 1883:1883
    # # mqtt websockets port
    # -p 1884:1884

    # pihole dns - use it if you have a pi-hole docker image running
    # --dns="`sudo docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' pi-hole`"

    --rm
    -d
    )
