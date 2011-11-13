<?php

	/**
	 * @package content
	 */

	require_once(INSTALL . '/lib/class.installerpage.php');

	Class UpdaterPage extends InstallerPage {

		// @todo We need a method to allow a user to remove the updater (from the
		// Alert in the backend, it's update.php?remove in 2.2.x
		// @todo We need to show the Change log/Release notes links.
		public function __construct($template, $params = array()) {
			parent::__construct($template, $params);

			$this->_page_title = __('Update Symphony');
		}

		protected function viewMissing() {
			$h2 = new XMLElement('h2', __('Missing Symphony Installation'));
			$p = new XMLElement('p', __('It appears that Symphony has not been installed at this location.'));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		protected function viewUptodate() {
			$h2 = new XMLElement('h2', __('Symphony is already up-to-date'));
			$p = new XMLElement('p', __('It appears that Symphony has already been installed at this location and is up to date.'));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		protected function viewReady() {
			$h2 = new XMLElement('h2', __('Updating Symphony'));
			$p = new XMLElement('p', __('This script will update your existing Symphony installation to version %s.', array('<code>' . VERSION . '</code>')));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);

			if(!empty($this->_params['notes'])){
				$h2 = new XMLElement('h2', __('Pre-Installation Notes:'));
				$dl = new XMLElement('dl');

				foreach($this->_params['notes'] as $version => $note){
					$dl->appendChild(new XMLElement('dt', $version));
					$dl->appendChild(new XMLElement('dd', '<p>' . implode('</p><p>', $note) . '</p>'));
				}

				$this->Form->appendChild($h2);
				$this->Form->appendChild($dl);
			}

			$submit = new XMLElement('div', null, array('class' => 'submit'));
			$submit->appendChild(Widget::input('action[update]', __('Update Symphony'), 'submit'));

			$this->Form->appendChild($submit);
		}

		protected function viewFailure() {
			$h2 = new XMLElement('h2', __('Updating Failure'));
			$p = new XMLElement('p', __('An error occurred during updating.') . __('View your log for more details'));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		protected function viewSuccess() {
			$this->Form->setAttribute('action', URL . '/symphony/');

			$h2 = new XMLElement('h2', __('Updating Complete'));

			if(!empty($this->_params['notes'])){
				$dl = new XMLElement('dl');

				foreach($this->_params['notes'] as $version => $note){
					var_dump($note);
					if($note){
						$dl->appendChild(new XMLElement('dt', $version));
						$dl->appendChild(new XMLElement('dd', '<p>' . implode('</p><p>', $note) . '</p>'));
					}
				}

				$this->Form->appendChild($h2);
				$this->Form->appendChild($dl);
			}

			$this->Form->appendChild(
				new XMLElement('p',
					__('Before proceeding, please make sure to delete the %s file for security reasons.', array('<code>' . basename(SCRIPT_FILENAME) . '</code>'))
				)
			);

			$submit = new XMLElement('div', null, array('class' => 'submit'));
			$submit->appendChild(Widget::input('submit', __('I promise, now take me to the login page'), 'submit'));

			$this->Form->appendChild($submit);

		}

	}
