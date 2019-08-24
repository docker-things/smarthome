FROM alpine:3.8
MAINTAINER Gabriel Ionescu <gabi.ionescu+docker@protonmail.com>


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


# INSTALL NODEJS
ENV NODE_VERSION 10.16.0
RUN echo -e "\n > INSTALL NODEJS $NODE_VERSION\n" \
 && apk add --no-cache \
    libstdc++ \
 && apk add --no-cache --virtual build-dependencies \
    binutils-gold \
    curl \
    g++ \
    gcc \
    gnupg \
    libgcc \
    linux-headers \
    make \
    python \
 # gpg keys listed at https://github.com/nodejs/node#release-keys
 && for key in \
        94AE36675C464D64BAFA68DD7434390BDBE9B9C5 \
        FD3A5288F042B6850C66B31F09FE44734EB7990E \
        71DCFD284A79C3B38668286BC97EC7A07EDE3FC1 \
        DD8F2338BAE7501E3DD5AC78C273792F7D83545D \
        C4F0DFFF4E8C1A8236409D08E73BC641CC11F4C8 \
        B9AE9905FFD7803F25714661B63B535A4C206CA9 \
        77984A986EBC2AA786BC0F66B01FBB92821C587A \
        8FCCA13FEF1D0C2E91008E09770F7A9A5AE15600 \
        4ED778F539E3634C779C87C6D7062848A1AB005C \
        A48C2BEE680E841632CD4E44F07496B3EB3C1762 \
        B9E2F5981AA6E0CD28160D9FF13993A75599653C \
    ; do \
        gpg --batch --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys "$key" || \
        gpg --batch --keyserver hkp://ipv4.pool.sks-keyservers.net --recv-keys "$key" || \
        gpg --batch --keyserver hkp://pgp.mit.edu:80 --recv-keys "$key" ; \
    done \
 && curl -fsSLO --compressed "https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION.tar.xz" \
 && curl -fsSLO --compressed "https://nodejs.org/dist/v$NODE_VERSION/SHASUMS256.txt.asc" \
 && gpg --batch --decrypt --output SHASUMS256.txt SHASUMS256.txt.asc \
 && grep " node-v$NODE_VERSION.tar.xz\$" SHASUMS256.txt | sha256sum -c - \
 && tar -xf "node-v$NODE_VERSION.tar.xz" \
 && cd "node-v$NODE_VERSION" \
 && ./configure \
 && make -j$(getconf _NPROCESSORS_ONLN) V= \
 && make install \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del build-dependencies \
 && cd .. \
 && rm -rf \
    "node-v$NODE_VERSION" \
    "node-v$NODE_VERSION.tar.xz" \
    SHASUMS256.txt.asc \
    SHASUMS256.txt \
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
 # && apk add --no-cache \
    # nodejs \
    # npm \
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
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*



# INSTALL MODULE: YEELIGHT
COPY app/modules/yeelight /app/modules/yeelight
RUN echo -e "\n > INSTALL MODULE: YEELIGHT\n" \
 && apk add --no-cache --virtual=build-dependencies \
    go \
    git \
    musl-dev \
 \
 && cd /app/modules/yeelight \
 && go build -ldflags "-s -w" src/yeelight.go \
 \
 && echo -e "\n > INSTALL MODULE: WAKEONLAN\n" \
 && go get github.com/blchinezu/go-wol/cmd/wol \
 && mv /root/go/bin/wol /app/modules/wakeonlan \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /app/modules/yeelight/src \
    /root/.cache \
    /root/go \
    /tmp/* \
    /var/tmp/*


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


# INSTALL CEC-CLIENT TO MQTT BRIDGE
COPY app/modules/cec-client-mqtt-bridge /app/modules/cec-client-mqtt-bridge
RUN echo -e "\n > INSTALL CEC-CLIENT TO MQTT BRIDGE\n" \
 && apk add --no-cache --virtual=build-dependencies \
    go \
    git \
    musl-dev \
    \
 && cd /app/modules/cec-client-mqtt-bridge \
 && go get github.com/eclipse/paho.mqtt.golang \
 && go build -ldflags "-s -w" src/cec-client-mqtt-bridge.go \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /app/modules/cec-client-mqtt-bridge/src \
    /root/.cache \
    /root/go \
    /tmp/* \
    /var/tmp/*


# INSTALL EVDEV2MQTT
COPY app/modules/evdev2mqtt /app/modules/evdev2mqtt
RUN echo -e "\n > INSTALL EVDEV2MQTT\n" \
 && apk add --no-cache --virtual=build-dependencies \
    go \
    git \
    musl-dev \
    linux-headers \
    \
 && cd /app/modules/evdev2mqtt \
 && go get github.com/eclipse/paho.mqtt.golang \
 && go get github.com/gvalkov/golang-evdev \
 && go build -ldflags "-s -w" src/evdev2mqtt.go \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /app/modules/evdev2mqtt/src \
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


# ARGS
ARG DOCKER_USERID
ARG DOCKER_GROUPID
ARG DOCKER_USERNAME
ENV DOCKER_USERID $DOCKER_USERID
ENV DOCKER_GROUPID $DOCKER_GROUPID
ENV DOCKER_USERNAME $DOCKER_USERNAME
RUN echo -e "\n > CREATE DOCKER USER: $DOCKER_USERNAME\n" \
 && addgroup -g $DOCKER_GROUPID $DOCKER_USERNAME \
 && adduser -D -u $DOCKER_USERID -G $DOCKER_USERNAME $DOCKER_USERNAME


# ADD APACHE TO THE USER GROUP
RUN echo -e "\n > ADD APACHE TO THE USER GROUP: $DOCKER_GROUPID\n" \
 && sed -i "s/$DOCKER_USERNAME:x:$DOCKER_GROUPID:$DOCKER_USERNAME/$DOCKER_USERNAME:x:$DOCKER_GROUPID:$DOCKER_USERNAME,apache/" /etc/group


# SET TIMEZONE
ARG DOCKER_TIMEZONE
ENV TZ "$DOCKER_TIMEZONE"
RUN echo -e "\n > SET TIMEZONE: $TZ\n" \
 && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
 && echo $TZ > /etc/timezone


# INSTALL UI
COPY app/smart-home-ui /app/smart-home-ui
RUN echo -e "\n > INSTALL UI DEPENDENCIES\n" \
 && cd /app/smart-home-ui \
 && npm i \
 && echo -e "\n > BUILD UI\n" \
 && cd /app/smart-home-ui \
 && npm run build \
 && mv dist/smart-home-ui /tmp/smart-home-ui \
 && echo -e "\n > CLEANUP\n" \
 && cd /app \
 && rm -rf \
        /app/smart-home-ui \
        /root/.npm \
        /root/.node-gyp


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


# MOVE UI BUILD
RUN echo -e "\n > MOVE UI\n" \
 && mv /tmp/smart-home-ui/* /app/web/ \
 && rmdir /tmp/smart-home-ui


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
