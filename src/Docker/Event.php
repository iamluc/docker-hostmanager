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
        $this->status = isset($raw['status']) ? $raw['status'] : null;
        $this->id = isset($raw['id']) ? $raw['id'] : null;
        $this->from = isset($raw['from']) ? $raw['from'] : null;
        $this->time = isset($raw['time']) ? $raw['time'] : null;
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
