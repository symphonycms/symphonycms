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
		 * @var Administration An instance of the Administration class
		 * @see core.Administration
		 */
	    public $_Parent;

		/**
		 * @var array An array of all the objects that the Manager
		 *  is responsible for. Defaults to empty.
		 */
	    protected static $_pool = array();

	    /**
		 * The constructor for Manager. This sets the $_Parent to be an
		 * instance of the Administration class.
		 *
		 * @param Adminstration $parent
		 *  The Adminstration object that this manager has been created from
		 *  passed by reference
		 */
        public function __construct(&$parent){
			$this->_Parent = $parent;
        }

		/**
		 * This function will empty the $_pool array.
		 */
        public function flush(){
	        self::$_pool = array();
        }

	    /**
		 * The about function returns information about a particular object
		 * in this manager's pool. It is limited for use on file based objects
		 * such as Datasource, Event, Extension and Text Formatter. The function
		 * uses the getClassName, getDriverPath and getHandleFromFilename functions
		 * to autodiscover the object. There is no confusion between names (ie.
		 * does author refer to a Datasource or Field) because the Manager subclass
		 * will override the autodiscovery functions to only look for objects of
		 * that type.
		 *
		 * @param string $name
		 *  The name of the object that has the about information. This should be
		 *  lowercase and free from any Symphony conventions. eg. author,
		 *  not field.author.php.
		 * @return boolean|array
		 *  False is object doesn't exist or an associative array of information
		 */
        public function about($name){

	        $classname = $this->__getClassName($name);
	        $path = $this->__getDriverPath($name);

			if(!@file_exists($path)) return false;

			require_once($path);

			$handle = $this->__getHandleFromFilename(basename($path));

	        if(is_callable(array($classname, 'about'))){
				$about = call_user_func(array($classname, 'about'));
				return array_merge($about, array('handle' => $handle));
			}

        }

        /**
		 * Given a name, return the class name of that object
		 *
		 * @return string
		 */
        public function __getClassName($name){
            return null;
        }

        /**
		 * Given a name, return the path to the class of that object
		 *
		 * @return string
		 */
        public function __getClassPath($name){
            return null;
        }

        /**
		 * Given a name, return the path to the driver of that object
		 *
		 * @return string
		 */
        public function __getDriverPath($name){
            return null;
        }

        /**
		 * Given a filename, return the handle. This will remove
		 * any Symphony conventions such as field.*.php
		 *
		 * @return string
		 */
		public function __getHandleFromFilename($filename){
		    return null;
		}

        /**
		 * Returns an array of all the objects that this manager is responsible for.
		 * This function is only use on the file based Managers in Symphony
		 * such DatasourceManager,EventManager, ExtensionManager, FieldManager
		 * and TextformatterManager.
		 *
		 * @return array
		 */
        public function listAll(){
            return array();
        }

        /**
		 * Creates a new instance of an object by name and returns it by reference.
		 *
		 * @param string name (optional)
		 *  The name of the Object to be created. Can be used in conjunction
		 *  with the auto discovery methods to find a class.
		 * @return object
		 *  by reference
		 */
        public function &create($name){
        }

    }
