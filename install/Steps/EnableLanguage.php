<?php
namespace SymphonyCms\Installer\Steps;

use Configuration;
use ExtensionManager;
use Lang;

class EnableLanguage extends DefaultStep
{
    /**
     * {@inheritdoc}
     */
    public function handle(Configuration $config, array $data)
    {
        // Loading default language
        if (isset($_REQUEST['lang']) && $_REQUEST['lang'] !== 'en') {
            $this->logger->info('CONFIGURING: Default language');

            $language = Lang::Languages();
            $language = $language[$_REQUEST['lang']];

            // Is the language extension enabled?
            if (in_array('lang_' . $language['handle'], ExtensionManager::listInstalledHandles())) {
                $config->set('lang', $_REQUEST['lang'], 'symphony');
            } else {
                $this->logger->warning('Could not enable the desired language ‘' . $language['name'] . '’.');
                return false;
            }
        }

        return true;
    }
}
