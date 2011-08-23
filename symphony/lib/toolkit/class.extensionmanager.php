<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The ExtensionManager class is responsible for managing all extensions
	 * in Symphony. Extensions are stored on the file system in the `EXTENSIONS`
	 * folder. They are autodiscovered where the Extension class name is the same
	 * as it's folder name (excluding the extension prefix).
	 */

	include_once(TOOLKIT . '/class.manager.php');
	include_once(TOOLKIT . '/class.extension.php');

	Class ExtensionManager extends Manager{

		/**
		 * An array of all the objects that the Manager is responsible for.
		 * Defaults to an empty array.
		 * @var array
		 */
		protected static $_pool = array();

		/**
		 * An array of all extensions whose status is enabled
		 * @var array
		 */
		private static $_enabled_extensions = null;

		/**
		 * An array of all the subscriptions to Symphony delegates made by extensions.
		 * @var array
		 */
		private static $_subscriptions = array();

		/**
		 * An associative array of all the extensions in `tbl_extensions` where
		 * the key is the extension name and the value is an array
		 * representation of it's accompanying database row.
		 * @var array
		 */
		private static $_extensions = array();

		/**
		 * The constructor will populate the `$_subscriptions` variable from
		 * the `tbl_extension` and `tbl_extensions_delegates` tables.
		 */
		public function __construct() {
			if (empty(self::$_subscriptions)) {
				$subscriptions = Symphony::Database()->fetch("
					SELECT t1.name, t2.page, t2.delegate, t2.callback
					FROM `tbl_extensions` as t1 INNER JOIN `tbl_extensions_delegates` as t2 ON t1.id = t2.extension_id
					WHERE t1.status = 'enabled'
				");

				foreach($subscriptions as $subscription) {
					self::$_subscriptions[$subscription['delegate']][] = $subscription;
				}
			}
		}

		/**
		 * Given a name, returns the full class name of an Extension.
		 * Extension use an 'extension' prefix.
		 *
		 * @param string $name
		 *  The extension handle
		 * @return string
		 */
		public static function __getClassName($name){
			return 'extension_' . $name;
		}

		/**
		 * Finds an Extension by name by searching the `EXTENSIONS` folder and
		 * returns the path to the folder.
		 *
		 * @param string $name
		 *  The extension folder
		 * @return string
		 */
		public static function __getClassPath($name){
			return EXTENSIONS . strtolower("/$name");
		}

		/**
		 * Given a name, return the path to the driver of the Extension.
		 *
		 * @see toolkit.ExtensionManager#__getClassPath()
		 * @param string $name
		 *  The extension folder
		 * @return string
		 */
		public static function __getDriverPath($name){
			return self::__getClassPath($name) . '/extension.driver.php';
		}

		/**
		 * This function returns an instance of an extension from it's name
		 *
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return Extension
		 */
		public static function getInstance($name){
			foreach(self::$_pool as $key => $extension){
				if($key == $name) return $extension;
			}

			return self::create($name);
		}

		/**
		 * Populates the `ExtensionManager::$_extensions` array with all the
		 * extensions stored in `tbl_extensions`. If `ExtensionManager::$_extensions`
		 * isn't empty, passing true as a parameter will force the array to update
		 *
		 * @param boolean $update
		 *  Updates the `ExtensionManager::$_extensions` array even if it was
		 *  populated, defaults to false.
		 */
		private static function __buildExtensionList($update=false) {
			if (empty(self::$_extensions) || $update) {
				self::$_extensions = Symphony::Database()->fetch("SELECT * FROM `tbl_extensions`", 'name');
			}
		}

		/**
		 * Returns the status of an Extension by name
		 *
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return integer
		 *  An extension status, `EXTENSION_ENABLED`, `EXTENSION_DISABLED`
		 *  `EXTENSION_NOT_INSTALLED` and `EXTENSION_REQUIRES_UPDATE`.
		 *  If an extension doesn't exist, null will be returned.
		 */
		public static function fetchStatus($name){
			self::__buildExtensionList();

			if(array_key_exists($name, self::$_extensions)) {
				$status = self::$_extensions[$name]['status'];
			}
			else return EXTENSION_NOT_INSTALLED;

			if($status == 'enabled') return EXTENSION_ENABLED;

			return EXTENSION_DISABLED;
		}

		/**
		 * A convenience method that returns an extension version from it's name.
		 *
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return string
		 */
		public static function fetchInstalledVersion($name){
			self::__buildExtensionList();
			return self::$_extensions[$name]['version'];
		}

		/**
		 * A convenience method that returns an extension ID from it's name.
		 *
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return integer
		 */
		public static function fetchExtensionID($name){
			self::__buildExtensionList();
			return self::$_extensions[$name]['id'];
		}

		/**
		 * Custom user sorting function to sort extensions by name
		 *
		 * @link http://php.net/manual/en/function.strnatcasecmp.php
		 * @param array $a
		 * @param array $b
		 * @return integer
		 */
		public function sortByName(array $a, array $b) {
			return strnatcasecmp($a['name'], $b['name']);
		}

		/**
		 * Determines whether the current extension is installed or not by checking
		 * for an id in `tbl_extensions`
		 *
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return boolean
		 */
		private static function __requiresInstallation($name){
			self::__buildExtensionList();
			$id = self::$_extensions[$name]['id'];

			return (is_numeric($id) ? false : true);
		}

		/**
		 * Determines whether an extension needs to be updated or not using
		 * PHP's version_compare function.
		 *
		 * @param array $info
		 *  The about array from the extension
		 * @return boolean
		 */
		private static function __requiresUpdate(array $info){
			if($info['status'] == EXTENSION_NOT_INSTALLED) return false;

			$current_version = self::fetchInstalledVersion($info['handle']);

			return (version_compare($current_version, $info['version'], '<') ? $current_version : false);
		}

		/**
		 * Enabling an extension will re-register all it's delegates with Symphony.
		 * It will also install or update the extension if needs be by calling the
		 * extensions respective install and update methods. The enable method is
		 * of the extension object is finally called.
		 *
		 * @see toolkit.ExtensionManager#registerDelegates()
		 * @see toolkit.ExtensionManager#__canUninstallOrDisable()
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return boolean
		 */
		public static function enable($name){
			$obj = self::getInstance($name);

			## If not installed, install it
			if(self::__requiresInstallation($name) && $obj->install() === false){
				return false;
			}

			## If requires and upate before enabling, than update it first
			elseif(($about = self::about($name)) && ($previousVersion = self::__requiresUpdate($about)) !== false) {
				self::update($previousVersion);
			}

			$info = $obj->about();
			$id = self::fetchExtensionID($name);

			$fields = array(
				'name' => $name,
				'status' => 'enabled',
				'version' => $info['version']
			);

			if(is_null($id)) {
				Symphony::Database()->insert($fields, 'tbl_extensions');
				self::__buildExtensionList(true);
			}
			else {
				Symphony::Database()->update($fields, 'tbl_extensions', " `id` = '$id '");
			}

			self::registerDelegates($name);

			## Now enable the extension
			$obj->enable();

			return true;
		}

		/**
		 * Disabling an extension will prevent it from executing but retain all it's
		 * settings in the relevant tables. Symphony checks that an extension can
		 * be disabled using the `canUninstallorDisable()` before removing
		 * all delegate subscriptions from the database and calling the extension's
		 * `disable()` function.
		 *
		 * @see toolkit.ExtensionManager#removeDelegates()
		 * @see toolkit.ExtensionManager#__canUninstallOrDisable()
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return boolean
		 */
		public static function disable($name){
			$obj = self::getInstance($name);

			self::__canUninstallOrDisable($obj);

			$info = $obj->about();
			$id = self::fetchExtensionID($name);

			Symphony::Database()->update(array(
					'name' => $name,
					'status' => 'disabled',
					'version' => $info['version']
				),
				'tbl_extensions',
				" `id` = '$id '"
			);

			$obj->disable();

			self::removeDelegates($name);

			return true;
		}

		/**
		 * Uninstalling an extension will unregister all delegate subscriptions and
		 * remove all extension settings. Symphony checks that an extension can
		 * be uninstalled using the `canUninstallorDisable()` before calling
		 * the extension's `uninstall()` function.
		 *
		 * @see toolkit.ExtensionManager#removeDelegates()
		 * @see toolkit.ExtensionManager#__canUninstallOrDisable()
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return boolean
		 */
		public static function uninstall($name){
			$obj = self::getInstance($name);

			self::__canUninstallOrDisable($obj);

			$obj->uninstall();

			self::removeDelegates($name);

			Symphony::Database()->delete('tbl_extensions', " `name` = '$name' ");

			return true;
		}

		/**
		 * This functions registers an extensions delegates in `tbl_extensions_delegates`.
		 *
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return integer
		 *  The Extension ID
		 */
		public static function registerDelegates($name){
			$obj = self::getInstance($name);
			$id = self::fetchExtensionID($name);

			if(!$id) return false;

			Symphony::Database()->delete('tbl_extensions_delegates', " `extension_id` = '$id ' ");

			$delegates = $obj->getSubscribedDelegates();

			if(is_array($delegates) && !empty($delegates)){
				foreach($delegates as $delegate){

					Symphony::Database()->insert(
						array(
							'extension_id' => $id  ,
							'page' => $delegate['page'],
							'delegate' => $delegate['delegate'],
							'callback' => $delegate['callback']
						),
						'tbl_extensions_delegates'
					);

				}
			}

			## Remove the unused DB records
			self::__cleanupDatabase();

			return $id;
		}

		/**
		 * This function will remove all delegate subscriptions for an extension
		 * given an extension's name. This triggers `__cleanupDatabase()`
		 *
		 * @see toolkit.ExtensionManager#__cleanupDatabase()
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 */
		public static function removeDelegates($name){
			$classname = self::__getClassName($name);
			$path = self::__getDriverPath($name);

			if(!@file_exists($path)) return false;

			$delegates = Symphony::Database()->fetchCol('id', sprintf("
					SELECT tbl_extensions_delegates.`id`
					FROM `tbl_extensions_delegates`
					LEFT JOIN `tbl_extensions`
					ON (`tbl_extensions`.id = `tbl_extensions_delegates`.extension_id)
					WHERE `tbl_extensions`.name = '%s'
				", $name
			));

			if(!empty($delegates)) {
				Symphony::Database()->delete('tbl_extensions_delegates', " `id` IN ('". implode("', '", $delegates). "') ");
			}

			## Remove the unused DB records
			self::__cleanupDatabase();

			return true;
		}

		/**
		 * This function checks that if the given extension has provided Fields,
		 * Data Sources or Events, that they aren't in use before the extension
		 * is uninstalled or disabled. This prevents exceptions from occurring when
		 * accessing an object that was using something provided by this Extension
		 * can't anymore because it has been removed.
		 *
		 * @param Extension $obj
		 *  An extension object
		 * @return boolean
		 */
		private static function __canUninstallOrDisable(Extension $obj){
			$extension_handle = strtolower(preg_replace('/^extension_/i', NULL, get_class($obj)));

			// Fields:
			if(is_dir(EXTENSIONS . "/{$extension_handle}/fields")){
				foreach(glob(EXTENSIONS . "/{$extension_handle}/fields/field.*.php") as $file){
					$type = preg_replace(array('/^field\./i', '/\.php$/i'), NULL, basename($file));
					if(Symphony::Database()->fetchVar('count', 0, "SELECT COUNT(*) AS `count` FROM `tbl_fields` WHERE `type` = '{$type}'") > 0){
						$about = $obj->about();
						throw new Exception(
							__(
								"The field '%s', provided by the Extension '%s', is currently in use. Please remove it from your sections prior to uninstalling or disabling.",
								array(basename($file), $about['name'])
							)
						);
					}
				}
			}

			// Data Sources:
			if(is_dir(EXTENSIONS . "/{$extension_handle}/data-sources")){
				foreach(glob(EXTENSIONS . "/{$extension_handle}/data-sources/data.*.php") as $file){
					$handle = preg_replace(array('/^data\./i', '/\.php$/i'), NULL, basename($file));
					if(Symphony::Database()->fetchVar('count', 0, "SELECT COUNT(*) AS `count` FROM `tbl_pages` WHERE `data_sources` REGEXP '[[:<:]]{$handle}[[:>:]]' ") > 0){
						$about = $obj->about();
						throw new Exception(
							__(
								"The Data Source '%s', provided by the Extension '%s', is currently in use. Please remove it from your pages prior to uninstalling or disabling.",
								array(basename($file), $about['name'])
							)
						);
					}
				}
			}

			// Events
			if(is_dir(EXTENSIONS . "/{$extension_handle}/events")){
				foreach(glob(EXTENSIONS . "/{$extension_handle}/events/event.*.php") as $file){
					$handle = preg_replace(array('/^event\./i', '/\.php$/i'), NULL, basename($file));
					if(Symphony::Database()->fetchVar('count', 0, "SELECT COUNT(*) AS `count` FROM `tbl_pages` WHERE `events` REGEXP '[[:<:]]{$handle}[[:>:]]' ") > 0){
						$about = $obj->about();
						throw new Exception(
							__(
								"The Event '%s', provided by the Extension '%s', is currently in use. Please remove it from your pages prior to uninstalling or disabling.",
								array(basename($file), $about['name'])
							)
						);
					}
				}
			}
		}

		/**
		 * Given a delegate name, notify all extensions that have registered to that
		 * delegate to executing their callbacks with a `$context` array parameter
		 * that contains information about the current Symphony state.
		 *
		 * @param string $delegate
		 *  The delegate name
		 * @param string $page
		 *  The current page namespace that this delegate operates in
		 * @param array $context
		 *  The `$context` param is an associative array that at minimum will contain
		 *  the current Administration class, the current page object and the delegate
		 *  name. Other context information may be passed to this function when it is
		 *  called. eg.
		 *
		 * array(
		 *		'parent' =>& $this->Parent,
		 *		'page' => $page,
		 *		'delegate' => $delegate
		 *	);
		 *
		 */
		public static function notifyMembers($delegate, $page, array $context=array()){
			if((int)Symphony::Configuration()->get('allow_page_subscription', 'symphony') != 1) return;

			// Make sure $page is an array
			if(!is_array($page)){
				$page = array($page);
			}

			// Support for global delegate subscription
			if(!in_array('*', $page)){
				$page[] = '*';
			}

			$services = array();

			if(isset(self::$_subscriptions[$delegate])) foreach(self::$_subscriptions[$delegate] as $subscription) {
				if(!in_array($subscription['page'], $page)) continue;

				$services[] = $subscription;
			}

			if(empty($services)) return null;

			$context += array('page' => $page, 'delegate' => $delegate);

			foreach($services as $s){
				$obj = self::getInstance($s['name']);

				if(is_object($obj) && method_exists($obj, $s['callback'])) {
					$obj->{$s['callback']}($context);
				}
			}
		}

		/**
		 * Returns an array of all the enabled extensions available
		 *
		 * @return array
		 */
		public static function listInstalledHandles(){
			if(is_null(self::$_enabled_extensions)) {
				self::$_enabled_extensions = Symphony::Database()->fetchCol('name',
					"SELECT `name` FROM `tbl_extensions` WHERE `status` = 'enabled'"
				);
			}
			return self::$_enabled_extensions;
		}

		/**
		 * Will return an associative array of all extensions and their about information
		 *
		 * @param string $filter
		 *  Allows a regular expression to be passed to return only extensions whose
		 *  folders match the filter.
		 * @return array
		 *  An associative array with the key being the extension folder and the value
		 *  being the extension's about information
		 */
		public static function listAll($filter='/^((?![-^?%:*|"<>]).)*$/') {
			$result = array();
			$extensions = General::listDirStructure(EXTENSIONS, $filter, false, EXTENSIONS);

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $extension){
					$e = trim($extension, '/');
					if($about = self::about($e)) $result[$e] = $about;
				}
			}

			return $result;
		}

		/**
		 * Returns information about an extension by it's name by calling
		 * it's own about method. This method checks if an extension needs
		 * to be updated or not.
		 * `
		 *		'name' => 'Name of Extension',
		 *		'version' => '1.8',
		 *		'release-date' => 'YYYY-MM-DD',
		 *		'author' => array(
		 *			'name' => 'Author Name',
		 *			'website' => 'Author Website',
		 *			'email' => 'Author Email'
		 *		),
		 *		'description' => 'A description about this extension'
		 * `
		 * @see toolkit.ExtensionManager#__requiresUpdate()
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return array
		 *  An associative array describing this extension
		 */
		public static function about($name){
			$obj = self::getInstance($name);

			$about = $obj->about();
			$about['handle'] = $name;
			$about['status'] = self::fetchStatus($name);

			$nav = $obj->fetchNavigation();

			if(!is_null($nav)) $about['navigation'] = $nav;

			if(self::__requiresUpdate($about)) $about['status'] = EXTENSION_REQUIRES_UPDATE;

			return $about;
		}

		/**
		 * Creates an instance of a given class and returns it
		 *
		 * @param string $name
		 *  The name of the Extension Class minus the extension prefix.
		 * @return Extension
		 */
		public static function create($name){
			if(!is_array(self::$_pool)) self::flush();

			if(!isset(self::$_pool[$name])){
				$classname = self::__getClassName($name);
				$path = self::__getDriverPath($name);

				if(!is_file($path)){
					throw new Exception(
						__('Could not find extension at location %s', array($path))
					);
				}

				if(!class_exists($classname)) require_once($path);

				// Create the extension object
				self::$_pool[$name] = new $classname(array());
			}

			return self::$_pool[$name];
		}

		/**
		 * A utility function that is used by the ExtensionManager to ensure
		 * stray delegates are not in `tbl_extensions_delegates`. It is called when
		 * a new Delegate is added or removed.
		 */
		private static function __cleanupDatabase(){
			## Grab any extensions sitting in the database
			$rows = Symphony::Database()->fetch("SELECT `name` FROM `tbl_extensions`");

			## Iterate over each row
			if(is_array($rows) && !empty($rows)){
				foreach($rows as $r){
					$name = $r['name'];

					## Grab the install location
					$path = self::__getClassPath($name);
					$existing_id = self::fetchExtensionID($name);

					## If it doesnt exist, remove the DB rows
					if(!@is_dir($path)){
						Symphony::Database()->delete("tbl_extensions_delegates", " `extension_id` = $existing_id ");
						Symphony::Database()->delete('tbl_extensions', " `id` = '$existing_id' LIMIT 1");
					}
					elseif ($r['status'] == 'disabled') {
						Symphony::Database()->delete("tbl_extensions_delegates", " `extension_id` = $existing_id ");
					}
				}
			}
		}
	}

	/**
	 * Status when an extension is installed and enabled
	 * @var integer
	 */
	define_safe('EXTENSION_ENABLED', 10);

	/**
	 * Status when an extension is disabled
	 * @var integer
	 */
	define_safe('EXTENSION_DISABLED', 11);

	/**
	 * Status when an extension is in the file system, but has not been installed.
	 * @var integer
	 */
	define_safe('EXTENSION_NOT_INSTALLED', 12);

	/**
	 * Status when an extension version in the file system is different to
	 * the version stored in the database for the extension
	 * @var integer
	 */
	define_safe('EXTENSION_REQUIRES_UPDATE', 13);
