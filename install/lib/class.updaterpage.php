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
				Symphony::Configuration()->get('version', 'symphony')
			);

			if(isset($this->_params['release-notes'])){
				$h1 = $this->Body->getChildrenByName('h1');
				$h1->appendChild(
					new XMLElement(
						'em',
						Widget::Anchor(__('Release Notes'), $this->_params['release-notes'])
					)
				);
			}
		}

#		protected function __build() {
#			HTMLPage::__build();

#			$this->Form = Widget::Form(sprintf('%s?lang=%s&step=%s',
#				SCRIPT_FILENAME,
#				(isset($_REQUEST['lang']) ? $_REQUEST['lang'] : 'en'),
#				$this->_template
#			), 'post');

#			$title = new XMLElement('h1', $this->_page_title);
#			$version = new XMLElement('em', __('Version %s', array($this->_params['version'])));
#			$releasenotes = Widget::Anchor(__('Release Notes'), $this->_params['release-notes']);

#			$title->appendChild($version);
#			$title->appendChild(new XMLElement('em', $releasenotes));
#			$this->Body->appendChild($title);

#			$languages = new XMLElement('ul');

#			foreach(Lang::getAvailableLanguages(false) as $code => $lang) {
#				$languages->appendChild(new XMLElement(
#					'li',
#					Widget::Anchor(
#						$lang,
#						'?lang=' . $code . '&step=' . $this->_template
#					),
#					($_REQUEST['lang'] == $code || ($_REQUEST['lang'] == NULL && $code == 'en')) ? array('class' => 'selected') : array()
#				));
#			}

#			$languages->appendChild(new XMLElement(
#				'li',
#				Widget::Anchor(
#					__('Symphony is also available in other languages'),
#					'http://symphony-cms.com/download/extensions/translations/'
#				),
#				array('class' => 'more')
#			));

#			$this->Body->appendChild($languages);
#			$this->Body->appendChild($this->Form);

#			$function = 'view' . str_replace('-', '', ucfirst($this->_template));
#			$this->$function();
#		}

#		protected function viewMissing() {
#			$h2 = new XMLElement('h2', __('Missing Symphony Installation'));
#			$p = new XMLElement('p', __('It appears that Symphony has not been installed at this location.'));

#			$this->Form->appendChild($h2);
#			$this->Form->appendChild($p);
#		}

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
			$p = new XMLElement('p', __('An error occurred during updating.') . __('View your log for more details'));

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
					__('Congratulations rock star, you have just updated your Symphony install to the latest and greatest!')
				)
			);
			$this->Form->appendChild(
				new XMLElement('p',
					__('Before logging in, we recommend that the %s directory be removed for security.', array('<code>/install</code>'))
				)
			);

			$submit = new XMLElement('div', null, array('class' => 'submit'));
			$submit->appendChild(Widget::input('submit', __('I promise, now take me to the login page'), 'submit'));

			$this->Form->appendChild($submit);

		}

	}
