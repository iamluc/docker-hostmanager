<?php

namespace DockerHostManager\Docker;

use Docker\API\Normalizer\NormalizerFactory;
use Docker\Docker as DockerBase;
use Docker\DockerClientFactory;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class Docker extends DockerBase
{
    public function __construct(HttpClient $httpClient = null, Serializer $serializer = null, MessageFactory $messageFactory = null)
    {
        $httpClient = $httpClient ?: DockerClientFactory::createFromEnv();
        $serializer = $serializer ?:  $serializer = new Serializer(
            NormalizerFactory::create(),
            [
                new JsonEncoder(
                    new JsonEncode(),
                    new JsonDecode()
                ),
            ]
        );
        $messageFactory = $messageFactory ?: new GuzzleMessageFactory();

        parent::__construct($httpClient, $messageFactory, $serializer);
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
