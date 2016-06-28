<?php
namespace SymphonyCms\Installer\Steps;

use Author;
use Configuration;
use Cryptography;
use DatabaseException;
use Exception;
use Symphony;

class CreateDatabase extends DefaultStep
{
    /**
     * {@inheritdoc}
     */
    public function handle(Configuration $config, array $data)
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

        if (Symphony::Database()->tableExists($config->get('tbl_prefix', 'database') . '%') && !$this->override) {
            $this->logger->error('MYSQL: Database table prefix is already in use. Change prefix or run installation with the `--override` flag.', [
                'prefix' => $config->get('tbl_prefix', 'database'),
                'db' => $config->get('db', 'database')
            ]);

            return false;
        }

        // MySQL: Setting prefix & importing schema
        Symphony::Database()->setPrefix($config->get('tbl_prefix', 'database'));
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
        if (isset($data['user'])) {
            $this->logger->info('MYSQL: Creating Default Author');

            try {
                // Clean all the user data.
                $userData = array_map([Symphony::Database(), 'cleanValue'], $data['user']);

                $author = new Author;
                $author->set('user_type', 'developer');
                $author->set('primary', 'yes');
                $author->set('username', $userData['username']);
                $author->set('password', Cryptography::hash($userData['password']));
                $author->set('first_name', $userData['firstname']);
                $author->set('last_name', $userData['lastname']);
                $author->set('email', $userData['email']);
                $author->commit();
            } catch (DatabaseException $e) {
                throw new Exception(sprintf(
                    'There was an error while trying create the default author. MySQL returned: %s:%s',
                    $e->getDatabaseErrorCode(),
                    $e->getDatabaseErrorMessage()
                ));
            }
        } else {
            $this->logger->info('MYSQL: Skipping Default Author creation');
        }

        return true;
    }
}
