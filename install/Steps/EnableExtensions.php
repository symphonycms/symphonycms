<?php
    namespace SymphonyCms\Installer\Steps;

    use \Psr\Log\LoggerInterface;
    use Configuration;
    use General;
    use Exception;
    use DirectoryIterator;
    use ExtensionManager;

    class EnableExtensions implements Step
    {
        /**
         * @var LoggerInterface
         */
        protected $logger;

        /**
         * CreateManifest constructor.
         *
         * @param LoggerInterface $logger
         */
        public function __construct(LoggerInterface $logger)
        {
            $this->logger = $logger;
        }

        /**
         * {@inheritdoc}
         */
        public function handle(Configuration $config, array $data)
        {
            // Write extensions folder
            if (!is_dir(EXTENSIONS)) {
                // Create extensions folder
                $this->logger->info('WRITING: Creating ‘extensions’ folder (/extensions)');
                if (!General::realiseDirectory(EXTENSIONS, $config->get('write_mode', 'directory'))) {
                    throw new Exception('Could not create ‘extension’ directory. Check permission on the root folder.');
                }

                return true;
            }

            // Install existing extensions
            $this->logger->info('CONFIGURING: Installing existing extensions');
            foreach (new DirectoryIterator(EXTENSIONS) as $e) {
                if ($e->isDot() || $e->isFile() || !is_file($e->getRealPath() . '/extension.driver.php')) {
                    continue;
                }

                $handle = $e->getBasename();
                try {
                    if (!ExtensionManager::enable($handle)) {
                        $this->logger->warning('Could not enable the extension ‘' . $handle . '’.');
                    }
                } catch (Exception $ex) {
                    $this->logger->warning(sprintf(
                        'Could not enable the extension ‘%s’. %s',
                        $handle,
                        $ex->getMessage()
                    ));
                }
            }

            return true;
        }
    }
