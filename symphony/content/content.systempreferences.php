<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentSystemPreferences extends AdministrationPage{

		function __construct(&$parent){
			parent::__construct($parent);
			$this->setPageType('form');
			$this->setTitle('Symphony &ndash; Preferences');
		}
		
		## Overload the parent 'view' function since we dont need the switchboard logic
		function view(){
			
			$this->appendSubheading('Preferences');

		    $bIsWritable = true;
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

		    if(!is_writable(CONFIG)){
		        $this->pageAlert('The Symphony configuration file, <code>/manifest/config.php</code>, is not writable. You will not be able to save changes to preferences.', AdministrationPage::PAGE_ALERT_ERROR);
		        $bIsWritable = false;
		    }

			elseif($formHasErrors) $this->pageAlert('An error occurred while processing this form. <a href="#error">See below for details.</a>', AdministrationPage::PAGE_ALERT_ERROR);
/*
			### Website Settings ###
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'Website Settings'));

			$label = Widget::Label('Website Name');
			$label->appendChild(Widget::Input('settings[general][sitename]', General::Sanitize($this->_Parent->Configuration->get('sitename', 'general'))));		
			$group->appendChild((isset($this->_errors['general']['sitename']) ? $this->wrapFormElementWithError($label, $this->_errors['general']['sitename']) : $label));

			$label = Widget::Label();
			$input = Widget::Input('settings[public][maintenance_mode]', 'yes', 'checkbox');
			if($this->_Parent->Configuration->get('maintenance_mode', 'public') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Enable maintenance mode');
			$group->appendChild($label);
			
			$group->appendChild(new XMLElement('p', 'Maintenance mode will redirect all visitors, other than developers, to the maintenance page.', array('class' => 'help')));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$dateformat = $this->_Parent->Configuration->get('date_format', 'region');
			$label = Widget::Label('Date Format');
			$dateFormats = array( 			
				array('Y/m/d', $dateformat == 'Y/m/d', DateTimeObj::get('Y/m/d')),
				array('m/d/Y', $dateformat == 'm/d/Y', DateTimeObj::get('m/d/Y')),
				array('m/d/y', $dateformat == 'm/d/y', DateTimeObj::get('m/d/y')),
				array('d F Y', $dateformat == 'd F Y', DateTimeObj::get('d F Y')),
			);
			$label->appendChild(Widget::Select('settings[region][date_format]', $dateFormats));
			$div->appendChild($label);	
			
			$timeformat = $this->_Parent->Configuration->get('time_format', 'region');
			$label = Widget::Label('Time Format');
			$label->setAttribute('title', 'Local' . (date('I') == 1 ? ' daylight savings' : '') . ' time for ' . date_default_timezone_get());
			
			if(date('I') == 1) $label->appendChild(new XMLElement('i', 'Daylight savings time'));
			
			$timeformats = array(
				array('H:i:s', $timeformat == 'H:i:s', DateTimeObj::get('H:i:s')),
				array('H:i', $timeformat == 'H:i', DateTimeObj::get('H:i')),
				array('g:i:s a', $timeformat == 'g:i:s a', DateTimeObj::get('g:i:s a')),
				array('g:i a', $timeformat == 'g:i a', DateTimeObj::get('g:i a')),
			);
			$label->appendChild(Widget::Select('settings[region][time_format]', $timeformats));
			$div->appendChild($label);

			$group->appendChild($div);
			$this->Form->appendChild($group);	
			###

			### File Actions ###
			
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'File Actions'));			
			
			$ul = new XMLElement('ul', NULL, array('id' => 'file-actions', 'class' => 'group'));
			
			$li = new XMLElement('li');
			$div = new XMLElement('div', 'Create Ensemble', array('class' => 'label'));			
			$span = new XMLElement('span');
			$span->appendChild(new XMLElement('button', 'Create Ensemble', array('name' => 'action[export]')));
			$div->appendChild($span);
			$li->appendChild($div);		
			$li->appendChild(new XMLElement('p', 'Packages entire site as a <code>.zip</code> archive for download.', array('class' => 'help')));						
			$ul->appendChild($li);
			
			$li = new XMLElement('li');
			$div = new XMLElement('div', 'Uninstall Symphony', array('class' => 'label'));			
			$span = new XMLElement('span');
			$span->appendChild(new XMLElement('button', 'Uninstall Symphony', array('name' => 'action[uninstall]', 'class' => 'confirm')));
			$div->appendChild($span);
			$li->appendChild($div);		
			$li->appendChild(new XMLElement('p', 'Deletes all database tables, uninstall extensions and remove files created by the installer.', array('class' => 'help')));								
			$ul->appendChild($li);					
			
			$group->appendChild($ul);
			$this->Form->appendChild($group);
			###
*/

			###
			# Delegate: AddCustomPreferenceFieldsets
			# Description: Add Extension custom preferences. Use the $wrapper reference to append objects.
			$this->_Parent->ExtensionManager->notifyMembers('AddCustomPreferenceFieldsets', getCurrentPage(), array('wrapper' => &$this->Form));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			
			$attr = array('accesskey' => 's');
			if(!$bIsWritable) $attr['disabled'] = 'disabled';
			$div->appendChild(Widget::Input('action[save]', 'Save Changes', 'submit', $attr));

			$this->Form->appendChild($div);	

		}
		
		function action(){
			
			##Do not proceed if the config file is read only
		    if(!is_writable(CONFIG)) redirect($this->_Parent->getCurrentPageURL());
			
			/*if($_REQUEST['action'] == 'toggle-maintenance-mode'){			
				$value = ($this->_Parent->Configuration->get('maintenance_mode', 'public') == 'no' ? 'yes' : 'no');					
				$this->_Parent->Configuration->set('maintenance_mode', $value, 'public');
				$this->_Parent->saveConfig();
				redirect((isset($_REQUEST['redirect']) ? URL . '/symphony' . $_REQUEST['redirect'] : $this->_Parent->getCurrentPageURL() . '/'));
			}

			if(isset($_POST['action']['export'])):
				$this->_Parent->export();

			elseif(isset($_POST['action']['uninstall'])):

				//$this->_Parent->uninstall();

		        $this->_Parent->customError(E_USER_ERROR, 'Uninstall Successful', 'Extensions have been left intact, along with the <code>/symphony</code> folder and <code>index.php</code>. To complete the uninstall you will need to remove the aforementioned items manually.', false, true);

			endif;*/

			
			if(isset($_POST['action']['save'])){

				$settings = $_POST['settings'];
				
				//$this->_errors = array();
				
				//if(trim($settings['general']['sitename']) == '') $this->_errors['general']['sitename'] = 'This is a required field.';

				//else{

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

				//}
			}
		}	
	}

