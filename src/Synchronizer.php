<?php

namespace DockerHostManager;

use Docker\Container;
use Docker\Exception\APIException;
use Docker\Exception\ContainerNotFoundException;
use DockerHostManager\Docker\Docker;
use DockerHostManager\Docker\Event;

class Synchronizer
{
    const START_TAG = '## docker-hostmanager-start';
    const END_TAG = '## docker-hostmanager-end';

    /** @var Docker  */
    private $docker;
    /** @var string */
    private $hostsFile;
    /** @var string */
    private $tld;

    /** @var array Container */
    private $activeContainers = [];

    /**
     * @param Docker $docker
     * @param string $hostsFile
     * @param string $tld
     */
    public function __construct(Docker $docker, $hostsFile, $tld)
    {
        $this->docker = $docker;
        $this->hostsFile = $hostsFile;
        $this->tld = $tld;
    }

    public function run()
    {
        if (!is_writable($this->hostsFile)) {
            throw new \RuntimeException(sprintf('File "%s" is not writable.', $this->hostsFile));
        }

        $this->init();
        $this->listen();
    }

    private function init()
    {
        foreach ($this->docker->getContainerManager()->findAll() as $container) {
            if ($this->isExposed($container)) {
                $this->activeContainers[$container->getId()] = $container;
            }
        }

        $this->write();
    }

    private function listen()
    {
        $this->docker->listenEvents(function (Event $event) {
            if (null === $event->getId()) {
                return;
            }

            try  {
                $container = $this->docker->getContainerManager()->find($event->getId());
            } catch (\Exception $e) {
                return;
            }

            if (null === $container) {
                return;
            }

            if ($this->isExposed($container)) {
                $this->activeContainers[$container->getId()] = $container;
            } else {
                unset($this->activeContainers[$container->getId()]);
            }

            $this->write();
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
        $inspection = $container->getRuntimeInformations();
        $lines = [];

        // Global
        if (!empty($inspection['NetworkSettings']['IPAddress'])) {
            $ip = $inspection['NetworkSettings']['IPAddress'];

            $lines[] = $ip.' '.implode(' ', $this->getContainerHosts($container));
        }

        // Networks
        if (isset($inspection['NetworkSettings']['Networks']) && is_array($inspection['NetworkSettings']['Networks'])) {
            foreach ($inspection['NetworkSettings']['Networks'] as $networkName => $conf) {
                $ip = $conf['IPAddress'];

                $aliases = isset($conf['Aliases']) && is_array($conf['Aliases']) ? $conf['Aliases'] : [];
                $aliases[] = substr($container->getName(), 1);

                $hosts = [$this->getTldHost($container)];
                foreach (array_unique($aliases) as $alias) {
                    $hosts[] = $alias.'.'.$networkName;
                }

                $lines[] = $ip.' '.implode(' ', $hosts);
            }
        }

        return $lines;
    }

    /**
     * @param Container $container
     *
     * @return array
     */
    private function getContainerHosts(Container $container)
    {
        $inspection = $container->getRuntimeInformations();

        $hosts = [$this->getTldHost($container)];
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
     * @return string
     */
    private function getTldHost(Container $container)
    {
        return substr($container->getName(), 1).$this->tld;
    }

    /**
     * @param Container $container
     *
     * @return bool
     */
    private function isExposed(Container $container)
    {
        try {
            $this->docker->getContainerManager()->inspect($container);
        } catch (APIException $e) {
            // Happen on "docker build"
            return false;
        }

        $inspection = $container->getRuntimeInformations();
        if (empty($inspection['NetworkSettings']['Ports']) || empty($inspection['State']['Running'])) {
            return false;
        }

        return $inspection['State']['Running'];
    }
}
