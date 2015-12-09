#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

const DOCKER_ENTRYPOINT = 'unix:///var/run/docker.sock';
const HOSTS_FILE = '/etc/hosts';
const TLD = '.docker';

$client = new Docker\Http\DockerClient([], DOCKER_ENTRYPOINT);
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

function writeHosts(array $containers)
{
    $content = file(HOSTS_FILE);

    $res = preg_grep('/^## docker-hostmanager-start/', $content);
    $start = count($res) ? key($res) : count($content) + 1;

    $res = preg_grep('/^## docker-hostmanager-end/', $content);
    $end = count($res) ? key($res) : count($content) + 1;

    $conf = ["## docker-hostmanager-start\n"];
    foreach ($containers as $ip => $name) {
        $conf[] = $ip.' '.$name.TLD."\n";
    }
    $conf[] = "## docker-hostmanager-end\n";

    array_splice($content, $start, $end - $start + 1, $conf);
    file_put_contents(HOSTS_FILE, implode('', $content));
}

$lastState = [];
while (true) {
    $state = getRunningContainers($docker);
    if ($state !== $lastState) {
        $lastState = $state;
        writeHosts($state);
    }

    sleep(1);
}
