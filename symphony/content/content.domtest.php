<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	class contentDomTest extends AdministrationPage {

		public function __viewIndex() {
			$this->Form->appendChild(
				$this->createElement('h3', 'Hi there')
			);

			$element = $this->createElement('h4');
			$element->setValue('testing & setValue');
			$element->setAttributeArray(array("style" => "color:red", "class" => "new"));

			$span = $this->createElement('span', 'hello');
			$element->setValue($span);

			$this->Form->appendChild($element);

		}

		public function action() {
			// Blah
		}
	}
