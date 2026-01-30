<?php

namespace DockerHostManager\Docker;

use Docker\API\Model\EventMessage;
use Docker\DockerClient;

class Docker
{
    public function __construct(private DockerClient $client)
    {
    }
    
    /**
     * @param callable $callback
     */
    public function listenEvents(callable $callback)
    {
        $stream = $this->client->systemEvents();
        $stream->onFrame(fn(EventMessage $message) => call_user_func($callback, Event::fromEventMessage($message)));
        $stream->wait();
    }
}
