<?php

namespace SymphonyCms\Installer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SymphonyCms\Installer\Lib\Requirements;

class RequirementsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('requirements')
            ->setDescription('Check if this server is capable of installing Symphony.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $requirements = new Requirements();
        $errors = $requirements->check();

        if (!empty($errors)) {
            $output->writeln('<error>Fail.</error> Unfortunately this server does not support Symphony CMS:');
            foreach ($errors as $error) {
                $output->writeln($error['msg'] . ' ' . $error['details']);
            }

            return 1;
        }

        $output->writeln('<info>Pass.</info> The server supports the minimum requirements for Symphony CMS.');

        return 0;
    }
}

return __NAMESPACE__;
