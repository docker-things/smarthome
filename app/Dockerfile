FROM alpine:3.13.3
MAINTAINER Gabriel Ionescu <gabi.ionescu+docker@protonmail.com>

# CREATE USER
RUN echo -e "\n > CREATE DOCKER USER: 1000\n" \
 && addgroup -g 1000 1000 \
 && adduser -D -u 1000 -G 1000 1000


# INSTALL ESSENTIAL PACKAGES
RUN echo -e "\n > INSTALL DEPENDENCIES\n" \
 && apk add --no-cache \
    bc \
    curl \
    iputils \
    lm_sensors \
    rsync \
    screen \
    sudo \
    tzdata \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# SET TIMEZONE
ARG DOCKER_TIMEZONE
ENV TZ "$DOCKER_TIMEZONE"
RUN echo -e "\n > SET TIMEZONE: $TZ\n" \
 && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
 && echo $TZ > /etc/timezone


# INSTALL PHP
RUN echo -e "\n > INSTALL PHP\n" \
 && apk add --no-cache \
    php7 \
    php7-curl \
    php7-json \
    php7-mysqli \
    php7-session \
    php7-yaml \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# INSTALL APACHE
RUN echo -e "\n > INSTALL APACHE\n" \
 && apk add --no-cache \
    apache2 \
    php7-apache2 \
 && sed -i "s/#ServerName www.example.com:80/ServerName smarthome/" /etc/apache2/httpd.conf \
 && sed -i 's/\/var\/www\/localhost\/htdocs/\/app\/web/g' /etc/apache2/httpd.conf \
 && sed -i "s/    AllowOverride None/    AllowOverride All/" /etc/apache2/httpd.conf \
 && sed -i "s/#LoadModule rewrite_module/LoadModule rewrite_module/" /etc/apache2/httpd.conf \
 && mkdir -p /run/apache2 \
 \
 && echo -e "\n > ADD APACHE TO THE USER GROUP: 1000\n" \
 && sed -i "s/1000:x:1000:1000/1000:x:1000:1000,apache/" /etc/group \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# PYTHON-MIIO
