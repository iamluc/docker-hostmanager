FROM composer

ADD . /app/

RUN composer install --working-dir=/app --ignore-platform-reqs --no-interaction --no-dev --prefer-dist --optimize-autoloader

FROM alpine:3.7

RUN apk add --no-cache \
	php7 \
	php7-cli \
	php7-iconv \
	php7-mbstring \
	php7-intl \
	php7-json

COPY --from=0 /app/ /usr/local/src/docker-hostmanager

ENV HOSTS_FILE=/hosts

ENTRYPOINT ["/usr/local/src/docker-hostmanager/bin/docker-hostmanager"]
