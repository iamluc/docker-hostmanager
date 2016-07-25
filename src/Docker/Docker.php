<?php

namespace DockerHostManager\Docker;

use Docker\Docker as DockerBase;
use Docker\DockerClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Symfony\Component\Serializer\Serializer;

class Docker extends DockerBase
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    public function __construct(HttpClient $httpClient = null, Serializer $serializer = null, MessageFactory $messageFactory = null)
    {
        $this->httpClient = $httpClient ?: DockerClient::createFromEnv();
        $this->messageFactory = $messageFactory ?: new GuzzleMessageFactory();

        parent::__construct($this->httpClient, $serializer, $this->messageFactory);
    }

    /**
     * @param callable $callback
     */
    public function listenEvents(callable $callback)
    {
        $request = $this->messageFactory->createRequest('GET', '/events');
        $response = $this->httpClient->sendRequest($request);

        $stream = $response->getBody();
        while (!$stream->eof()) {
            $line = \GuzzleHttp\Psr7\readline($stream);
            if (null !== ($raw = json_decode($line, true))) {
                call_user_func($callback, new Event($raw));
            }
        }
    }
}
