<?php

namespace DockerHostManager\Command;

use Docker\Http\DockerClient;
use DockerHostManager\Docker\Docker;
use DockerHostManager\Synchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeHostsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('synchronize-hosts')
            ->setDescription('Run the application')
            ->addOption(
                'hosts_file',
                'f',
                InputOption::VALUE_REQUIRED,
                'The host file to update',
                getenv('HOSTS_FILE') ?: '/etc/hosts'
            )
            ->addOption(
                'tld',
                't',
                InputOption::VALUE_REQUIRED,
                'The TLD to use',
                getenv('TLD') ?: '.docker'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = new Synchronizer(
            new Docker($this->createDockerClient()),
            $input->getOption('hosts_file'),
            $input->getOption('tld')
        );

        $app->run();
    }

    /**
     * Copy of https://github.com/hxpro/docker-php/blob/master/src/Docker/Http/DockerClient.php
     * + disable peer_name check.
     *
     * @return DockerClient
     */
    private function createDockerClient()
    {
        $entrypoint = getenv('DOCKER_HOST') ? getenv('DOCKER_HOST') : 'unix:///var/run/docker.sock';
        $context = null;
        $useTls = false;
        if (getenv('DOCKER_TLS_VERIFY') && getenv('DOCKER_TLS_VERIFY') == 1) {
            if (!getenv('DOCKER_CERT_PATH')) {
                throw new \RuntimeException('Connection to docker has been set to use TLS, but no PATH is defined for certificate in DOCKER_CERT_PATH docker environment variable');
            }
            $useTls = true;
            $cafile = getenv('DOCKER_CERT_PATH').DIRECTORY_SEPARATOR.'ca.pem';
            $certfile = getenv('DOCKER_CERT_PATH').DIRECTORY_SEPARATOR.'cert.pem';
            $keyfile = getenv('DOCKER_CERT_PATH').DIRECTORY_SEPARATOR.'key.pem';
            $context = stream_context_create([
                'ssl' => [
                    'cafile' => $cafile,
                    'local_cert' => $certfile,
                    'local_pk' => $keyfile,
                ],
            ]);
        }

        return new DockerClient([], $entrypoint, $context, $useTls);
    }
}
