<?php

    use \Psr\Log\LoggerInterface;

    class CreateHtaccess implements Step
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
        public function handle(Configuration $config)
        {
            // Writing htaccess file
            $this->logger->info('CONFIGURING: Frontend');

            $rewrite_base = ltrim(preg_replace('/\/install$/i', null, dirname($_SERVER['PHP_SELF'])), '/');
            $htaccess = str_replace(
                '<!-- REWRITE_BASE -->', $rewrite_base,
                file_get_contents(INSTALL . '/includes/htaccess.txt')
            );

            if (!General::writeFile(DOCROOT . "/.htaccess", $htaccess, $config->get('write_mode', 'directory'), 'a')) {
                throw new Exception('Could not write ‘.htaccess’ file. Check permission on ' . DOCROOT);
            }

            return true;
        }

    }
