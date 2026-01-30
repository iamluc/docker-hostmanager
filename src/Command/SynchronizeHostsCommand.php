<?php

namespace DockerHostManager\Command;

use Docker\DockerClient;
use DockerHostManager\Docker\Docker;
use DockerHostManager\Synchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeHostsCommand extends Command
{
    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = new Synchronizer(
            new Docker(DockerClient::create()),
            $input->getOption('hosts_file'),
            $input->getOption('tld')
        );

        $app->run();
        
        return self::SUCCESS;
    }
}
