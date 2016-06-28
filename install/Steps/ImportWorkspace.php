<?php
namespace SymphonyCms\Installer\Steps;

use Configuration;
use DatabaseException;
use Exception;
use Symphony;

class ImportWorkspace extends DefaultStep
{
    /**
     * {@inheritdoc}
     */
    public function handle(Configuration $config, array $data)
    {
        // MySQL: Importing workspace data
        $this->logger->info('MYSQL: Importing existing workspace data');

        if (is_file(WORKSPACE . '/install.sql')) {
            try {
                Symphony::Database()->import(file_get_contents(WORKSPACE . '/install.sql'));
            } catch (DatabaseException $e) {
                throw new Exception(sprintf(
                    'There was an error while trying to import data to the database. MySQL returned: %s:%s',
                    $e->getDatabaseErrorCode(),
                    $e->getDatabaseErrorMessage()
                ));
            }
        }

        return true;
    }
}
