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
					$td4 = Widget::TableData();

					if($about['author'][0] && is_array($about['author'][0])) {
						foreach($about['author'] as $i => $author) {

							if(isset($author['website']))
								$link = Widget::Anchor($author['name'], General::validateURL($author['website']));
							else if(isset($author['email']))
								$link = Widget::Anchor($author['name'], 'mailto:' . $author['email']);
							else
								$link = $author['name'];

							$value .= ($link instanceof XMLElement ? $link->generate() : $link)
							        . ($i != count($about['author']) - 1 ? ", " : "");
						}

						$td4->setValue($value);
					}
					else{

						if(isset($about['author']['website']))
							$link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));
						else if(isset($about['author']['email']))
							$link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);
						else
							$link = $about['author']['name'];

						$td4->setValue($link instanceof XMLElement ? $link->generate() : $link);
					}

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
	}
	
?>
