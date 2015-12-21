<?php

namespace DockerHostManager\Docker;

use Docker\Docker as DockerBase;

class Docker extends DockerBase
{
    /**
     * @param string   $entrypoint
     * @param callable $callback
     */
    public function listenEvents($entrypoint, callable $callback)
    {
        $stream = stream_socket_client($entrypoint, $errno, $errstr, 30);
        fputs($stream, "GET /events HTTP/1.0\r\nHost: $entrypoint\r\nAccept: */*\r\n\r\n\"");
        while (!feof($stream)) {
            if (null !== ($raw = json_decode(fgets($stream), true))) {
                call_user_func($callback, new Event($raw));
            }
        }
        fclose($stream);
    }
}
