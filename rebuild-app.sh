#!/bin/bash

docker-compose build app
docker-compose stop app
docker-compose rm -f app
docker-compose create app
docker-compose start app
