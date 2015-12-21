<?php

namespace DockerHostManager;

use Docker\Container;
use Docker\Docker;
use Docker\Exception\ContainerNotFoundException;
use Docker\Http\DockerClient;
use Docker\Manager\ContainerManager;

class Application
{
    const START_TAG = '## docker-hostmanager-start';
    const END_TAG = '## docker-hostmanager-end';

    /** @var string */
    private $entrypoint;
    /** @var string */
    private $hostsFile;

    /** @var DockerClient */
    private $client;
    /** @var ContainerManager  */
    private $containerManager;

    /** @var array Container */
    private $activeContainers = [];

    /**
     * @param string $entrypoint
     * @param string $hostsFile
     */
    public function __construct($entrypoint, $hostsFile, $tld)
    {
        $this->entrypoint = $entrypoint;
        $this->hostsFile = $hostsFile;

        $this->client = new DockerClient([], $this->entrypoint);
        $this->containerManager = (new Docker($this->client))->getContainerManager();
    }

    public function run()
    {
        $this->init();
        $this->listen();
    }

    private function init()
    {
        $this->activeContainers = array_filter($this->containerManager->findAll(), function (Container $container) {
            return $this->isExposed($container);
        });

        $this->write();
    }

    private function listen()
    {
        $stream = stream_socket_client($this->entrypoint, $errno, $errstr, 30);
        fputs($stream, "GET /events HTTP/1.0\r\nHost: $this->entrypoint\r\nAccept: */*\r\n\r\n");
        while (!feof($stream)) {
            if (null !== ($response = json_decode(fgets($stream)))) {
                $container = $this->containerManager->find($response->id);
                if ($this->isExposed($container)) {
                    $this->activeContainers[] = $container;
                } else {
                    $this->activeContainers = array_filter($this->activeContainers, function (Container $c) use ($container) {
                        return $c->getId() !== $container->getId();
                    });
                }
                $this->write();
            }
        }
        fclose($stream);
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
                    return $this->getHost($container);
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
     * @return string
     *
     * @throws ContainerNotFoundException
     */
    private function getHost(Container $container)
    {
        $this->containerManager->inspect($container);
        $infos = $container->getRuntimeInformations();

        $ip = $infos['NetworkSettings']['IPAddress'];
        $host = substr($container->getName(), 1);

        return "$ip $host";
    }

    /**
     * @param Container $container
     *
     * @return bool
     */
    private function isExposed(Container $container)
    {
        $this->containerManager->inspect($container);
        $infos = $container->getRuntimeInformations();

        if (empty($infos['NetworkSettings']['Ports'])) {
            return false;
        }

        if (!isset($infos['NetworkSettings']['IPAddress']) || !isset($infos['State']['Running'])) {
            return false;
        }

        return $infos['State']['Running'];
    }
}
