<?php

	/**
	 * @package core
	 */

	/**
	 * The Frontend class is the renderer that is used to display FrontendPage's.
	 * A FrontendPage is one that is setup in Symphony and it's output is generated
	 * by using XML and XSLT
	 */

	require_once(CORE . '/class.symphony.php');
	require_once(TOOLKIT . '/class.lang.php');
	require_once(TOOLKIT . '/class.manager.php');
	require_once(TOOLKIT . '/class.frontendpage.php');

	Class Frontend extends Symphony {

		/**
		 * An instance of the FrontendPage class
		 * @var FrontendPage
		 */
		private static $_page;

		/**
		 * This function returns an instance of the Frontend
		 * class. It is the only way to create a new Frontend, as
		 * it implements the Singleton interface
		 *
		 * @return Frontend
		 */
		public static function instance() {
			if (!(self::$_instance instanceof Frontend)) {
				self::$_instance = new Frontend;
			}

			return self::$_instance;
		}

		/**
		 * The constructor for Frontend calls the parent Symphony
		 * constructor.
		 *
		 * @see core.Symphony#__construct()
		 * @deprecated The constructor creates backwards compatible references
		 *  to `$this->Database`, `$this->ExtensionManager` and `$this->Configuration`
		 *  that act as alias for `Symphony::Database()`, `Symphony::ExtensionManager()`
		 *  and `Symphony::Configuration()`. These will be removed in the
		 *  next Symphony release
		 */
		protected function __construct() {
			parent::__construct();

			$this->_env = array();

			// Need this part for backwards compatiblity
			$this->Database = Symphony::Database();
			$this->Configuration = Symphony::Configuration();
			$this->ExtensionManager = Symphony::ExtensionManager();
		}

		/**
		 * Accessor for `$_page`
		 *
		 * @return FrontendPage
		 */
		public static function Page() {
			return self::$_page;
		}

		/**
		 * Overrides the Symphony isLoggedIn function to allow Authors
		 * to become logged into the frontend when `$_REQUEST['auth-token']`
		 * is present. This logs an Author in using the loginFromToken function.
		 * This function allows the use of 'admin' type pages, where a Frontend
		 * page requires that the viewer be a Symphony Author
		 *
		 * @see core.Symphony#loginFromToken()
		 * @return boolean
		 */
		public function isLoggedIn() {
			if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && strlen($_REQUEST['auth-token']) == 8) {
				return $this->loginFromToken($_REQUEST['auth-token']);
			}

			return parent::isLoggedIn();
		}

		/**
		 * Called by index.php, this function is responsible for rendering the current
		 * page on the Frontend. One delegate is fired, FrontendInitialised
		 *
		 * @uses FrontendInitialised
		 * @see boot.getCurrentPage()
		 * @param string $page
		 *  The result of getCurrentPage, which returns the $_GET['symphony-page']
		 *  variable.
		 * @return string
		 *  The HTML of the page to return
		 */
		public function display($page) {
			self::$_page = new FrontendPage($this);

			/**
			 * @delegate FrontendInitialised
			 */
			Frontend::instance()->ExtensionManager->notifyMembers('FrontendInitialised', '/frontend/');

			$output = self::$_page->generate($page);

			return $output;
		}
	}

	/**
	 * FrontendPageNotFoundException extends a default Exception, it adds nothing
	 * but allows a different Handler to be used to render the Exception
	 *
	 * @see core.FrontendPageNotFoundExceptionHandler
	 */
	Class FrontendPageNotFoundException extends Exception{

		/**
		 * The constructor for FrontendPageNotFoundException sets the default
		 * error message and code for Logging purposes
		 */
		public function __construct() {
			parent::__construct();
			$this->message = __('The page you requested, %s, does not exist.', array('<code>' . getCurrentPage() . '</code>'));
			$this->code = E_USER_NOTICE;
		}

	}

	/**
	 * The FrontendPageNotFoundExceptionHandler attempts to find a Symphony
	 * page that has been given the '404' page type to render the SymphonyErrorPage
	 * error, instead of using the Symphony default.
	 */
	Class FrontendPageNotFoundExceptionHandler extends SymphonyErrorPageHandler{

		/**
		 * The render function will take a FrontendPageNotFoundException Exception and
		 * output a HTML page. This function first checks to see if their is a page in Symphony
		 * that has been given the '404' page type, otherwise it will just use the default
		 * Symphony error page template to output the exception
		 *
		 * @param FrontendPageNotFoundException $e
		 *  The Exception object
		 * @return string
		 *  An HTML string
		 */
		public static function render($e){
			$page_id = Symphony::Database()->fetchVar('page_id', 0, "SELECT `page_id` FROM `tbl_pages_types` WHERE `type` = '404' LIMIT 1");

			if(is_null($page_id)){
				parent::render(new SymphonyErrorPage($e->getMessage(), __('Page Not Found'), 'error', array('header' => 'HTTP/1.0 404 Not Found')));
			}
			else{
				$url = '/' . Frontend::instance()->resolvePagePath($page_id) . '/';

				$output = Frontend::instance()->display($url);
				header(sprintf('Content-Length: %d', strlen($output)));
				echo $output;
				exit;
			}
		}
	}
