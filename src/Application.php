<?php

namespace DockerHostManager;

use Docker\Container;
use DockerHostManager\Docker\Docker;
use DockerHostManager\Docker\Event;
use Docker\Http\DockerClient;

class Application
{
    const START_TAG = '## docker-hostmanager-start';
    const END_TAG = '## docker-hostmanager-end';

    /** @var string */
    private $entrypoint;
    /** @var string */
    private $hostsFile;
    /** @var string */
    private $tld;

    /** @var Docker  */
    private $docker;
    /** @var array Container */
    private $activeContainers = [];

    /**
     * @param string $entrypoint
     * @param string $hostsFile
     * @param string $tld
     */
    public function __construct($entrypoint, $hostsFile, $tld)
    {
        $this->entrypoint = $entrypoint;
        $this->hostsFile = $hostsFile;
        $this->tld = $tld;
        $client = new DockerClient([], $this->entrypoint);
        $this->docker = new Docker($client);
    }

    public function run()
    {
        $this->init();
        $this->listen();
    }

    private function init()
    {
        $this->activeContainers = array_filter($this->docker->getContainerManager()->findAll(), function (Container $container) {
            $this->docker->getContainerManager()->inspect($container);
            return $this->isExposed($container);
        });
        $this->write();
    }

    private function listen()
    {
        $this->docker->listenEvents($this->entrypoint, function(Event $event) {
            $container = $this->docker->getContainerManager()->find($event->getId());
            $this->docker->getContainerManager()->inspect($container);
            if ($this->isExposed($container)) {
                $this->addActiveContainer($container);
            } else {
                $this->removeActiveContainer($container);
            }
            $this->write();
        });
    }

    /**
     * @param Container $container
     */
    private function addActiveContainer(Container $container)
    {
        $id = $container->getId();
        if (!empty($this->activeContainers[$id])) {
            return;
        }
        $this->activeContainers[$id] = $container;
    }

    /**
     * @param Container $container
     */
    private function removeActiveContainer(Container $container)
    {
        $this->activeContainers = array_filter($this->activeContainers, function (Container $c) use ($container) {
            return $c->getId() !== $container->getId();
        });
    }

    private function write()
    {
        $content = array_map('trim', file($this->hostsFile));
        $res = preg_grep('/^'.self::START_TAG.'/', $content);
        $start = count($res) ? key($res) : count($content) + 1;
        $res = preg_grep('/^'.self::END_TAG.'/', $content);
        $end = count($res) ? key($res) : count($content) + 1;
        $hosts = array_merge(
            [self::START_TAG],
            array_map(
                function (Container $container) {
                    return implode("\n", $this->getHostsLines($container));
                },
                $this->activeContainers
            ),
            [self::END_TAG]
        );
        array_splice($content, $start, $end - $start + 1, $hosts);
        file_put_contents($this->hostsFile, implode("\n", $content));
    }

    /**
     * @param Container $container
     *
     * @return array
     *
     * @throws ContainerNotFoundException
     */
    private function getHostsLines(Container $container)
    {
        $this->docker->getContainerManager()->inspect($container);

        $lines = [];
        $hosts = $this->getContainerHosts($container);
        foreach ($this->getContainerIps($container) as $ip) {
            $lines[] = $ip.' '.implode(' ', $hosts);
        }

        return $lines;
    }

    /**
     * @param Container $container
     *
     * @return array
     */
    private function getContainerIps(Container $container)
    {
        $inspection = $container->getRuntimeInformations();

        $ips = [];
        if (!empty($inspection['NetworkSettings']['IPAddress'])) {
            $ips[] = $inspection['NetworkSettings']['IPAddress'];
        }

        if (isset($inspection['NetworkSettings']['Networks']) && is_array($inspection['NetworkSettings']['Networks'])) {
            foreach ($inspection['NetworkSettings']['Networks'] as $conf) {
                $ips[] = $conf['IPAddress'];
            }
        }

        return array_unique($ips);
    }

    /**
     * @param Container $container
     *
     * @return array
     */
    private function getContainerHosts(Container $container)
    {
        $inspection = $container->getRuntimeInformations();

        $hosts = [substr($container->getName(), 1).$this->tld];
        if (isset($inspection['Config']['Env']) && is_array($inspection['Config']['Env'])) {
            $env = $inspection['Config']['Env'];
            foreach (preg_grep('/DOMAIN_NAME=/', $env) as $row) {
                $row = substr($row, strlen('DOMAIN_NAME='));
                $hosts = array_merge($hosts, explode(',', $row));
            }
        }

        return $hosts;
    }

    /**
     * @param Container $container
     *
     * @return bool
     */
    private function isExposed(Container $container)
    {
        $inspection = $container->getRuntimeInformations();
        if (
            empty($inspection['NetworkSettings']['Ports']) ||
            empty($inspection['NetworkSettings']['IPAddress']) ||
            empty($inspection['State']['Running'])
        ) {
            return false;
        }

        return $inspection['State']['Running'];
    }
}
