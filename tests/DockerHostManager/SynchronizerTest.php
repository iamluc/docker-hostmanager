<?php

namespace Test\DockerHostManager;

use Docker\Docker;
use DockerHostManager\Synchronizer;
use PHPUnit\Framework\TestCase;
use Test\Utils\PropertyAccessor;

class SynchronizerTest extends TestCase
{
    public function testThatAppCanBeConstructed()
    {
        $docker = $this->prophesize('DockerHostManager\Docker\Docker');
        $docker = $docker->reveal();

        $application = new Synchronizer($docker, '/etc/hosts', 'docker');

        $this->assertSame($docker, PropertyAccessor::getProperty($application, 'docker'));
        $this->assertSame('/etc/hosts', PropertyAccessor::getProperty($application, 'hostsFile'));
        $this->assertSame('docker', PropertyAccessor::getProperty($application, 'tld'));
        $this->assertInstanceOf(Docker::class, PropertyAccessor::getProperty($application, 'docker'));
        $this->assertInternalType('array', PropertyAccessor::getProperty($application, 'activeContainers'));
    }
}
