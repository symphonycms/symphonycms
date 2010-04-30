<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentSystemExtensions extends AdministrationPage{

		function __viewIndex(){
			$this->setPageType('table');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Extensions'))));
			$this->appendSubheading(__('Extensions'));
			
			$this->Form->setAttribute('action', URL . '/symphony/system/extensions/');
			
			$ExtensionManager = $this->_Parent->ExtensionManager; 		
			$extensions = $ExtensionManager->listAll();
			
			## Sort by extensions name:
			uasort($extensions, array('ExtensionManager', 'sortByName'));

			$aTableHead = array(
				array(__('Name'), 'col'),
				array(__('Enabled'), 'col'),
				array(__('Version'), 'col'),
				array(__('Author'), 'col'),
			);	

			$aTableBody = array();

			if(!is_array($extensions) || empty($extensions)){
				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				foreach($extensions as $name => $about){

					## Setup each cell
					$td1 = Widget::TableData((!empty($about['table-link']) && $about['status'] == EXTENSION_ENABLED ? Widget::Anchor($about['name'], $this->_Parent->getCurrentPageURL() . 'extension/' . trim($about['table-link'], '/') . '/') : $about['name']));			
					$td2 = Widget::TableData(($about['status'] == EXTENSION_ENABLED ? __('Yes') : __('No')));
					$td3 = Widget::TableData($about['version']);
					
					$link = $about['author']['name'];

					if(isset($about['author']['website']))
						$link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));

					elseif(isset($about['author']['email']))
						$link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);	
						
					$td4 = Widget::TableData($link);	
					
					$td4->appendChild(Widget::Input('items['.$name.']', 'on', 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3, $td4), ($about['status'] == EXTENSION_NOT_INSTALLED ? 'inactive' : NULL));		

				}
			}

			$table = Widget::Table(
								Widget::TableHead($aTableHead), 
								NULL, 
								Widget::TableBody($aTableBody)
						);

			$this->Form->appendChild($table);
			
			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			
			$options = array(
				array(NULL, false, __('With Selected...')),
				array('enable', false, __('Enable')),
				array('disable', false, __('Disable')),
				array('uninstall', false, __('Uninstall'), 'confirm'),
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
			
			$this->Form->appendChild($tableActions);			
			
			
		}

		function __actionIndex(){
			$checked  = @array_keys($_POST['items']);

			if(isset($_POST['with-selected']) && is_array($checked) && !empty($checked)){
				
				try{
					switch($_POST['with-selected']){

						case 'enable':		

							## TODO: Fix Me
							###
							# Delegate: Enable
							# Description: Notifies of enabling Extension. Array of selected services is provided.
							#              This can not be modified.
							//$ExtensionManager->notifyMembers('Enable', getCurrentPage(), array('services' => $checked));

							foreach($checked as $name){
								if($this->_Parent->ExtensionManager->enable($name) === false) return;
							}
							break;


						case 'disable':

							## TODO: Fix Me
							###
							# Delegate: Disable
							# Description: Notifies of disabling Extension. Array of selected services is provided.
							#              This can be modified.
							//$ExtensionManager->notifyMembers('Disable', getCurrentPage(), array('services' => &$checked));
	
							foreach($checked as $name){
								$this->_Parent->ExtensionManager->disable($name);
							}
							break;
					
						case 'uninstall':

							## TODO: Fix Me
							###
							# Delegate: Uninstall
							# Description: Notifies of uninstalling Extension. Array of selected services is provided.
							#              This can be modified.
							//$ExtensionManager->notifyMembers('Uninstall', getCurrentPage(), array('services' => &$checked));
						
							foreach($checked as $name){
								$this->_Parent->ExtensionManager->uninstall($name);
							}
						
							break;
					}		

					redirect($this->_Parent->getCurrentPageURL());
				}
				catch(Exception $e){
					$this->pageAlert($e->getMessage(), Alert::ERROR);
				}
			}			
		}
		
		/*function __viewDetail(){
	
			$date = $this->_Parent->getDateObj();

			if(!$extension_name = $this->_context[1]) redirect(URL . '/symphony/system/extensions/');

			if(!$extension = $this->_Parent->ExtensionManager->about($extension_name)) $this->_Parent->customError(E_USER_ERROR, 'Extension not found', 'The Symphony Extension you were looking for, <code>'.$extension_name.'</code>, could not be found.', 'Please check it has been installed correctly.');
	
			$link = $extension['author']['name'];

			if(isset($extension['author']['website']))
				$link = Widget::Anchor($extension['author']['name'], General::validateURL($extension['author']['website']));

			elseif(isset($extension['author']['email']))
				$link = Widget::Anchor($extension['author']['name'], 'mailto:' . $extension['author']['email']);

			$this->setPageType('form');	
			$this->setTitle('Symphony &ndash; Extensions &ndash; ' . $extension['name']);
			$this->appendSubheading($extension['name']);

			$fieldset = new XMLElement('fieldset');

			$dl = new XMLElement('dl');

			$dl->appendChild(new XMLElement('dt', 'Author'));
			$dl->appendChild(new XMLElement('dd', (is_object($link) ? $link->generate(false) : $link)));

			$dl->appendChild(new XMLElement('dt', 'Version'));
			$dl->appendChild(new XMLElement('dd', $extension['version']));	

			$dl->appendChild(new XMLElement('dt', 'Release Date'));
			$dl->appendChild(new XMLElement('dd', $date->get(true, true, strtotime($extension['release-date']))));	

			$fieldset->appendChild($dl);

			$fieldset->appendChild((is_object($extension['description']) ? $extension['description'] : new XMLElement('p', strip_tags(General::sanitize($extension['description'])))));
			
			switch($extension['status']){
				
				case EXTENSION_DISABLED:
				case EXTENSION_ENABLED:
					$fieldset->appendChild(new XMLElement('p', '<strong>Uninstall this Extension, which will remove anything created by it, but will leave the original files intact. To fully remove it, you will need to manually delete the files.</strong>'));		
					$fieldset->appendChild(Widget::Input('action[uninstall]', 'Uninstall Extension', 'submit'));				
					break;
					
				case EXTENSION_REQUIRES_UPDATE:
					$fieldset->appendChild(new XMLElement('p', '<strong>Note: This Extension is currently disabled as it is ready for updating. Use the button below to complete the update process.</strong>'));
					$fieldset->appendChild(Widget::Input('action[update]', 'Update Extension', 'submit'));				
					break;
					
				case EXTENSION_NOT_INSTALLED:
					$fieldset->appendChild(new XMLElement('p', '<strong>Note: This Extension has not been installed. If you wish to install it, please use the button below.</strong>'));
					$fieldset->appendChild(Widget::Input('action[install]', 'Install Extension', 'submit'));				
					break;					
			
			}

			$this->Form->appendChild($fieldset);			
		}
		
		function __actionDetail(){

			if(!$extension_name = $this->_context[1]) redirect(URL . '/symphony/system/extensions/');

			if(!$extension = $this->_Parent->ExtensionManager->about($extension_name)) $this->_Parent->customError(E_USER_ERROR, 'Extension not found', 'The Symphony Extension you were looking for, <code>'.$extension_name.'</code>, could not be found.', 'Please check it has been installed correctly.');
			
			if(isset($_POST['action']['install']) && $extension['status'] == EXTENSION_NOT_INSTALLED){
				$this->_Parent->ExtensionManager->enable($extension_name);
			}
			
			elseif(isset($_POST['action']['update']) && $extension['status'] == EXTENSION_REQUIRES_UPDATE){
				$this->_Parent->ExtensionManager->enable($extension_name);	
			}
			
			elseif(isset($_POST['action']['uninstall']) && in_array($extension['status'], array(EXTENSION_ENABLED, EXTENSION_DISABLED))){
				$this->_Parent->ExtensionManager->uninstall($extension_name);	
			}
		}*/
	}
	
?>