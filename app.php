#!/usr/bin/env php
<?php

namespace DockerHostManager;

require_once __DIR__.'/vendor/autoload.php';

$entrypoint = getenv('DOCKER_ENTRYPOINT') ?: 'unix:///var/run/docker.sock';
$hostsFile = getenv('HOSTS_FILE') ?: '/etc/hosts';
$tld = getenv('TLD') ?: '.docker';

$app = new Application($entrypoint, $hostsFile, $tld);
$app->run();
