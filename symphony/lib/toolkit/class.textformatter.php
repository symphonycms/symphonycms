<?php
	/**
	 * @package toolkit
	 */
	/**
	 * The abstract TextFormatter classes defines two methods
	 * that must be implemented by any Symphony text formatter.
	 */
	Abstract Class TextFormatter{

		/**
		 * An instance of the Administration class
		 * @var Administration
		 * @see core.Administration
		 */
		protected $_Parent;

		/**
		 * The text formatter constructor sets the $_Parent variable
		 * to the $parent provided as a parameter.
		 *
		 * @param Administration $parent
		 *  The Administration object that this page has been created from
		 *  passed by reference
		 */
		public function __construct(&$parent){
			$this->_Parent = $parent;
		}

		/**
		 * The about method allows a text formatter to provide
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
		 * Given an input, apply the formatter and return the result
		 *
		 * @param string $string
		 * @return string
		 */
		abstract public function run($string);

	}
