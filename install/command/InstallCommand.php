<?php

namespace SymphonyCms\Installer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use SymphonyCms\Installer\Lib\ConsoleInstaller;
use Configuration;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install Symphony on this server.')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to Symphony configuration file.')
            ->addOption('override', 'o', InputOption::VALUE_NONE, 'Whether the Installer shall override an existing Symphony install.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Be really sure..
        if ($input->getOption('override') && !$input->getOption('no-interaction')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to overwrite the existing Symphony installation?', false);

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Skipping Symphony installation</comment>');
                return 0;
            } else {
                $output->writeln('<info>Overwriting existing Symphony installation</info>');
            }
        }

        // First, check the requirements.
        $command = $this->getApplication()->find('requirements');
        $requirementsInput = new ArrayInput([]);
        if ($command->run($requirementsInput, $output) !== 0) {
            return 1;
        }

        // Verify Configuration File is readable
        $configFile = $input->getArgument('config');
        if (!is_readable($configFile)) {
            $output->writeln('<error>Unable to install Symphony, configuration file is unreadable.</error>');
            return 1;
        } else {
            $config = new Configuration();
            $config->setArray(require_once $configFile);
        }

        $logger = new ConsoleLogger($output);
        $installer = new ConsoleInstaller($logger, $config);
        $installer->setOverride($input->getOption('override'));

        // TODO: Ask the user.
        $data = [
            'user' => [
                'firstname' => 'Test',
                'lastname' => 'User',
                'username' => 'symphony',
                'password' => 'testing',
                'email' => 'team@getsymphony.com'
            ]
        ];

        if (!$installer->install($data)) {
            $output->writeln('<error>Symphony has been installed as there was an error.</error>');
            return 1;
        }

        $output->writeln('<info>Symphony has been installed successfully.</info>');
        return 0;
    }
}

return __NAMESPACE__;
