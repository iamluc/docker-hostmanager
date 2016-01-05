FROM iamluc/composer

ADD . /usr/local/src/docker-hostmanager

RUN composer install --no-interaction --no-dev --prefer-dist --working-dir=/usr/local/src/docker-hostmanager \
    && ln -s /usr/local/src/docker-hostmanager/bin/docker-hostmanager /usr/local/bin/docker-hostmanager

ENV HOSTS_FILE=/hosts

ENTRYPOINT ["/usr/local/bin/docker-hostmanager"]
