<?php
	/**
	 * @package toolkit
	 */
	/**
	 * AjaxPage extends the Page class to provide an object representation
	 * of a Symphony backend AJAX page.
	 */

	require_once(TOOLKIT . '/class.page.php');

	Abstract Class AjaxPage extends Page{

		/**
		 * Refers to the HTTP status code, 200 OK
		 * @var integer
		 */
		const STATUS_OK = 200;

		/**
		 * Refers to the HTTP status code, 400 Bad Request
		 * @var integer
		 */
		const STATUS_BAD = 400;

		/**
		 * Refers to the HTTP status code, 400 Bad Request
		 * @var integer
		 */
		const STATUS_ERROR = 400;

		/**
		 * Refers to the HTTP status code, 401 Unauthorized
		 * @var integer
		 */
		const STATUS_UNAUTHORISED = 401;

		/**
		 * An instance of the Administration class
		 * @var Administration
		 * @see core.Administration
		 */
		protected $_Parent;

		/**
		 * The root node for the response of the AJAXPage
		 * @var XMLElement
		 */
		protected $_Result;

		/**
		 * The HTTP status code of the page using the AJAXPage constants
		 * `STATUS_OK`, `STATUS_BAD`, `STATUS_ERROR` or `STATUS_UNAUTHORISED`
		 * @var integer
		 */
		protected $_status;

		/**
		 * The constructor for AJAXPage. This sets the page status to `STATUS_OK`,
		 * the default content type to text/xml and initialises `$this->_Result`
		 * with an XMLElement. The constructor also starts the Profiler for this
		 * page template.
		 *
		 * @see toolkit.Profiler
		 * @param Administration $parent
		 *  The Administration object that this page has been created from
		 *  passed by reference
		 */
		public function __construct(&$parent){

			$this->_Parent = $parent;

			$this->_Result = new XMLElement('result');
			$this->_Result->setIncludeHeader(true);

			$this->_status = self::STATUS_OK;

			$this->addHeaderToPage('Content-Type', 'text/xml');

			Administration::instance()->Profiler->sample('Page template created', PROFILE_LAP);
		}

		/**
		 * This function is called when a user is not authenticated to the Symphony
		 * backend. It sets the status of this page to `STATUS_UNAUTHORISED` and
		 * appends a message for generation
		 */
		public function handleFailedAuthorisation(){
			$this->_status = self::STATUS_UNAUTHORISED;
			$this->_Result->setValue(__('You are not authorised to access this page.'));
		}

		/**
		 * Calls the view function of this page. If a context is passed, it is
		 * also set.
		 *
		 * @see view()
		 * @param array $context
		 *  The context of the page as an array. Defaults to null
		 */
		public function build($context = null){
			if($context) $this->_context = $context;
			$this->view();
		}

		/**
		 * The generate functions outputs the correct headers for
		 * this AJAXPage, adds `$this->_status` code to the root attribute
		 * before calling the parent generate function and generating
		 * the `$this->_Result` XMLElement
		 *
		 * @return string
		 */
		public function generate(){

			switch($this->_status){

				case self::STATUS_OK:
					$status_message = '200 OK';
					break;

				case self::STATUS_BAD:
				case self::STATUS_ERROR:
					$status_message = '400 Bad Request';
					break;

				case self::STATUS_UNAUTHORISED:
					$status_message = '401 Unauthorized';
					break;

			}

			$this->addHeaderToPage('HTTP/1.0 ' . $status_message);
			$this->_Result->setAttribute('status', $this->_status);

			parent::generate();
			return $this->_Result->generate(true);
		}

		/**
		 * All classes that extend the AJAXPage class must define a view method
		 * which contains the logic for the content of this page. The resulting HTML
		 * is append to `$this->_Result` where it is generated on build
		 *
		 * @see build()
		 */
		abstract public function view();

	}
