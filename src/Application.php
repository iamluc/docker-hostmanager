<?php

namespace DockerHostManager;

use Docker\Docker;
use Docker\Http\DockerClient;

class Application
{
    private $entrypoint;
    private $hostsFile;
    private $tld;

    private $client;
    private $docker;

    public function __construct($entrypoint, $hostsFile, $tld)
    {
        $this->entrypoint = $entrypoint;
        $this->hostsFile = $hostsFile;
        $this->tld = $tld;

        $this->client = new DockerClient([], $this->entrypoint);
        $this->docker = new Docker($this->client);
    }

    public function run()
    {
        $lastState = [];
        while (true) {
            $state = $this->getRunningContainers();
            if ($state !== $lastState) {
                $lastState = $state;
                $this->writeHosts($state);
            }

            sleep(1);
        }
    }

    private function getRunningContainers()
    {
        $containers = [];
        $containerManager = $this->docker->getContainerManager();
        foreach ($containerManager->findAll() as $container) {
            $containerManager->inspect($container);
            $infos = $container->getRuntimeInformations();

            if (false === $this->exposeContainer($infos)) {
                continue;
            }

            if (isset($infos['NetworkSettings']['IPAddress'])) {
                $ip = $infos['NetworkSettings']['IPAddress'];
                $containers[$ip] = substr($container->getName(), 1);
            }

            if (isset($infos['NetworkSettings']['Networks']) && is_array($infos['NetworkSettings']['Networks'])) {
                foreach ($infos['NetworkSettings']['Networks'] as $name => $conf) {
                    $containers[$conf['IPAddress']] = substr($container->getName(), 1);
                }
            }
        }

        return $containers;
    }

    private function exposeContainer($infos)
    {
        if (!isset($infos['State']['Running']) || true !== $infos['State']['Running']) {
            return false;
        }

        if (empty($infos['NetworkSettings']['Ports'])) {
            return false;
        }

        return true;
    }

    private function writeHosts(array $containers)
    {
        $content = file($this->hostsFile);

        $res = preg_grep('/^## docker-hostmanager-start/', $content);
        $start = count($res) ? key($res) : count($content) + 1;

        $res = preg_grep('/^## docker-hostmanager-end/', $content);
        $end = count($res) ? key($res) : count($content) + 1;

        $conf = ["## docker-hostmanager-start\n"];
        foreach ($containers as $ip => $name) {
            $conf[] = $ip.' '.$name.$this->tld."\n";
        }
        $conf[] = "## docker-hostmanager-end\n";

        array_splice($content, $start, $end - $start + 1, $conf);
        file_put_contents($this->hostsFile, implode('', $content));
    }
}
