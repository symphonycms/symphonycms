<?php

    use \Psr\Log\LoggerInterface;

    class CreateWorkspace implements Step
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
        public function getWorkspaceDirectories()
        {
            return [
                'workspace'     => WORKSPACE,
                'data-sources'  => DATASOURCES,
                'events'        => EVENTS,
                'pages'         => PAGES,
                'utilities'     => UTILITIES
            ];
        }

        /**
         * {@inheritdoc}
         */
        public function handle(Configuration $config)
        {
            foreach ($this->getWorkspaceDirectories() as $name => $dir) {
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
