<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The abstract Manager class provides some generic functions
	 * to assist the managers in cataloguing their children. This class
	 * defines a number of abstract methods to enable autodiscovery of
	 * file based objects such as Datasource, Event, Extension and
	 * Text Formatter. Manager classes implement CRUD methods to
	 * apply their objects.
	 */
	Abstract Class Manager{

		/**
		 * An array of all the objects that the Manager is responsible for.
		 * Defaults to an empty array.
		 * @var array
		 */
		protected static $_pool = array();

		/**
		 * This function will empty the $_pool array.
		 */
		public static function flush(){
			self::$_pool = array();
		}

		/**
		 * The about function returns information about a particular object
		 * in this manager's pool. It is limited for use on file based objects
		 * such as Datasource, Event, Extension and Text Formatter. The function
		 * uses the `getClassName()`, `getDriverPath()` and `getHandleFromFilename()`
		 * functions to autodiscover the object. There is no confusion between names (ie.
		 * does author refer to a Datasource or Field) because the Manager subclass
		 * will override the autodiscovery functions to only look for objects of
		 * that type.
		 *
		 * @param string $name
		 *	The name of the object that has the about information. This should be
		 *	lowercase and free from any Symphony conventions. eg. `author`,
		 *	not `field.author.php`.
		 * @return boolean|array
		 *	False is object doesn't exist or an associative array of information
		 */
		public static function about($name) {
			return false;
		}

		/**
		 * Given a filename, return the handle. This will remove
		 * any Symphony conventions such as `field.*.php`
		 *
		 * @param string $filename
		 * @return string
		 */
		public static function __getHandleFromFilename($filename) {
			return false;
		}

		/**
		 * Given a name, return the class name of that object. Symphony objects
		 * often have conventions tied to an objects class name that prefix the
		 * class with the type of the object. eg. field{Class}, formatter{Class}
		 *
		 * @param string $name
		 * @return string
		 */
		public static function __getClassName($name) {
			return false;
		}

		/**
		 * Given a name, return the path to the class of that object
		 *
		 * @param string $name
		 * @return string
		 */
		public static function __getClassPath($name) {
			return false;
		}

		/**
		 * Given a name, return the path to the driver of that object
		 *
		 * @param string $name
		 * @return string
		 */
		public static function __getDriverPath($name) {
			return false;
		}

		/**
		 * Returns an array of all the objects that this manager is responsible for.
		 * This function is only use on the file based Managers in Symphony
		 * such `DatasourceManager`, `EventManager`, `ExtensionManager`, `FieldManager`
		 * and `TextformatterManager`.
		 *
		 * @return array
		 */
		public static function listAll() {
			return array();
		}

		/**
		 * Creates a new instance of an object by name and returns it by reference.
		 *
		 * @param string name
		 *	The name of the Object to be created. Can be used in conjunction
		 *	with the auto discovery methods to find a class.
		 * @return object
		 *	by reference
		 */
		public static function create($name) {
			return null;
		}

	}
