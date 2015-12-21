<?php

namespace DockerHostManager\Docker;

class Event
{
    /** @var string */
    protected $status;

    /** @var string */
    protected $id;

    /** @var string */
    protected $from;

    /** @var string */
    protected $time;

    /**
     * @param string $raw
     */
    public function __construct($raw)
    {
        $this->status = $raw['status'];
        $this->id = $raw['id'];
        // eg. pull events do not contain 'from'
        if (isset($raw['from'])) {
            $this->from = $raw['from'];
        }
        $this->time = $raw['time'];
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

}
