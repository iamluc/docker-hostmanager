<?php

namespace DockerHostManager\Command;

use DockerHostManager\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('synchronize-hosts')
            ->setDescription('Run the application')
            ->addArgument(
                'entrypoint',
                InputArgument::OPTIONAL,
                'The docker entrypoint',
                getenv('DOCKER_ENTRYPOINT') ?: 'unix:///var/run/docker.sock'
            )
            ->addArgument(
                'hosts_file',
                InputArgument::OPTIONAL,
                'The host file to update',
                getenv('HOSTS_FILE') ?: '/etc/hosts'
            )
            ->addArgument(
                'tld',
                InputArgument::OPTIONAL,
                'The TLD to use',
                getenv('TLD') ?: '.docker'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = new Application(
            $input->getArgument('entrypoint'),
            $input->getArgument('hosts_file'),
            $input->getArgument('tld')
        );

        $app->run();
    }
}
