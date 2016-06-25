<?php
    namespace SymphonyCms\Installer\Steps;

    use \Psr\Log\LoggerInterface;
    use Configuration;

    class Workspace implements Step
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
            if (!is_dir(DOCROOT . '/workspace')) {
                return (new CreateWorkspace($this->logger))->handle($config, $data);
            } else {
                return (new ImportWorkspace($this->logger))->handle($config, $data);
            }
        }
    }
