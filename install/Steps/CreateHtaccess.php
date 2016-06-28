<?php
namespace SymphonyCms\Installer\Steps;

use Configuration;
use Exception;
use General;

class CreateHtaccess extends DefaultStep
{
    /**
     * {@inheritdoc}
     */
    public function handle(Configuration $config, array $data)
    {
        // Writing htaccess file
        $this->logger->info('CONFIGURING: Apache Rewrite Rules');

        if (APP_MODE === 'console') {
            $rewrite_base = basename(DOCROOT);
        } else {
            $rewrite_base = ltrim(preg_replace('/\/install$/i', null, dirname($_SERVER['PHP_SELF'])), '/');
        }

        $htaccess = str_replace(
            '<!-- REWRITE_BASE -->',
            $rewrite_base,
            file_get_contents(INSTALL . '/includes/htaccess.txt')
        );

        // If the step can override, replace the entire .htaccess file.
        if (file_exists(DOCROOT . "/.htaccess") && $this->override) {
            $this->logger->info('CONFIGURING: Replacing existing .htaccess file');
            $file_mode = 'w';
        } else {
            $file_mode = 'a';
        }

        if (!General::writeFile(DOCROOT . "/.htaccess", $htaccess, $config->get('write_mode', 'file'), $file_mode)) {
            throw new Exception('Could not write ‘.htaccess’ file. Check permission on ' . DOCROOT);
        }

        return true;
    }
}
