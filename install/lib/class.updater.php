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
			// Initialize log
			if(!is_dir(INSTALL . '/logs') && !General::realiseDirectory(INSTALL . '/logs', Symphony::Configuration()->get('write_mode', 'directory'))){
				self::__render(new UpdaterPage('missing-log'));
			}
			else{
				// @todo Again, are we going to have a consolidated log, or individual logs.
				Symphony::Log()->setLogPath(INSTALL . '/logs/update');
				Symphony::Log()->setArchive((Symphony::Configuration()->get('archive', 'log') == '1' ? true : false));
				Symphony::Log()->setMaxSize(intval(Symphony::Configuration()->get('maxsize', 'log')));
				Symphony::Log()->setDateTimeFormat(Symphony::Configuration()->get('date_format', 'region') . ' ' . Symphony::Configuration()->get('time_format', 'region'));

				if(Symphony::Log()->open(Log::APPEND, Symphony::Configuration()->get('write_mode', 'file')) == 1){
					Symphony::Log()->initialise('Symphony Update Log');
				}
			}

			// Check if Symphony is installed or is already up-to-date
			if(!file_exists(DOCROOT . '/manifest/config.php')){
				self::__render(new UpdaterPage('missing'));
			}
			else{
				if(false && Symphony::Configuration()->get('version', 'symphony') && version_compare(VERSION, Symphony::Configuration()->get('version', 'symphony'), '=')){
					Symphony::Log()->pushToLog(
						sprintf('Updater - Already up-to-date'),
						E_ERROR, true
					);

					self::__render(new UpdaterPage('uptodate'));
				}
			}

			// Prepare updating
			// @todo We need a way to pass the current installed version to the upgrade function.
			$migrations = array();

			foreach(new DirectoryIterator(INSTALL . '/migrations') as $m){
				if(!is_dir($m->getPathname())){
					$version = str_replace('.php', '', $m->getFilename());

					if(version_compare('2.0.0', $version, '<=')){
						include_once($m->getPathname());
						$classname = 'migration_' . str_replace('.', '', $version);
						$migrations[$version] = new $classname();;
					}
					else break;
				}
			}

			// Show start page
			if(!isset($_POST['action']['update'])) {
				$notes = array();

				foreach($migrations as $version => $m){
					$n = $m::pre_notes();
					if(!empty($n)) $notes[$version] = $n;
				}

				self::__render(new UpdaterPage('ready', array(
					'notes' => $notes
				)));
			}

			// Upgrade Symphony
			else{

				$notes = array();
				$canProceed = true;

				foreach($migrations as $version => $m){
					$n = $m::post_notes();
					if(!empty($n)) $notes[$version] = $n;

					$canProceed = $m::run('upgrade');
					if(!$canProceed) break;
				}

				if(!$canProceed){
					self::__render(new UpdaterPage('failure'));
				}

				self::__render(new UpdaterPage('success', array(
					'notes' => $notes
				)));

			}

		}

	}
