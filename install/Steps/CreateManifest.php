<?php
    namespace SymphonyCms\Installer\Steps;

    use Configuration;
    use Exception;
    use General;
    use Psr\Log\LoggerInterface;

    class CreateManifest implements Step
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
         * Return the directories that should be created.
         *
         * @return array
         */
        public function getManifestDirectories()
        {
            return [
                'manifest' => MANIFEST,
                'logs'     => MANIFEST . '/logs',
                'cache'    => MANIFEST . '/cache',
                'tmp'      => MANIFEST . '/tmp'
            ];
        }

        /**
         * {@inheritdoc}
         */
        public function handle(Configuration $config, array $data)
        {
            foreach ($this->getManifestDirectories() as $name => $dir) {
                $this->logger->info(sprintf(
                    'WRITING: Creating ‘%s‘ folder',
                    $name
                ));

                if (!General::realiseDirectory($dir, $config->get('write_mode', 'directory'))) {
                    throw new Exception(sprintf(
                        'Could not create ‘%s’ directory. Check permission on the root folder.',
                        $name
                    ));
                }
            }

            return true;
        }
    }
