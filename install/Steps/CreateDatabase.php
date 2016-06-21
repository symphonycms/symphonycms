<?php

    use \Psr\Log\LoggerInterface;

    class CreateDatabase implements Step
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
            // MySQL: Establishing connection
            $this->logger->info('MYSQL: Establishing Connection');

            try {
                Symphony::Database()->connect(
                    $config->get('host', 'database'),
                    $config->get('user', 'database'),
                    $config->get('password', 'database'),
                    $config->get('port', 'database'),
                    $config->get('db', 'database')
                );
            } catch (DatabaseException $e) {
                throw new Exception(
                    'There was a problem while trying to establish a connection to the MySQL server. Please check your settings.'
                );
            }

            // MySQL: Setting prefix & character encoding
            Symphony::Database()->setPrefix($config->get('tbl_prefix', 'database'));

            // MySQL: Importing schema
            $this->logger->info('MYSQL: Importing Table Schema');

            try {
                Symphony::Database()->import(file_get_contents(INSTALL . '/includes/install.sql'));
            } catch (DatabaseException $e) {
                throw new Exception(sprintf(
                    'There was an error while trying to import data to the database. MySQL returned: %s:%s',
                    $e->getDatabaseErrorCode(),
                    $e->getDatabaseErrorMessage()
                ));
            }

            // MySQL: Creating default author
            $this->logger->info('MYSQL: Creating Default Author');

            try {
                Symphony::Database()->insert(array(
                    'id' => 1,
                    'username' => Symphony::Database()->cleanValue($_POST['fields']['user']['username']),
                    'password' => Cryptography::hash(Symphony::Database()->cleanValue($_POST['fields']['user']['password'])),
                    'first_name' => Symphony::Database()->cleanValue($_POST['fields']['user']['firstname']),
                    'last_name' => Symphony::Database()->cleanValue($_POST['fields']['user']['lastname']),
                    'email' => Symphony::Database()->cleanValue($_POST['fields']['user']['email']),
                    'last_seen' => null,
                    'user_type' => 'developer',
                    'primary' => 'yes',
                    'default_area' => null,
                    'auth_token_active' => 'no'
                ), 'tbl_authors');
            } catch (DatabaseException $e) {
                throw new Exception(sprintf(
                    'There was an error while trying create the default author. MySQL returned: %s:%s',
                    $e->getDatabaseErrorCode(),
                    $e->getDatabaseErrorMessage()
                ));
            }

            return true;
        }
    }
