<?php
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class contentDomTest extends AdministrationPage {

		public function __viewIndex() {
			$this->Form->appendChild($this->createElement('h3', 'Hi there'));
		}

		public function action() {
			// Blah
		}	
	}
