<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	class contentDomTest extends AdministrationPage {

		public function __viewIndex() {
			$this->Form->appendChild(
				$this->createElement('h3', 'Hi there')
			);

			$a = Widget::Acronym('URL', array('title' => 'U R L'), ' Parameters');
			
			//var_dump($doc);

			$this->Form->appendChild($a);

		}

		public function action() {
			// Blah
		}
	}
