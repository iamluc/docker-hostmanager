<?php

namespace DockerHostManager;

use Docker\API\Model\Container;
use Docker\Manager\ContainerManager;
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

    /** @var array */
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
        foreach ($this->docker->getContainerManager()->findAll() as $containerConfig) {
            $response = $this->docker->getContainerManager()->find($containerConfig->getId(), [], ContainerManager::FETCH_RESPONSE);
            $container = json_decode(\GuzzleHttp\Psr7\copy_to_string($response->getBody()), true);

            if ($this->isExposed($container)) {
                $this->activeContainers[$container['Id']] = $container;
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
                $response = $this->docker->getContainerManager()->find($event->getId(), [], ContainerManager::FETCH_RESPONSE);
                $container = json_decode(\GuzzleHttp\Psr7\copy_to_string($response->getBody()), true);
            } catch (\Exception $e) {
                return;
            }

            if (null === $container) {
                return;
            }

            if ($this->isExposed($container)) {
                $this->activeContainers[$container['Id']] = $container;
            } else {
                unset($this->activeContainers[$container['Id']]);
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

    /**
     * @param $container
     *
     * @return array
     */
    private function getHostsLines($container)
    {
        $lines = [];

        // Global
        if (!empty($container['NetworkSettings']['IPAddress'])) {
            $ip = $container['NetworkSettings']['IPAddress'];

            $lines[$ip] = implode(' ', $this->getContainerHosts($container));
        }

        // Networks
        if (isset($container['NetworkSettings']['Networks']) && is_array($container['NetworkSettings']['Networks'])) {
            foreach ($container['NetworkSettings']['Networks'] as $networkName => $conf) {
                preg_match(sprintf('/^%s_(.+)$/', $this->getProjectName($container)), $networkName, $matches);
                $networkShortName = $matches[1];
                $ip = $conf['IPAddress'];

                $aliases = isset($conf['Aliases']) && is_array($conf['Aliases']) ? $conf['Aliases'] : [];
                $aliases[] = substr($container['Name'], 1);

                $hosts = [];
                foreach (array_unique($aliases) as $alias) {
                    $hosts[] = $alias.'.'.$networkName;
                }

                foreach ($this->getAdditionalContainerHosts($container) as $host) {
                    if (preg_match('/^(.+):(.+)$/', $host, $matches) && $matches[1] === $networkShortName) {
                        $hosts[] = $matches[2];
                    }
                }

                $lines[$ip] = sprintf('%s%s', isset($lines[$ip]) ? $lines[$ip].' ' : '', implode(' ', $hosts));
            }
        }

        array_walk($lines, function (&$host, $ip) {
            $host = $ip.' '.$host;
        });

        return $lines;
    }

    /**
     * @param array $container
     *
     * @return string
     */
    private function getProjectName($container)
    {
        if (isset($container['Config']['Labels']['com.docker.compose.project'])) {
            return $container['Config']['Labels']['com.docker.compose.project'];
        }

        return '';
    }

    /**
     * @param array $container
     *
     * @return array
     */
    private function getContainerHosts($container)
    {
        return array_merge(
            [substr($container['Name'], 1).$this->tld],
            $this->getAdditionalHosts($container)
        );
    }

    /**
     * @param array $container
     *
     * @return array
     */
    private function getAdditionalContainerHosts($container)
    {
        $hosts = [];

        $domainName = $this->getEnv($container, 'DOMAIN_NAME');
        if ($domainName) {
            $hosts = array_merge($hosts, explode(',', $domainName));
        }

        return $hosts;
    }

    /**
     * @param array  $container
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    private function getEnv($container, $name, $default = null)
    {
        if (isset($container['Config']['Env']) && is_array($container['Config']['Env'])) {
            foreach ($container['Config']['Env'] as $env) {
                if (preg_match(sprintf('/^%s=(.*)$/', $name), $env, $matches)) {
                    return $matches[1];
                }
            }
        }

        return $default;
    }

    /**
     * @param Container $container
     *
     * @return bool
     */
    private function isExposed($container)
    {
        if (empty($container['NetworkSettings']['Ports']) || empty($container['State']['Running'])) {
            return false;
        }

        return $container['State']['Running'];
    }
}
