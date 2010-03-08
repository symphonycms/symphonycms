<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentSystemExtensions extends AdministrationPage{
		
		public function __call($name, $args){

			$type = NULL;
			
			switch($name){
				
				case '__viewIndex':
				case '__viewAll':
					$this->buildTable(
						ExtensionManager::instance()->listAll(), 
						true
					);
					return;
					break;
				
				case '__viewCore':
					$type = 'Core';
					break;
					
				case '__viewDatasources':
					$type = 'Data Source Template';
					break;
				
				case '__viewFields':
					$type = 'Field';
					break;
					
				case '__viewOther':
					$type = 'Other';
					break;
				
				default:
					throw new Exception('No such method or function "contentSystemExtensions::'.$name.'()".');
					break;
			}

			$this->buildTable(
				ExtensionManager::instance()->listByType($type)
			);
		}
		
/*		public function __viewIndex(){		
			$extensions = ExtensionManager::instance()->listAll();
			$this->buildTable($extensions, true);
		}
		
		public function __viewCore(){
			$extensions = ExtensionManager::instance()->listByType('Core');
			$this->buildTable($extensions);
		}
		
		function __viewDatasources(){
			$extensions = ExtensionManager::instance()->listByType('Data Source Template');		
			$this->buildTable($extensions);
		}
		
		function __viewFields(){
			$extensions = ExtensionManager::instance()->listByType('Field');
			$this->buildTable($extensions);
		}
		
		function __viewOther(){
			//this probably needs a new method that fetches all excluding the above...
			$extensions = ExtensionManager::instance()->listByType('Other');
			$this->buildTable($extensions);
		}
	*/	
		function buildTable($extensions, $prefixes=false){
		
			$this->setPageType('table');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Extensions'))));
			$this->appendSubheading(__('Extensions'));
			
			$path = URL . '/symphony/system/extensions/';
			$this->Form->setAttribute('action', Administration::instance()->getCurrentPageURL());
			
			$viewoptions = array(
				'subnav'	=> array(
					'All'				=>	$path,
					'Core'				=>	$path . 'core/',
					'Data Source Templates'	=>	$path . 'datasources/',
					'Fields'			=>	$path . 'fields/',
					'Other'				=>	$path . 'other/'
				)
			);
			
			$this->appendViewOptions($viewoptions);
			
			## Sort by extensions name:
			uasort($extensions, array('ExtensionManager', 'sortByName'));

			$aTableHead = array(
				array(__('Name'), 'col'),
				array(__('Version'), 'col'),
				array(__('Author'), 'col'),
				array(__('Status'), 'col')
			);	

			$aTableBody = array();

			if(!is_array($extensions) || empty($extensions)){

				$aTableBody = array(
					Widget::TableRow(
						array(
							Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))
						),
						'odd'
					)
				);
			}

			else{
				foreach($extensions as $name => $about){

					## Setup each cell
					$td1 = Widget::TableData((!empty($about['table-link']) && $about['status'] == EXTENSION_ENABLED ? Widget::Anchor($about['name'], Administration::instance()->getCurrentPageURL() . 'extension/' . trim($about['table-link'], '/') . '/') : $about['name']) . ($prefixes && isset($about['type']) ? ' <span class="label">&middot; ' . $about['type'][0] . '</span>' : NULL));			

					$td2 = Widget::TableData($about['version']);
					
					$link = $about['author']['name'];

					if(isset($about['author']['website'])){
						$link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));
					}
					
					elseif(isset($about['author']['email'])){
						$link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);	
					}
					
					$td3 = Widget::TableData($link);	
					
					$td3->appendChild(Widget::Input('items['.$name.']', 'on', 'checkbox'));
					
					switch ($about['status']) {
						case EXTENSION_ENABLED:
							$td4 = Widget::TableData(__('Enabled'), 'enabled');
							break;
							
						case EXTENSION_DISABLED:
							$td4 = Widget::TableData(__('Disabled'), 'disabled');
							break;
							
						case EXTENSION_NOT_INSTALLED:
							$td4 = Widget::TableData(__('Not Installed'), 'not-installed');
							break;
							
						case EXTENSION_REQUIRES_UPDATE:
							$td4 = Widget::TableData(__('Needs Update'), 'updatable');
					}

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow(
						array($td1, $td2, $td3, $td4), 
						($about['status'] == EXTENSION_NOT_INSTALLED ? 'inactive' : NULL)
					);

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

		function action(){
			$checked  = @array_keys($_POST['items']);

			if(isset($_POST['with-selected']) && is_array($checked) && !empty($checked)){

				$action = $_POST['with-selected'];

				switch($action){

					case 'enable':		

						## TODO: Fix Me
						###
						# Delegate: Enable
						# Description: Notifies of enabling Extension. Array of selected services is provided.
						#              This can not be modified.
						//ExtensionManager::instance()->notifyMembers('Enable', getCurrentPage(), array('services' => $checked));

						foreach($checked as $name){
							if(ExtensionManager::instance()->enable($name) === false) return;
						}
						break;


					case 'disable':

						## TODO: Fix Me
						###
						# Delegate: Disable
						# Description: Notifies of disabling Extension. Array of selected services is provided.
						#              This can be modified.
						//ExtensionManager::instance()->notifyMembers('Disable', getCurrentPage(), array('services' => &$checked));
	
						foreach($checked as $name){
							if(ExtensionManager::instance()->disable($name) === false) return;			
						}
						break;
					
					case 'uninstall':

						## TODO: Fix Me
						###
						# Delegate: Uninstall
						# Description: Notifies of uninstalling Extension. Array of selected services is provided.
						#              This can be modified.
						//ExtensionManager::instance()->notifyMembers('Uninstall', getCurrentPage(), array('services' => &$checked));
						
						foreach($checked as $name){
							if(ExtensionManager::instance()->uninstall($name) === false) return;			
						}
						
						break;
				}		

				redirect(Administration::instance()->getCurrentPageURL());
			}			
		}
		
		/*function __viewDetail(){
	
			$date = Administration::instance()->getDateObj();

			if(!$extension_name = $this->_context[1]) redirect(ADMIN_URL . '/system/extensions/');

			if(!$extension = ExtensionManager::instance()->about($extension_name)) Administration::instance()->customError(E_USER_ERROR, 'Extension not found', 'The Symphony Extension you were looking for, <code>'.$extension_name.'</code>, could not be found.', 'Please check it has been installed correctly.');
	
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

			if(!$extension_name = $this->_context[1]) redirect(ADMIN_URL . '/system/extensions/');

			if(!$extension = ExtensionManager::instance()->about($extension_name)) Administration::instance()->customError(E_USER_ERROR, 'Extension not found', 'The Symphony Extension you were looking for, <code>'.$extension_name.'</code>, could not be found.', 'Please check it has been installed correctly.');
			
			if(isset($_POST['action']['install']) && $extension['status'] == EXTENSION_NOT_INSTALLED){
				ExtensionManager::instance()->enable($extension_name);
			}
			
			elseif(isset($_POST['action']['update']) && $extension['status'] == EXTENSION_REQUIRES_UPDATE){
				ExtensionManager::instance()->enable($extension_name);	
			}
			
			elseif(isset($_POST['action']['uninstall']) && in_array($extension['status'], array(EXTENSION_ENABLED, EXTENSION_DISABLED))){
				ExtensionManager::instance()->uninstall($extension_name);	
			}
		}*/
	}
