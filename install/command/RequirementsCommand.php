<?php

    namespace SymphonyCms\Installer\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use SymphonyCms\Installer\Lib\Requirements;

    class RequirementsCommmand extends Command
    {
        protected function configure()
        {
            $this
                ->setName('check')
                ->setDescription('Check if this server is capable of installing Symphony.')
            ;
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $requirements = new Requirements();
            $errors = $requirements->check();

            if (empty($errors)) {
                $output->writeln('All good, the show goes on');
            } else {
                foreach ($errors as $error) {
                    $output->writeln($error['msg'] . ' ' . $error['details']);
                }
            }
        }
    }
