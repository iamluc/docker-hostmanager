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
                'entrypoint',
                'p',
                InputOption::VALUE_REQUIRED,
                'The docker entrypoint',
                getenv('DOCKER_ENTRYPOINT') ?: 'unix:///var/run/docker.sock'
            )
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
        $client = new DockerClient([], $input->getOption('entrypoint'));

        $app = new Synchronizer(
            new Docker($client),
            $input->getOption('hosts_file'),
            $input->getOption('tld')
        );

        $app->run();
    }
}
