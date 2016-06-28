<?php
namespace SymphonyCms\Installer\Steps;

use Configuration;

class Workspace extends DefaultStep
{
    /**
     * {@inheritdoc}
     */
    public function handle(Configuration $config, array $data)
    {
        if (!is_dir(WORKSPACE)) {
            return (new CreateWorkspace($this->logger))->handle($config, $data);
        } else {
            return (new ImportWorkspace($this->logger))->handle($config, $data);
        }
    }
}
