FROM alpine:3.8
MAINTAINER Gabriel Ionescu <gabi.ionescu+docker@protonmail.com>


# CREATE USER
ARG DOCKER_USERID
ARG DOCKER_GROUPID
ARG DOCKER_USERNAME
ENV DOCKER_USERID $DOCKER_USERID
ENV DOCKER_GROUPID $DOCKER_GROUPID
ENV DOCKER_USERNAME $DOCKER_USERNAME
RUN echo -e "\n > CREATE DOCKER USER: $DOCKER_USERNAME\n" \
 && addgroup -g $DOCKER_GROUPID $DOCKER_USERNAME \
 && adduser -D -u $DOCKER_USERID -G $DOCKER_USERNAME $DOCKER_USERNAME


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


# INSTALL MOSQUITTO
RUN echo -e "\n > INSTALL MOSQUITTO\n" \
 && apk add --no-cache \
	mosquitto \
	mosquitto-clients \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# INSTALL MARIADB
RUN echo -e "\n > INSTALL MARIADB\n" \
 && apk add --no-cache \
    mariadb \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


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
ARG APACHE_SERVER_NAME
ARG APACHE_PORT
RUN echo -e "\n > INSTALL APACHE\n" \
 && apk add --no-cache \
    apache2 \
    php7-apache2 \
 && sed -i "s/#ServerName www.example.com:80/ServerName $APACHE_SERVER_NAME/" /etc/apache2/httpd.conf \
 && sed -i 's/\/var\/www\/localhost\/htdocs/\/app\/web/g' /etc/apache2/httpd.conf \
 && sed -i "s/    AllowOverride None/    AllowOverride All/" /etc/apache2/httpd.conf \
 && sed -i "s/#LoadModule rewrite_module/LoadModule rewrite_module/" /etc/apache2/httpd.conf \
 && sed -i "s/Listen 80/Listen $APACHE_PORT/" /etc/apache2/httpd.conf \
 && mkdir -p /run/apache2 \
 \
 && echo -e "\n > ADD APACHE TO THE USER GROUP: $DOCKER_GROUPID\n" \
 && sed -i "s/$DOCKER_USERNAME:x:$DOCKER_GROUPID:$DOCKER_USERNAME/$DOCKER_USERNAME:x:$DOCKER_GROUPID:$DOCKER_USERNAME,apache/" /etc/group \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# INSTALL ZIGBEE2MQTT
ENV ZIGBEE2MQTT_PATH /app/modules/zigbee2mqtt
RUN echo -e "\n > INSTALL ZIGBEE2MQTT IN $ZIGBEE2MQTT_PATH\n" \
 && apk add --no-cache --virtual=build-dependencies \
    make \
    gcc \
    g++ \
    python \
    linux-headers \
    udev \
    git \
 && apk add --no-cache \
    nodejs \
    npm \
 \
 && git clone https://github.com/koenkk/zigbee2mqtt $ZIGBEE2MQTT_PATH \
 && cd $ZIGBEE2MQTT_PATH \
 && cp data/configuration.yaml ./ \
 \
 && npm install --unsafe-perm \
 && npm i semver mqtt winston moment js-yaml object-assign-deep mkdir-recursive rimraf \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    $ZIGBEE2MQTT_PATH/.git \
    /tmp/* \
    /var/tmp/* \
    /root/.npm \
    /root/.node-gyp


# PYTHON-MIIO
RUN echo -e "\n > INSTALL PYTHON-MIIO\n" \
 && apk add --no-cache \
    python3 \
    openssl \
 && apk add --no-cache --virtual build-dependencies \
    gcc \
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


# INSTALL GO COMPILED MODULES:
# - CEC-CLIENT TO MQTT BRIDGE
# - EVDEV2MQTT
# - YEELIGHT
# - WAKEONLAN
COPY app/modules/cec-client-mqtt-bridge /app/modules/cec-client-mqtt-bridge
COPY app/modules/evdev2mqtt /app/modules/evdev2mqtt
COPY app/modules/yeelight /app/modules/yeelight
RUN echo -e "\n > INSTALL GO BUILD ENV\n" \
 && apk add --no-cache --virtual=build-dependencies \
    go \
    git \
    musl-dev \
    linux-headers \
    \
 && echo -e "\n > BUILD MODULE: CEC-CLIENT TO MQTT BRIDGE\n" \
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
 && mv /root/go/bin/wol /app/modules/wakeonlan \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /app/modules/cec-client-mqtt-bridge/src \
    /app/modules/evdev2mqtt/src \
    /app/modules/yeelight/src \
    /root/.cache \
    /root/go \
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


# COPY APP FILES
COPY app/config /app/config
COPY app/modules/bluetooth-scan /app/modules/bluetooth-scan
COPY app/modules/roborock /app/modules/roborock
COPY app/modules/sunrise_sunset /app/modules/sunrise_sunset
COPY app/services /app/services
COPY app/web /app/web
COPY app/run.sh /app/run.sh


# CLEANUP ROOT
RUN echo -e "\n > CLEANUP ROOT\n" \
 && rm -rf /root/*


# WORKDIR
WORKDIR /app

# EXPOSE WEB SERVER
EXPOSE $APACHE_PORT

# EXPOSE MQTT BROKER
EXPOSE 1883

# LAUNCH
CMD ["ash", "run.sh"]
