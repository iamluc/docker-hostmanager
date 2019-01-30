<?php

namespace DockerHostManager;

use Docker\API\Model\ContainersIdJsonGetResponse200;
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

    /** @var ContainersIdJsonGetResponse200[] */
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
        foreach ($this->docker->containerList() as $container) {
            $this->configureContainer($container->getId());
        }

        $this->write();
    }

    private function configureContainer(string $containerId)
    {
        try {
            $container = $this->docker->containerInspect($containerId);
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
    }

    private function listen()
    {
        $this->docker->listenEvents(function (Event $event) {
            if (null === $event->getId()) {
                return;
            }

            $this->configureContainer($event->getId());

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
                function ($container) {
                    return implode("\n", $this->getHostsLines($container));
                },
                $this->activeContainers
            ),
            [self::END_TAG]
        );
        array_splice($content, $start, $end - $start + 1, $hosts);
        file_put_contents($this->hostsFile, implode("\n", $content));
    }

    private function getHostsLines(ContainersIdJsonGetResponse200 $container): array
    {
        $lines = [];

        // Global
        if (!empty($container->getNetworkSettings()->getIPAddress())) {
            $ip = $container->getNetworkSettings()->getIPAddress();
            $lines[$ip] = implode(' ', $this->getContainerHosts($container));
        }

        // Networks
        foreach ($container->getNetworkSettings()->getNetworks() as $networkName => $conf) {
            $ip = $conf->getIPAddress();
            $aliases = is_array($conf->getAliases()) ? $conf->getAliases() : [];
            $aliases[] = substr($container->getName(), 1);

            $hosts = [];
            foreach (array_unique($aliases) as $alias) {
                $hosts[] = $alias.'.'.$networkName;
            }

            $lines[$ip] = sprintf('%s%s', isset($lines[$ip]) ? $lines[$ip].' ' : '', implode(' ', $hosts));
        }

        array_walk($lines, function (&$host, $ip) {
            $host = $ip.' '.$host;
        });

        return $lines;
    }

    private function getContainerHosts(ContainersIdJsonGetResponse200 $container): array
    {
        $hosts = [substr($container->getName(), 1).$this->tld];
        if (is_array($container->getConfig()->getEnv())) {
            $env = $container->getConfig()->getEnv();
            foreach (preg_grep('/DOMAIN_NAME=/', $env) as $row) {
                $row = substr($row, strlen('DOMAIN_NAME='));
                $hosts = array_merge($hosts, explode(',', $row));
            }
        }

        return $hosts;
    }

    private function isExposed(ContainersIdJsonGetResponse200 $container): bool
    {
        if (empty($container->getNetworkSettings()->getPorts()) || empty($container->getState()->getRunning())) {
            return false;
        }

        return true === $container->getState()->getRunning();
    }
}
