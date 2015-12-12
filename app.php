#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

$DOCKER_ENTRYPOINT = getenv('DOCKER_ENTRYPOINT') ?: 'unix:///var/run/docker.sock';
$HOSTS_FILE = getenv('HOSTS_FILE') ?: '/etc/hosts';
$TLD = getenv('TLD') ?: '.docker';

$client = new Docker\Http\DockerClient([], $DOCKER_ENTRYPOINT);
$docker = new Docker\Docker($client);

function getRunningContainers(Docker\Docker $docker)
{
    $containers = [];
    $containerManager = $docker->getContainerManager();
    foreach ($containerManager->findAll() as $container) {
        $containerManager->inspect($container);
        $infos = $container->getRuntimeInformations();

        if (!isset($infos['State']['Running']) || true !== $infos['State']['Running']) {
            continue;
        }

        if (empty($infos['NetworkSettings']['Ports'])) {
            continue;
        }

        if (!isset($infos['NetworkSettings']['IPAddress'])) {
            continue;
        }

        $ip = $infos['NetworkSettings']['IPAddress'];
        $containers[$ip] = substr($container->getName(), 1);
    }

    return $containers;
}

function writeHosts($hostsFile, $tld, array $containers)
{
    $content = file($hostsFile);

    $res = preg_grep('/^## docker-hostmanager-start/', $content);
    $start = count($res) ? key($res) : count($content) + 1;

    $res = preg_grep('/^## docker-hostmanager-end/', $content);
    $end = count($res) ? key($res) : count($content) + 1;

    $conf = ["## docker-hostmanager-start\n"];
    foreach ($containers as $ip => $name) {
        $conf[] = $ip.' '.$name.$tld."\n";
    }
    $conf[] = "## docker-hostmanager-end\n";

    array_splice($content, $start, $end - $start + 1, $conf);
    file_put_contents($hostsFile, implode('', $content));
}

$lastState = [];
while (true) {
    $state = getRunningContainers($docker);
    if ($state !== $lastState) {
        $lastState = $state;
        writeHosts($HOSTS_FILE, $TLD, $state);
    }

    sleep(1);
}
