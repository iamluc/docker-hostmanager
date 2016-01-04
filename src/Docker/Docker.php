<?php

namespace DockerHostManager\Docker;

use Docker\Docker as DockerBase;

class Docker extends DockerBase
{
    /**
     * @param callable $callback
     */
    public function listenEvents(callable $callback)
    {
        $stream = $this->getHttpClient()->get('/events');

        while (!$stream->getBody()->eof()) {
            $line = \GuzzleHttp\Stream\read_line($stream->getBody());
            if (null !== ($raw = json_decode($line, true))) {
                call_user_func($callback, new Event($raw));
            }
        }
    }
}
