<?php
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class contentDomTest extends AdministrationPage {

		public function __viewIndex() {
			$this->Form->appendChild($this->createElement('h3', 'Hi there'));
			
			$b = $this->createElement('strong');			
			$text = $this->createTextNode('hi');
			
			var_dump(get_class_methods($this));
			
			
			$this->Form->appendChild(
				$b->appendChild(
					$text
				)
			);
		}

		public function action() {
			// Blah
		}	
	}
