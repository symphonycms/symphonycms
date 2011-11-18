<?php

	require_once(CORE . '/class.administration.php');

	require_once(INSTALL . '/lib/class.installer.php');
	require_once(INSTALL . '/lib/class.updaterpage.php');
	require_once(INSTALL . '/lib/class.migration.php');

	Class Updater extends Installer {

		/**
		 * This function returns an instance of the Updater
		 * class. It is the only way to create a new Updater, as
		 * it implements the Singleton interface
		 *
		 * @return Updater
		 */
		public static function instance(){
			if(!(self::$_instance instanceof Updater)) {
				self::$_instance = new Updater;
			}

			return self::$_instance;
		}

		public function run() {
			// Check if Symphony is installed or is already up-to-date
			if(!file_exists(DOCROOT . '/manifest/config.php')){
				self::__render(new UpdaterPage('missing'));
			}
			else {
				// Include the default Config for installation.
				$this->initialiseConfiguration();
			}

			// Initialize log
			if(!is_dir(INSTALL . '/logs') && !General::realiseDirectory(INSTALL . '/logs', Symphony::Configuration()->get('write_mode', 'directory'))){
				self::__render(new UpdaterPage('missing-log'));
			}
			else{
				// @todo Again, are we going to have a consolidated log, or individual logs.
				Symphony::Log()->setLogPath(INSTALL . '/logs/update');

				if(Symphony::Log()->open(Log::APPEND, Symphony::Configuration()->get('write_mode', 'file')) == 1){
					Symphony::Log()->initialise('Symphony Update Log');
				}
			}

			// Get available migrations. This will only contain the migrations
			// that are applicable to the current install.
			$migrations = array();

			foreach(new DirectoryIterator(INSTALL . '/migrations') as $m){
				if(!is_dir($m->getPathname())){
					$version = str_replace('.php', '', $m->getFilename());

					// Include migration so we can see what the version is
					include_once($m->getPathname());
					$classname = 'migration_' . str_replace('.', '', $version);
					$m = new $classname();

					if(version_compare(Symphony::Configuration()->get('version', 'symphony'), $m::getVersion(), '<')){
						$migrations[$version] = $m;
					}
				}
			}

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
					$n = $m::preUpdateNotes();
					if(!empty($n)) $notes[$version] = $n;
				}

				// Show the update ready page, which will display the
				// version and release notes of the most recent migration
				self::__render(new UpdaterPage('ready', array(
					'notes' => $notes,
					'version' => $m::getVersion(),
					'release-notes' => $m::getReleaseNotes()
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
					$n = $m::postUpdateNotes();
					if(!empty($n)) $notes[$version] = $n;

					$canProceed = $m::run('upgrade', Symphony::Configuration()->get('version', 'symphony'));

					if(!$canProceed) break;
				}

				if(!$canProceed){
					self::__render(new UpdaterPage('failure'));
				}
				else {
					self::__render(new UpdaterPage('success', array(
						'notes' => $notes,
						'version' => $m::getVersion(),
						'release-notes' => $m::getReleaseNotes()
					)));
				}
			}

		}

	}
