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
# RUN echo -e "\n > INSTALL MOSQUITTO\n" \
#  && apk add --no-cache \
# 	mosquitto \
# 	mosquitto-clients \
#  \
#  && echo -e "\n > CLEANUP\n" \
#  && rm -rf \
#     /tmp/* \
#     /var/tmp/*
ENV MOSQUITTO_VERSION=1.6.9 \
    DOWNLOAD_SHA256=412979b2db0a0020bd02fa64f0a0de9e7000b84462586e32b67f29bb1f6c1685 \
    GPG_KEYS=A0D6EEA1DCAE49A635A3B2F0779B22DFB3E717B7 \
    LIB_WEBSOCKETS_VERSION=2.4.2

RUN echo -e "\n > INSTALL MOSQUITTO\n" \
 && set -x \
 && apk --no-cache add --virtual build-deps \
      build-base \
      cmake \
      gnupg \
      libressl-dev \
      util-linux-dev \
 && wget https://github.com/warmcat/libwebsockets/archive/v${LIB_WEBSOCKETS_VERSION}.tar.gz -O /tmp/lws.tar.gz \
 && mkdir -p /build/lws \
 && tar --strip=1 -xf /tmp/lws.tar.gz -C /build/lws \
 && rm /tmp/lws.tar.gz \
 && cd /build/lws \
 && cmake . \
      -DCMAKE_BUILD_TYPE=MinSizeRel \
      -DCMAKE_INSTALL_PREFIX=/usr \
      -DLWS_IPV6=ON \
      -DLWS_WITHOUT_BUILTIN_GETIFADDRS=ON \
      -DLWS_WITHOUT_CLIENT=ON \
      -DLWS_WITHOUT_EXTENSIONS=ON \
      -DLWS_WITHOUT_TESTAPPS=ON \
      -DLWS_WITH_SHARED=OFF \
      -DLWS_WITH_ZIP_FOPS=OFF \
      -DLWS_WITH_ZLIB=OFF \
 && make -j "$(nproc)" \
 && rm -rf /root/.cmake \
 && \
    wget https://mosquitto.org/files/source/mosquitto-${MOSQUITTO_VERSION}.tar.gz -O /tmp/mosq.tar.gz \
 && echo "$DOWNLOAD_SHA256  /tmp/mosq.tar.gz" | sha256sum -c - \
 && wget https://mosquitto.org/files/source/mosquitto-${MOSQUITTO_VERSION}.tar.gz.asc -O /tmp/mosq.tar.gz.asc \
 && export GNUPGHOME="$(mktemp -d)" \
 && found=''; \
    for server in \
        ha.pool.sks-keyservers.net \
        hkp://keyserver.ubuntu.com:80 \
        hkp://p80.pool.sks-keyservers.net:80 \
        pgp.mit.edu \
    ; do \
        echo "Fetching GPG key $GPG_KEYS from $server"; \
        gpg --keyserver "$server" --keyserver-options timeout=10 --recv-keys "$GPG_KEYS" && found=yes && break; \
    done; \
    test -z "$found" && echo >&2 "error: failed to fetch GPG key $GPG_KEYS" && exit 1; \
    gpg --batch --verify /tmp/mosq.tar.gz.asc /tmp/mosq.tar.gz \
 && gpgconf --kill all \
 && rm -rf "$GNUPGHOME" /tmp/mosq.tar.gz.asc \
 && mkdir -p /build/mosq \
 && tar --strip=1 -xf /tmp/mosq.tar.gz -C /build/mosq \
 && rm /tmp/mosq.tar.gz \
 && make -C /build/mosq -j "$(nproc)" \
        CFLAGS="-Wall -O2 -I/build/lws/include" \
        LDFLAGS="-L/build/lws/lib" \
        WITH_ADNS=no \
        WITH_DOCS=no \
        WITH_SHARED_LIBRARIES=yes \
        WITH_SRV=no \
        WITH_STRIP=yes \
        WITH_TLS_PSK=no \
        WITH_WEBSOCKETS=yes \
        prefix=/usr \
        binary \
 && addgroup -S -g 1883 mosquitto 2>/dev/null \
 && adduser -S -u 1883 -D -H -h /var/empty -s /sbin/nologin -G mosquitto -g mosquitto mosquitto 2>/dev/null \
 && mkdir -p /etc/mosquitto /mosquitto/data /mosquitto/log \
 && install -d /usr/sbin/ \
 && install -s -m755 /build/mosq/client/mosquitto_pub /usr/bin/mosquitto_pub \
 && install -s -m755 /build/mosq/client/mosquitto_rr /usr/bin/mosquitto_rr \
 && install -s -m755 /build/mosq/client/mosquitto_sub /usr/bin/mosquitto_sub \
 && install -s -m644 /build/mosq/lib/libmosquitto.so.1 /usr/lib/libmosquitto.so.1 \
 && install -s -m755 /build/mosq/src/mosquitto /usr/sbin/mosquitto \
 && install -s -m755 /build/mosq/src/mosquitto_passwd /usr/bin/mosquitto_passwd \
 && install -m644 /build/mosq/mosquitto.conf /etc/mosquitto/mosquitto.conf \
 && apk --no-cache add \
        ca-certificates \
 && apk del build-deps \
 && rm -rf \
      /build \
      /tmp/*


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


# INSTALL BROADLINK
COPY app/modules/broadlink2mqtt /app/modules/broadlink2mqtt
RUN echo -e "\n > INSTALL BROADLINK IN $BROADLINK_PATH\n" \
 && apk add --no-cache --virtual=build-dependencies \
    git \
 && pip3 install \
    paho-mqtt \
 \
 && git clone https://github.com/mjg59/python-broadlink.git /app/modules/broadlink2mqtt/broadlink \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /tmp/* \
    /var/tmp/*

# PYTHON-MIIO
RUN echo -e "\n > INSTALL PYTHON-MIIO\n" \
 && apk add --no-cache --virtual build-dependencies \
    git \
 \
 && git clone https://github.com/foxel/python-miio.git /tmp/python-miio \
 && cd /tmp/python-miio \
 && git checkout air-purifier-3h-support \
 && python3 setup.py install -f \
 \
 && echo -e "\n > CLEANUP\n" \
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# COPY APP FILES
COPY app/config /app/config
COPY app/modules/bluetooth-scan /app/modules/bluetooth-scan
COPY app/modules/roborock /app/modules/roborock
COPY app/modules/airpurifier-miot /app/modules/airpurifier-miot
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
EXPOSE 1884

# LAUNCH
CMD ["ash", "run.sh"]
