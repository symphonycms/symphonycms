<?php
namespace SymphonyCms\Installer\Steps;

use Configuration;
use Exception;
use General;

class CreateManifest extends DefaultStep
{
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
            if (is_dir($dir)) {
                if ($this->override) {
                    $this->logger->info(sprintf(
                        'REMOVING: Deleting ‘%s‘ folder',
                        $name
                    ));

                    General::deleteDirectory($dir);
                } else {
                    $this->logger->info(sprintf(
                        'SKIPPING: `%s` folder exists',
                        $name
                    ));

                    continue;
                }
            }

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
