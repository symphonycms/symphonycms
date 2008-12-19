<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentSystemPreferences extends AdministrationPage{

		function __construct(&$parent){
			parent::__construct($parent);
			$this->setPageType('form');
			$this->setTitle(__('%s &ndash; %s', array(__('Symphony'), __('Preferences'))));
		}
		
		## Overload the parent 'view' function since we dont need the switchboard logic
		function view(){
			
			$this->appendSubheading(__('Preferences'));

		    $bIsWritable = true;
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

		    if(!is_writable(CONFIG)){
		        $this->pageAlert(__('The Symphony configuration file, <code>/manifest/config.php</code>, is not writable. You will not be able to save changes to preferences.'), AdministrationPage::PAGE_ALERT_ERROR);
		        $bIsWritable = false;
		    }

			elseif($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), AdministrationPage::PAGE_ALERT_ERROR);

			###
			# Delegate: AddCustomPreferenceFieldsets
			# Description: Add Extension custom preferences. Use the $wrapper reference to append objects.
			$this->_Parent->ExtensionManager->notifyMembers('AddCustomPreferenceFieldsets', getCurrentPage(), array('wrapper' => &$this->Form));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			
			$attr = array('accesskey' => 's');
			if(!$bIsWritable) $attr['disabled'] = 'disabled';
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);	

		}
		
		function action(){
			
			##Do not proceed if the config file is read only
		    if(!is_writable(CONFIG)) redirect($this->_Parent->getCurrentPageURL());

			###
			# Delegate: CustomActions
			# Description: This is where Extensions can hook on to custom actions they may need to provide.
			$this->_Parent->ExtensionManager->notifyMembers('CustomActions', getCurrentPage());

			
			if(isset($_POST['action']['save'])){

				$settings = $_POST['settings'];

				###
				# Delegate: Save
				# Description: Saving of system preferences.
				$this->_Parent->ExtensionManager->notifyMembers('Save', getCurrentPage(), array('settings' => &$settings, 'errors' => &$this->_errors));

				if(!is_array($this->_errors) || empty($this->_errors)){

					foreach($settings as $set => $values) {
						foreach($values as $key => $val) {
							$this->_Parent->Configuration->set($key, $val, $set);
						}
					}

					$this->_Parent->saveConfig();

					redirect($this->_Parent->getCurrentPageURL());
				}
			}
		}	
	}

