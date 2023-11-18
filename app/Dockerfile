FROM alpine:3.18.4
MAINTAINER Gabriel Ionescu <gabi.ionescu+docker@protonmail.com>

# CREATE USER
RUN echo -e "\n > CREATE DOCKER USER: 1000\n" \
 && addgroup -g 1000 1000 \
 && adduser -D -u 1000 -G 1000 1000


# INSTALL ESSENTIAL PACKAGES
RUN echo -e "\n > INSTALL DEPENDENCIES\n" \
 && apk update \
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
    php82 \
    php82-curl \
    php82-json \
    php82-mysqli \
    php82-session \
    php82-opcache \
    php82-pecl-yaml \
 \
 && ln -s /usr/bin/php82 /usr/bin/php \
 \
 && echo -e "\n[opcache]\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.enable=1\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.enable_cli=1\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.revalidate_freq=300\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.memory_consumption=256\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.max_accelerated_files=50000\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.save_comments=0\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.validate_timestamps=1\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.interned_strings_buffer=32\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.jit_buffer_size=500000000\n" >> /etc/php82/conf.d/00_opcache.ini \
 && echo -e "opcache.jit=1235\n" >> /etc/php82/conf.d/00_opcache.ini \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*

# INSTALL APACHE
RUN echo -e "\n > INSTALL APACHE\n" \
 && apk add --no-cache \
    apache2 \
    php82-apache2 \
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


# INSTALL CEC SUPPORT
RUN echo -e "\n > INSTALL CEC SUPPORT\n" \
 && apk add --no-cache \
    libcec \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# # INSTALL BLUETOOTH
# RUN echo -e "\n > INSTALL HCITOOL\n" \
#  && apk add --no-cache \
#     bluez-deprecated \
#  \
#  && echo -e "\n > CLEANUP\n" \
#  && rm -rf \
#     /tmp/* \
#     /var/tmp/*


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


# INSTALL MOSQUITTO
RUN echo -e "\n > INSTALL MOSQUITTO\n" \
 && apk add --no-cache \
 mosquitto-clients \
 \
 && echo -e "\n > CLEANUP\n" \
 && rm -rf \
    /tmp/* \
    /var/tmp/*


# # INSTALL BROADLINK
# COPY app/modules/broadlink2mqtt /app/modules/broadlink2mqtt
# RUN echo -e "\n > INSTALL BROADLINK IN $BROADLINK_PATH\n" \
#  && apk add --no-cache --virtual=build-dependencies \
#     git \
#     py3-pip \
#  \
#  && pip3 install paho-mqtt \
#  \
#  && git clone https://github.com/mjg59/python-broadlink.git /app/modules/broadlink2mqtt/broadlink \
#  && cd /app/modules/broadlink2mqtt/broadlink \
#  && git checkout 9ff6b2d48e58f005765088cdf3dc5cc553cdb01a \
#  \
#  && echo -e "\n > CLEANUP\n" \
#  && apk del --purge \
#     build-dependencies \
#  && rm -rf \
#     /tmp/* \
#     /var/tmp/*


# INSTALL GO COMPILED MODULES:
RUN echo -e "\n > INSTALL GO BUILD ENV\n" \
 && apk add --no-cache --virtual=build-dependencies \
    go \
    git \
    musl-dev \
    linux-headers

COPY app/modules/cec-client-mqtt-bridge /app/modules/cec-client-mqtt-bridge
COPY app/modules/evdev2mqtt /app/modules/evdev2mqtt
COPY app/modules/snapcast2mqtt /app/modules/snapcast2mqtt
COPY app/modules/yeelight /app/modules/yeelight

RUN echo -e "\n > BUILD MODULE: CEC-CLIENT TO MQTT BRIDGE\n" \
 && cd /app/modules/cec-client-mqtt-bridge/src \
 && go build -ldflags "-s -w" -o ../cec-client-mqtt-bridge cec-client-mqtt-bridge.go

RUN echo -e "\n > BUILD MODULE: EVDEV2MQTT\n" \
 && cd /app/modules/evdev2mqtt/src \
 && go build -ldflags "-s -w" -o ../evdev2mqtt evdev2mqtt.go

RUN echo -e "\n > BUILD MODULE: SNAPCAST2MQTT\n" \
 && cd /app/modules/snapcast2mqtt/src \
 && go build -ldflags "-s -w" -o ../snapcast2mqtt snapcast2mqtt.go

RUN echo -e "\n > BUILD MODULE: YEELIGHT\n" \
 && cd /app/modules/yeelight/src \
 && go build -ldflags "-s -w" -o ../yeelight yeelight.go

# RUN echo -e "\n > BUILD MODULE: WAKEONLAN\n" \
#  && go install github.com/blchinezu/go-wol/cmd/wol@latest \
#  && mv /root/go/bin/wol /app/modules/wakeonlan

COPY app/modules/core /app/modules/core

RUN echo -e "\n > BUILD CORE MODULES: Fetch dependencies\n" \
 && cd /app/modules/core/src \
 \
 && echo -e "\n > BUILD CORE MODULE: config\n" \
 && go build -ldflags "-s -w" -o ../config config.go \
 \
 && echo -e "\n > BUILD CORE MODULE: state\n" \
 && go build -ldflags "-s -w" -o ../state state.go \
 \
 && echo -e "\n > BUILD CORE MODULE: functions\n" \
 && go build -ldflags "-s -w" -o ../functions functions.go \
 \
 && echo -e "\n > BUILD CORE MODULE: incoming\n" \
 && go build -ldflags "-s -w" -o ../incoming incoming.go \
 \
 && echo -e "\n > BUILD CORE MODULE: telegram-bot\n" \
 && go build -ldflags "-s -w" -o ../telegram-bot telegram-bot.go \
 \
 && echo -e "\n > BUILD CORE MODULE: free-text-parser\n" \
 && go build -ldflags "-s -w" -o ../free-text-parser free-text-parser.go

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