RUN echo -e "\n > INSTALL PYTHON-MIIO\n" \
 && apk add --no-cache \
    python3 \
    py3-pip \
    openssl \
 && apk add --no-cache --virtual build-dependencies \
    gcc \
    rust \
    cargo \
    python3-dev \
    musl-dev \
    libffi-dev \
    linux-headers \
    openssl-dev \
 \
 && pip3 install python-miio \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /tmp/* \
    /var/tmp/*

# INSTALL CEC SUPPORT
RUN echo -e "\n > INSTALL CEC SUPPORT\n" \
 && apk add --no-cache \
    libcec \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# INSTALL BLUETOOTH
RUN echo -e "\n > INSTALL HCITOOL\n" \
 && apk add --no-cache \
    bluez-deprecated \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# RUN echo -e "\n > INSTALL PRESENCE DETECTION\n" \
#  && apk add --no-cache --virtual=build-dependencies \
#     git \
#  \
#  && git clone https://github.com/andrewjfreyer/presence.git /app/modules/presence \
#  \
#  && echo -e "\n > CLEANUP\n" \
#  && apk del --purge \
#     build-dependencies \
#  && rm -rf \
#     /app/modules/presence/.git \
#     # /tmp/* \
#     /var/tmp/*


# # INSTALL GO COMPILED MODULES:
# # - CEC-CLIENT TO MQTT BRIDGE
# # - EVDEV2MQTT
# # - YEELIGHT
# # - WAKEONLAN
# COPY app/modules/cec-client-mqtt-bridge /app/modules/cec-client-mqtt-bridge
# COPY app/modules/evdev2mqtt /app/modules/evdev2mqtt
# COPY app/modules/yeelight /app/modules/yeelight
# RUN echo -e "\n > INSTALL GO BUILD ENV\n" \
#  && apk add --no-cache --virtual=build-dependencies \
#     go \
#     git \
#     musl-dev \
#     linux-headers \
#     \
#  && echo -e "\n > BUILD MODULE: CEC-CLIENT TO MQTT BRIDGE\n" \
#  && cd /app/modules/cec-client-mqtt-bridge \
#  && go get github.com/eclipse/paho.mqtt.golang \
#  && go build -ldflags "-s -w" src/cec-client-mqtt-bridge.go \
#  \
#  && echo -e "\n > BUILD MODULE: EVDEV2MQTT\n" \
#  && cd /app/modules/evdev2mqtt \
#  && go get github.com/eclipse/paho.mqtt.golang \
#  && go get github.com/gvalkov/golang-evdev \
#  && go build -ldflags "-s -w" src/evdev2mqtt.go \
#  \
#  && echo -e "\n > BUILD MODULE: YEELIGHT\n" \
#  && cd /app/modules/yeelight \
#  && go build -ldflags "-s -w" src/yeelight.go \
#  \
#  && echo -e "\n > BUILD MODULE: WAKEONLAN\n" \
#  && go get github.com/blchinezu/go-wol/cmd/wol \
#  && mv /root/go/bin/wol /app/modules/wakeonlan \
#  \
#  && echo -e "\n > CLEANUP\n" \
#  && apk del --purge \
#     build-dependencies \
#  && rm -rf \
#     /app/modules/cec-client-mqtt-bridge/src \
#     /app/modules/evdev2mqtt/src \
#     /app/modules/yeelight/src \
#     /root/.cache \
#     /root/go \
#     /tmp/* \
#     /var/tmp/*


# INSTALL MOSQUITTO
RUN echo -e "\n > INSTALL MOSQUITTO\n" \
 && apk add --no-cache \
 mosquitto-clients \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# INSTALL BROADLINK
COPY app/modules/broadlink2mqtt /app/modules/broadlink2mqtt
RUN echo -e "\n > INSTALL BROADLINK IN $BROADLINK_PATH\n" \
 && apk add --no-cache --virtual=build-dependencies \
    git \
    py3-pip \
 \
 && pip3 install paho-mqtt \
 \
 && git clone https://github.com/mjg59/python-broadlink.git /app/modules/broadlink2mqtt/broadlink \
 && cd /app/modules/broadlink2mqtt/broadlink \
 && git checkout 9ff6b2d48e58f005765088cdf3dc5cc553cdb01a \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# INSTALL GO COMPILED MODULES:
RUN echo -e "\n > INSTALL GO BUILD ENV\n" \
 && apk add --no-cache --virtual=build-dependencies \
    go \
    git \
    musl-dev \
    linux-headers

COPY app/modules/cec-client-mqtt-bridge /app/modules/cec-client-mqtt-bridge
COPY app/modules/evdev2mqtt /app/modules/evdev2mqtt
COPY app/modules/yeelight /app/modules/yeelight

RUN echo -e "\n > BUILD MODULE: CEC-CLIENT TO MQTT BRIDGE\n" \
 && cd /app/modules/cec-client-mqtt-bridge \
 && go get github.com/eclipse/paho.mqtt.golang \
 && go build -ldflags "-s -w" src/cec-client-mqtt-bridge.go \
 \
 && echo -e "\n > BUILD MODULE: EVDEV2MQTT\n" \
 && cd /app/modules/evdev2mqtt \
 && go get github.com/eclipse/paho.mqtt.golang \
 && go get github.com/gvalkov/golang-evdev \
 && go build -ldflags "-s -w" src/evdev2mqtt.go \
 \
 && echo -e "\n > BUILD MODULE: YEELIGHT\n" \
 && cd /app/modules/yeelight \
 && go build -ldflags "-s -w" src/yeelight.go \
 \
 && echo -e "\n > BUILD MODULE: WAKEONLAN\n" \
 && go get github.com/blchinezu/go-wol/cmd/wol \
 && mv /root/go/bin/wol /app/modules/wakeonlan

COPY app/modules/core /app/modules/core

RUN echo -e "\n > BUILD CORE MODULE: CONFIG\n" \
 && cd /app/modules/core \
 && go get -d ./... \
 && go build -ldflags "-s -w" src/config.go

# COPY APP FILES
COPY app/config /app/config
COPY app/modules/bluetooth-scan /app/modules/bluetooth-scan
COPY app/modules/roborock /app/modules/roborock
COPY app/modules/airpurifier-miot /app/modules/airpurifier-miot
COPY app/modules/sunrise_sunset /app/modules/sunrise_sunset
COPY app/modules/openweather /app/modules/openweather
COPY app/services /app/services
COPY app/web /app/web
COPY app/run.sh /app/run.sh


# CLEANUP ROOT
RUN echo -e "\n > CLEANUP ROOT\n" \
 && rm -rf /root/*


# WORKDIR
WORKDIR /app

# LAUNCH
CMD ["ash", "run.sh"]
