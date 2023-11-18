FROM python:3.8-alpine

RUN apk add --no-cache \
    bash \
    mosquitto-clients \
    openssl \
 && apk add --no-cache --virtual build-dependencies \
    py3-pip \
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
 && apk del --purge \
    build-dependencies \
 && rm -rf \
    /tmp/* \
    /var/tmp/*

COPY run.sh /run.sh

RUN chmod +x /run.sh

CMD [ "bash", "/run.sh" ]
