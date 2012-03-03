<?php

	/**
	 * @package content
	 */

	require_once(INSTALL . '/lib/class.installerpage.php');

	Class UpdaterPage extends InstallerPage {

		// @todo We need a method to allow a user to remove the updater (from the
		// Alert in the backend, it's update.php?remove in 2.2.x
		public function __construct($template, $params = array()) {
			parent::__construct($template, $params);

			$this->_template = $template;
			$this->_page_title = __('Update Symphony');
		}

		protected function __build() {
			parent::__build(
				// Replace the installed version with the updated version
				isset($this->_params['version'])
					? $this->_params['version']
					: Symphony::Configuration()->get('version', 'symphony')
			);

			// Add Release Notes for the latest migration
			if(isset($this->_params['release-notes'])){
				$h1 = end($this->Body->getChildrenByName('h1'));
				$h1->appendChild(
					new XMLElement(
						'em',
						Widget::Anchor(__('Release Notes'), $this->_params['release-notes'])
					)
				);
			}
		}

		protected function viewUptodate() {
			$h2 = new XMLElement('h2', __('Symphony is already up-to-date'));
			$p = new XMLElement('p', __('It appears that Symphony has already been installed at this location and is up to date.'));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		protected function viewReady() {
			$h2 = new XMLElement('h2', __('Updating Symphony'));
			$p = new XMLElement('p', __('This script will update your existing Symphony installation to version %s.', array('<code>' . $this->_params['version'] . '</code>')));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);

			if(!is_writable(CONFIG)) {
				$this->Form->appendChild(
					new XMLElement('p', __('Please check that your configuration file is writable before proceeding'), array('class' => 'warning'))
				);
			}

			if(!empty($this->_params['pre-notes'])){
				$h2 = new XMLElement('h2', __('Pre-Installation Notes:'));
				$dl = new XMLElement('dl');

				foreach($this->_params['pre-notes'] as $version => $note){
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
			$p = new XMLElement('p', __('An error occurred during updating.') . ' ' . __('View the %s for more details', array('<a href="' . INSTALL_URL . '/logs/update">log</a>')));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		protected function viewSuccess() {
			$this->Form->setAttribute('action', SYMPHONY_URL);

			$h2 = new XMLElement('h2', __('Updating Complete'));
			$this->Form->appendChild($h2);

			if(!empty($this->_params['post-notes'])){
				$dl = new XMLElement('dl');

				foreach($this->_params['post-notes'] as $version => $note){
					if($note){
						$dl->appendChild(new XMLElement('dt', $version));
						$dl->appendChild(new XMLElement('dd', '<p>' . implode('</p><p>', $note) . '</p>'));
					}
				}

				$this->Form->appendChild($dl);
			}

			$this->Form->appendChild(
				new XMLElement('p',
					__('Congratulations rock star! You just updated %s to the latest and greatest Symphony!', array(Symphony::Configuration()->get('sitename', 'general')))
				)
			);
			$this->Form->appendChild(
				new XMLElement('p',
					__('Before logging in, we recommend that the %s directory be removed for security.', array('<code>' . basename(INSTALL_URL) . '</code>'))
				)
			);

			$submit = new XMLElement('div', null, array('class' => 'submit'));
			$submit->appendChild(Widget::input('submit', __('Ok, now take me to the login page'), 'submit'));

			$this->Form->appendChild($submit);

		}

	}
