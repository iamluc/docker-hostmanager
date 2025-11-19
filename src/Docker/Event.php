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
        $this->status = $raw['status'] ?? $raw['Action'] ?? null;
        $this->id = $raw['id'] ?? $raw['Actor']['ID'] ?? null;
        $this->from = $raw['from'] ?? $raw['Actor']['Attributes']['image'] ?? null;
        $this->time = $raw['time'] ?? null;
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
