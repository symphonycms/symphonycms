<?php

	/**
	 * @package content
	 */

	require_once(TOOLKIT . '/class.htmlpage.php');

	Class InstallerPage extends HTMLPage {

		/**
		 * Constructor for the HTMLPage. Intialises the class variables with
		 * empty instances of XMLElement
		 */
		public function __construct() {
			parent::__construct();

			$this->Html->setDTD('<!DOCTYPE html>');
			$this->Html->setAttribute('lang', Lang::get());
			$this->addElementToHead(new XMLElement('meta', NULL, array('charset' => 'UTF-8')), 0);

			$this->addStylesheetToHead(kINSTALL_ASSET_LOCATION . '/installer.css', 'screen', 40);
			$this->addScriptToHead(kINSTALL_ASSET_LOCATION . '/installer.js', 50);
		}

		/**
		 * Appends the `$this->Header`, `$this->Context` and `$this->Contents`
		 * to `$this->Wrapper` before adding the ID and class attributes for
		 * the `<body>` element. After this has completed the parent's generate
		 * function is called which will convert the `XMLElement`'s into strings
		 * ready for output
		 *
		 * @return string
		 */
		public function generate() {
#			$this->Wrapper->appendChild($this->Header);
#			$this->Wrapper->appendChild($this->Context);
#			$this->Wrapper->appendChild($this->Contents);

#			$this->Body->appendChild($this->Wrapper);

#			$this->__appendBodyId();
#			$this->__appendBodyClass($this->_context);
			return parent::generate();
		}

	}

	Class InstallerErrorPage extends InstallerPage {

		/**
		 * Constructor for the HTMLPage. Intialises the class variables with
		 * empty instances of XMLElement
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Appends the `$this->Header`, `$this->Context` and `$this->Contents`
		 * to `$this->Wrapper` before adding the ID and class attributes for
		 * the `<body>` element. After this has completed the parent's generate
		 * function is called which will convert the `XMLElement`'s into strings
		 * ready for output
		 *
		 * @return string
		 */
		public function generate() {
#			$this->Wrapper->appendChild($this->Header);
#			$this->Wrapper->appendChild($this->Context);
#			$this->Wrapper->appendChild($this->Contents);

#			$this->Body->appendChild($this->Wrapper);

#			$this->__appendBodyId();
#			$this->__appendBodyClass($this->_context);
			return parent::generate();
		}

	}
