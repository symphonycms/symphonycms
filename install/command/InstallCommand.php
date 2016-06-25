<?php

    namespace SymphonyCms\Installer\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class InstallCommmand extends Command
    {
        protected function configure()
        {
            $this
                ->setName('install')
                ->setDescription('Install Symphony given a configuration file');
            ;
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            // Make me work :)
        }
    }
