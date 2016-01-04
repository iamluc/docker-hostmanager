<?php

namespace Test\DockerHostManager;

use Docker\Docker;
use Docker\Http\DockerClient;
use DockerHostManager\Application;
use Test\Utils\PropertyAccessor;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testThatAppCanBeConstructed()
    {
        $application = new Application('unix:///var/run/docker.sock', '/etc/hosts', 'docker');

        $this->assertSame('unix:///var/run/docker.sock', PropertyAccessor::getProperty($application, 'entrypoint'));
        $this->assertSame('/etc/hosts', PropertyAccessor::getProperty($application, 'hostsFile'));
        $this->assertSame('docker', PropertyAccessor::getProperty($application, 'tld'));
        $this->assertInstanceOf(Docker::class, PropertyAccessor::getProperty($application, 'docker'));
        $this->assertInternalType('array', PropertyAccessor::getProperty($application, 'activeContainers'));
    }
}
