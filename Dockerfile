FROM composer

ADD . /app/

RUN composer install --working-dir=/app --no-interaction --no-dev --prefer-dist --optimize-autoloader

FROM alpine:3.7

RUN apk add --no-update --no-cache \
	php7 \
	php7-cli \
	php7-iconv \
	php7-mbstring

COPY --from=0 /app/ /usr/local/src/docker-hostmanager

ENV HOSTS_FILE=/hosts

CMD ["/usr/local/src/docker-hostmanager/bin/docker-hostmanager"]
