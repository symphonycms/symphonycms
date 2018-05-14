<?php

/**
 * @package install
 */
class Updater extends Installer
{
    /**
     * This function returns an instance of the Updater
     * class. It is the only way to create a new Updater, as
     * it implements the Singleton interface
     *
     * @return Updater
     */
    public static function instance()
    {
        if (!(self::$_instance instanceof Updater)) {
            self::$_instance = new Updater;
        }

        return self::$_instance;
    }

    /**
     * Get available migrations. This will only contain the migrations
     * that are applicable to the current install.
     *
     * @param string $version
     *  The Symphony version to use in order to find the available migration.
     *  If empty, it will default to the current Symphony version.
     * @return array
     *  All available migration objects
     */
    public static function getAvailableMigrations($version = null)
    {
        $migrations = [];
        if (!$version) {
            $version = Symphony::Configuration()->get('version', 'symphony');
        }
        $vc = new VersionComparator($version);

        if (!@file_exists(INSTALL . '/migrations')) {
            return $migrations;
        }

        foreach (new DirectoryIterator(INSTALL . '/migrations') as $m) {
            if ($m->isDot() || $m->isDir() || General::getExtension($m->getFilename()) !== 'php') {
                continue;
            }

            $migrationVersion = str_replace('.php', '', $m->getFilename());

            // Include migration so we can see what the actual version is
            // by creating the Migration instance
            require_once($m->getPathname());
            $className = 'migration_' . str_replace('.', '', $migrationVersion);
            $m = new $className($version);

            if ($vc->lessThan($m->getVersion())) {
                $migrations[$m->getVersion()] = $m;
            }
        }

        // The DirectoryIterator may return files in a sporadic order
        // on different servers. This will ensure the array is sorted
        // correctly as per the semver spec.
        uksort($migrations, ['VersionComparator', 'compare']);

        return $migrations;
    }

    /**
     * Initialises the language by looking at the existing
     * configuration
     */
    public static function initialiseLang()
    {
        Lang::set(Symphony::Configuration()->get('lang', 'symphony'), false);
    }

    /**
     * Initialises the configuration object by loading the existing
     * website config file
     */
    public static function initialiseConfiguration(array $data = [])
    {
        parent::initialiseConfiguration();
    }

    /**
     * Overrides the `initialiseLog()` method and writes
     * logs to manifest/logs/update
     */
    public static function initialiseLog($filename = null)
    {
        $w = self::Configuration()->get('write_mode', 'directory');
        if (is_dir(INSTALL_LOGS) || General::realiseDirectory(INSTALL_LOGS, $w)) {
            parent::initialiseLog(INSTALL_LOGS . '/update');
        }
    }

    /**
     * Overrides the default `initialiseDatabase()` method
     * This allows us to still use the normal accessor
     */
    public static function initialiseDatabase()
    {
        Symphony::initialiseDatabase();
    }

    public function run()
    {
        $currentSymphony = Symphony::Configuration()->get('version', 'symphony');
        $vc = new VersionComparator($currentSymphony);

        // Initialize log
        if (is_null(Symphony::Log()) || !file_exists(Symphony::Log()->getLogPath())) {
            $this->render(new UpdaterPage('missing-log'));
        }

        $migrations = static::getAvailableMigrations($currentSymphony);

        // If there are no applicable migrations then this is up to date
        if (empty($migrations)) {
            Symphony::Log()->pushToLog(
                'Updater - Already up-to-date',
                E_ERROR,
                true
            );

            $this->render(new UpdaterPage('uptodate'));
        } elseif ($vc->lessThan('2.7.0')) {
            Symphony::Log()->pushToLog(
                'Updater - Can not update',
                E_ERROR,
                true
            );

            $this->render(new UpdaterPage('noupdate'));

        // Show start page
        } elseif (!isset($_POST['action']['update'])) {
            $notes = [];

            // Loop over all available migrations showing there
            // pre update notes.
            foreach ($migrations as $version => $m) {
                $n = $m->preUpdateNotes();
                if (!empty($n)) {
                    $notes[$version] = $n;
                }
            }

            // Show the update ready page, which will display the
            // version and release notes of the most recent migration
            $this->render(new UpdaterPage('ready', array(
                'pre-notes' => $notes,
                'version' => $m->getVersion(),
                'release-notes' => $m->getReleaseNotes(),
            )));

        // Upgrade Symphony
        } else {
            $notes = [];

            // Loop over all the available migrations incrementally applying
            // the upgrades. If any upgrade throws an uncaught exception or
            // returns false, this will break and the failure page shown
            foreach ($migrations as $version => &$m) {
                $canProceed = $m->run('upgrade');

                Symphony::Log()->pushToLog(
                    sprintf(
                        'Updater - Migration to %s was %s',
                        $version,
                        $canProceed ? 'successful' : 'unsuccessful'
                    ),
                    E_NOTICE,
                    true
                );

                $n = $m->postUpdateNotes();
                if (!empty($n)) {
                    $notes[$version] = $n;
                }

                if (!$canProceed) {
                    break;
                }
            }

            if (!$canProceed) {
                $this->render(new UpdaterPage('failure'));
            } else {
                $this->render(new UpdaterPage('success', array(
                    'post-notes' => $notes,
                    'version' => $m->getVersion(),
                    'release-notes' => $m->getReleaseNotes(),
                )));
            }
        }
    }
}
