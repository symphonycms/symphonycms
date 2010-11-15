<?php
	/**
	 * @package toolkit
	 */
	/**
	 * The abstract Extension class contains common methods that most
	 * extensions require to get started in the Symphony environment. They
	 * include the installation updating and uninstallation, as well as a
	 * delegate subscription hook so an extension can perform custom logic
	 * at various times during Symphony execution.
	 */
	Abstract Class Extension{

		/**
		 * @var integer Determines that a new navigation group is to created
		 *  in the Symphony backend
		 */
		const NAV_GROUP = 1;

		/**
		 * @var integer Determines that a new item is to be added to an existing
		 *  navigation group in the Symphony backend
		 */
		const NAV_CHILD = 0;

		/**
		 * @var string The end-of-line constant.
		 * @deprecated This will no longer exist in Symphony 3
		 */
		const CRLF = PHP_EOL;

		/**
		 * @var Administration An instance of the Administration class
		 * @see core.Administration
		 */
		protected $_Parent;

		/**
		 * The extension constructor takes an associative array of arguments
		 * and sets the $_Parent variable using the 'parent' key. It appears that
		 * this is the only key set in the $args array by Symphony
		 */
		public function __construct(Array $args){
			$this->_Parent =& $args['parent'];
		}

		/**
		 * Any logic that assits this extension in being installed such as
		 * table creation, checking for dependancies etc.
		 *
		 * @see toolkit.ExtensionManager#install
		 * @return boolean
		 *  True if the install completly successfully, false otherwise
		 */
		public function install(){
			return true;
		}

		/**
		 * Logic that should take place when an extension is to be been updated
		 * when a user runs the 'Enable' action from the backend. The currently
		 * installed version of this extension is provided so that it can be
		 * compared to the current version of the extension in the file system.
		 * This is commonly done using PHP's version_compare function. Common
		 * logic done by this method is to update differences between extension
		 * tables.
		 *
		 * @see toolkit.ExtensionManager#update
		 * @param string $previousVersion
		 *  The currently installed version of this extension from the
		 *  tbl_extensions table. The current version of this extension is
		 *  provided by the about() method.
		 * @return boolean
		 */
		public function update($previousVersion=false){
			return true;
		}

		/**
		 * Any logic that should be run when an extension is to be uninstalled
		 * such as the removal of database tables.
		 *
		 * @see toolkit.ExtensionManager#uninstall
		 * @return boolean
		 */
		public function uninstall(){
			return true;
		}

		/**
		 * Extensions can be disabled in the backend which stops them from
		 * functioning by removing their delegate subscription information
		 * but keeps their settings intact (usually stored in the database).
		 * This method runs when a user selects Enable from the Symphony
		 * backend.
		 *
		 * @see toolkit.ExtensionManager#enable
		 * @return boolean
		 */
		public function enable(){
			return true;
		}

		/**
		 * This method runs when a user selects Disable from the Symphony
		 * backend.
		 *
		 * @see toolkit.ExtensionManager#enable
		 * @return boolean
		 */
		public function disable(){
			return true;
		}

		/**
		 * The about method allows an extension to provide
		 * information about itself as an associative array. eg.
		 *
		 *		'name' => 'Name of Formatter',
		 *		'version' => '1.8',
		 *		'release-date' => 'YYYY-MM-DD',
		 *		'author' => array(
		 *			'name' => 'Author Name',
		 *			'website' => 'Author Website',
		 *			'email' => 'Author Email'
		 *		),
		 *		'description' => 'A description about this formatter'
		 *
		 * @return array
		 *  An associative array describing the text formatter.
		 */
		abstract public function about();

		/**
		 * Extensions use delegates to perform logic at certain times
		 * throughout Symphony. This function allows an extension to
		 * subscribe to a delegate which will notify the extension when it
		 * is used so that it can perform it's custom logic.
		 * This method returns an array with the delegate name, delegate
		 * namespace, and then name of the method that should be called.
		 * The method that is called is passed an associative array containing
		 * the current context which is the $_Parent, current page object
		 * and any other variables that is passed via this delegate. eg.
		 *
		 * array(
		 *		array(
		 *			'page' => '/current/path/',
		 *			'delegate' => 'DelegateName',
		 *			'callback' => 'funtionToCall'
		 *		)
		 *	)
		 *
		 * @return array
		 */
		public function getSubscribedDelegates(){}

		/**
		 * When the Symphony navigation is being generated, this method will be
		 * called to allow extension to inject any custom backend pages into the
		 * navigation. The function returns an array of navigation items that include
		 * a location (numeric index), navigation item name and the URL to the
		 * custom page
		 *
		 * array(
		 * 	'location' => 200,
		 *		'name' => 'Bulk Importer',
		 *		'link' => '/import/'
		 *	)
		 *
		 * @see /symphony/assets/navigation.xml
		 * @return array
		 *	 Navigation items in Symphony are initially provided from the
		 *  navigation.xml file which defines some inital groupings by index.
		 *  Blueprints are 100, System are 200, but a custom group can be
		 *  created by an extension by returning a new index number. The URL
		 *  returned is relative to the Symphony backend (ie. /symphony/).
		 *  Defaults to returning null, which adds nothing to the Symphony
		 *  navigation
		 */
		public function fetchNavigation(){
			return null;
		}
	}
