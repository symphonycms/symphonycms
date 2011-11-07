<?php

	require_once(CORE . '/class.administration.php');

	require_once(INSTALL . '/lib/class.installer.php');
	require_once(INSTALL . '/lib/class.updaterpage.php');
	require_once(INSTALL . '/lib/class.migration.php');

	Class Updater extends Installer {

		public static function run(){

			// Check if Symphony is installed or is already up-to-date
			if(!file_exists(DOCROOT . '/manifest/config.php')){
				self::__render(new UpdaterPage('missing'));
			}
			else{
				// Initialize everything that is needed
				self::__initialize();

				if(false && self::$_conf->get('version', 'symphony') && version_compare(VERSION, self::$_conf->get('version', 'symphony'), '=')){
					self::$_log->pushToLog(
						sprintf('Updater - Already up-to-date'),
						E_ERROR, true
					);

					self::__render(new UpdaterPage('uptodate'));
				}
			}

			// Prepare updating
			$migrations = array();

			foreach(new DirectoryIterator(INSTALL . '/migrations') as $m){
				if(!is_dir($m->getPathname())){
					$version = str_replace('.php', '', $m->getFilename());

					if(version_compare('2.0.0', $version, '<=')){

						include_once($m->getPathname());
						$classname = 'migration_' . str_replace('.', '', $version);

						$object = new $classname();
						$migrations[$version] = $object;

					}
					else break;
				}
			}

			// Show start page
			if(!isset($_POST['action']['update']){

				$notes = array();

				foreach($migrations as $version => $m){
					if(!empty($m::pre_notes()))
						$notes[$version] = $m::pre_notes();
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
					if(!empty($m::post_notes()))
						$notes[$version] = $m::post_notes();

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

		protected static function __initialize(){
			Administration::instance();

			// Initialize configuration
			self::$_conf = Symphony::Configuration();

			// Initialize log
			self::$_log = Symphony::Log();

			// Initialize Database
			self::$_db = Symphony::Database();
		}

	}
