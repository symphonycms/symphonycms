<?php

    Class Updater extends Installer
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
            if(!(self::$_instance instanceof Updater)) {
                self::$_instance = new Updater;
            }

            return self::$_instance;
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
        public static function initialiseConfiguration(array $data = array())
        {
            parent::initialiseConfiguration();
        }

        /**
         * Overrides the `initialiseLog()` method and writes
         * logs to manifest/logs/update
         */
        public static function initialiseLog($filename = null)
        {
            if(is_dir(INSTALL_LOGS) || General::realiseDirectory(INSTALL_LOGS, self::Configuration()->get('write_mode', 'directory'))) {
                parent::initialiseLog(INSTALL_LOGS . '/update');
            }
        }

        /**
         * Overrides the default `initialiseDatabase()` method
         * This allows us to still use the normal accessor
         */
        public static function initialiseDatabase()
        {
            parent::setDatabase();

            $details = Symphony::Configuration()->get('database');

            try{
                Symphony::Database()->connect(
                    $details['host'],
                    $details['user'],
                    $details['password'],
                    $details['port'],
                    $details['db']
                );
            }
            catch(DatabaseException $e){
                self::__abort(
                    'There was a problem while trying to establish a connection to the MySQL server. Please check your settings.',
                    time()
                );
            }

            // MySQL: Setting prefix & character encoding
            Symphony::Database()->setPrefix($details['tbl_prefix']);
            Symphony::Database()->setCharacterEncoding();
            Symphony::Database()->setCharacterSet();
        }

        public function run()
        {
            // Initialize log
            if(is_null(Symphony::Log()) || !file_exists(Symphony::Log()->getLogPath())) {
                self::__render(new UpdaterPage('missing-log'));
            }

            // Get available migrations. This will only contain the migrations
            // that are applicable to the current install.
            $migrations = array();

            foreach(new DirectoryIterator(INSTALL . '/migrations') as $m){
                if($m->isDot() || $m->isDir() || General::getExtension($m->getFilename()) !== 'php') continue;

                $version = str_replace('.php', '', $m->getFilename());

                // Include migration so we can see what the version is
                include_once($m->getPathname());
                $classname = 'migration_' . str_replace('.', '', $version);

                $m = new $classname();

                if(version_compare(Symphony::Configuration()->get('version', 'symphony'), call_user_func(array($m, 'getVersion')), '<')){
                    $migrations[call_user_func(array($m, 'getVersion'))] = $m;
                }
            }

            // The DirectoryIterator may return files in a sporatic order
            // on different servers. This will ensure the array is sorted
            // correctly using `version_compare`
            uksort($migrations, 'version_compare');

            // If there are no applicable migrations then this is up to date
            if(empty($migrations)) {
                Symphony::Log()->pushToLog(
                    sprintf('Updater - Already up-to-date'),
                    E_ERROR, true
                );

                self::__render(new UpdaterPage('uptodate'));
            }

            // Show start page
            else if(!isset($_POST['action']['update'])) {
                $notes = array();

                // Loop over all available migrations showing there
                // pre update notes.
                foreach($migrations as $version => $m){
                    $n = call_user_func(array($m, 'preUpdateNotes'));
                    if(!empty($n)) $notes[$version] = $n;
                }

                // Show the update ready page, which will display the
                // version and release notes of the most recent migration
                self::__render(new UpdaterPage('ready', array(
                    'pre-notes' => $notes,
                    'version' => call_user_func(array($m, 'getVersion')),
                    'release-notes' => call_user_func(array($m, 'getReleaseNotes'))
                )));
            }

            // Upgrade Symphony
            else {
                $notes = array();
                $canProceed = true;

                // Loop over all the available migrations incrementally applying
                // the upgrades. If any upgrade throws an uncaught exception or
                // returns false, this will break and the failure page shown
                foreach($migrations as $version => $m){
                    $n = call_user_func(array($m, 'postUpdateNotes'));
                    if(!empty($n)) $notes[$version] = $n;

                    $canProceed = call_user_func(array($m, 'run'), 'upgrade', Symphony::Configuration()->get('version', 'symphony'));

                    Symphony::Log()->pushToLog(
                        sprintf('Updater - Migration to %s was %s', $version, $canProceed ? 'successful' : 'unsuccessful'),
                        E_NOTICE, true
                    );

                    if(!$canProceed) break;
                }

                if(!$canProceed){
                    self::__render(new UpdaterPage('failure'));
                }
                else {
                    self::__render(new UpdaterPage('success', array(
                        'post-notes' => $notes,
                        'version' => call_user_func(array($m, 'getVersion')),
                        'release-notes' => call_user_func(array($m, 'getReleaseNotes'))
                    )));
                }
            }

        }

    }

