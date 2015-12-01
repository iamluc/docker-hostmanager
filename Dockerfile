FROM iamluc/composer

ADD . /usr/local/src/docker-hostmanager

RUN composer install --no-interaction --prefer-dist --working-dir=/usr/local/src/docker-hostmanager \
    && ln -s /usr/local/src/docker-hostmanager/app.php /usr/local/bin/docker-hostmanager

ENTRYPOINT ["/usr/local/bin/docker-hostmanager"]
