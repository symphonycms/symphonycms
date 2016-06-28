<?php

namespace SymphonyCms\Installer\Lib;

use Configuration;
use DateTimeObj;
use Exception;
use Lang;
use Psr\Log\LoggerInterface;
use Symphony;
use SymphonyCms\Installer\Steps;

class ConsoleInstaller
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var bool
     */
    protected $overwrite = false;

    /**
     * @param LoggerInterface $logger
     * @param Configuration $configuration
     */
    public function __construct(LoggerInterface $logger, Configuration $configuration)
    {
        $this->logger = $logger;
        $this->configuration = $configuration;

        // Symphony expects a couple of constants to always exist, so lets do a basic bootstrap.
        $this->bootstrap();
    }

    /**
     * Allow the Installer and it's steps to attempt to install Symphony at all costs,
     * including removing and deleting existing data. Use with caution.
     *
     * @param bool $overwrite
     * @return $this
     */
    public function setOverride($overwrite = false)
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Installs Symphony using the configuration information available.
     *
     * @param array $data
     * @return bool
     */
    public function install(array $data)
    {
        $steps = [
            // Create database
            Steps\CreateDatabase::class,
            // Create manifest folder structure
            Steps\CreateManifest::class,
            // Write .htaccess
            Steps\CreateHtaccess::class,
            // Create or import the workspace
            Steps\Workspace::class,
            // Enable extensions
            Steps\EnableExtensions::class
        ];

        try {
            foreach ($steps as $step) {
                $installStep = new $step($this->logger);
                $installStep->setOverride($this->overwrite);

                if (false === $installStep->handle($this->configuration, $data)) {
                    throw new Exception(sprintf('Aborting installation, %s step failed.', $step));
                }
            }
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return false;
        }

        if (false === Symphony::Configuration()->write(CONFIG, $this->configuration->get('write_mode', 'file'))) {
            $this->logger->error('Could not create config file ‘' . CONFIG . '’. Check permission on /manifest.');
            return false;
        }

        $this->logger->info('Symphony has been installed.');
        return true;
    }

    /**
     * Bootstrap the environment to the bare minimum for Symphony to 'run'.
     */
    private function bootstrap()
    {
        // Initialize date/time
        define_safe('__SYM_DATE_FORMAT__', $this->configuration->get('date_format', 'region'));
        define_safe('__SYM_TIME_FORMAT__', $this->configuration->get('time_format', 'region'));
        define_safe(
            '__SYM_DATETIME_FORMAT__',
            __SYM_DATE_FORMAT__ . $this->configuration->get('datetime_separator', 'region') . __SYM_TIME_FORMAT__
        );
        DateTimeObj::setSettings($this->configuration->get('region'));

        Lang::initialize();
        Symphony::setDatabase();
        Symphony::initialiseExtensionManager();
        Symphony::initialiseConfiguration($this->configuration->get());

        define('INSTALL', DOCROOT . '/install');
        define('INSTALL_LOGS', MANIFEST . '/logs');
        define('INSTALL_URL', URL . '/install');
    }
}
