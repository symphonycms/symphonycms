<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	//require_once(TOOLKIT . '/class.sectionmanager.php');

	Class contentSystemRoles extends AdministrationPage{

		private $_errors;

		public function __construct(){
			parent::__construct();
			$this->_errors = array();		
		}
		
		public function __viewIndex(){
			
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('User Roles'))));
			$this->appendSubheading(
				__('User Roles'), Widget::Anchor(__('Add a Role'), Administration::instance()->getCurrentPageURL().'new/', __('Add a new Role'), 'create button')
			);
			
			$viewoptions = array(
				'subnav'	=>	array(
					'Users'	=> URL . '/symphony/system/users/',
					'Roles' => URL . '/symphony/system/roles/',
				)
			);
			
			$this->appendViewOptions($viewoptions);
			
			/* REMOVE AFTER TESTING */
			$roles = array();
			$roles[] = new Role;
			$roles[] = new Role;
			/* REMOVE AFTER TESTING */

			$aTableHead = array(
				array(__('Name'), 'col'),
				array(__('Description'), 'col'),
				array(__('Users'), 'col'),
			);	

			$aTableBody = array();

			if(!is_array($roles) || empty($roles)){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				$bOdd = true;
				foreach($roles as $r){
				
					/**********

						ASSUMING an $r object with a basic getter method
						for the following properties:
							- name
							- handle
							- description
							
						ALSO ASSUMING methods for grabbing user info:
							- getUserCount()
							
						NEED TO UPDATE the User filter link in $td3

					**********/	
					
					$td1 = Widget::TableData(
						Widget::Anchor(
							$r->get('name'),
							Administration::instance()->getCurrentPageURL() . 'edit/' . $r->get('handle') . '/',
							$r->get('name')
						)
					);
					
					$td2 = Widget::TableData(
						$r->get('description')
					);
					
					$td3 = Widget::TableData(
						Widget::Anchor(
							$r->getUserCount(),
							URL . '/symphony/system/users?filter=???????????'
						)
					);
					
					$td3->appendChild(
						Widget::Input(
							'items[' . $r->get('name') . ']',
							NULL,
							'checkbox'
						)
					);
					
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3), ($bOdd ? 'odd' : NULL));

					$bOdd = !$bOdd;

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
			
			/**********
			
				NEED TO ADD bulk operations like moving users
				
			**********/
			
			$options = array(
				array(NULL, false, 'With Selected...'),
				array('delete', false, 'Delete')								
			);
			
			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));
			
			$this->Form->appendChild($tableActions);		
			
		}
		
		public function __actionIndex(){
			if($_POST['with-selected'] == 'delete'){	 	
				
				$checked = @array_keys($_POST['items']);
				
				/**********
			
					WILL THERE be a deletion delegate?
				
				**********/		
				
				foreach($checked as $role){
				
					/**********
			
						NEED TO ADD code for deleting the role
				
					**********/
				
				}

				redirect(ADMIN_URL . '/system/roles/');
			}			
		}
		
		public function __viewNew(){
			$this->__form();
		}
		
		public function __viewEdit(){
			$this->__form();			
		}
		
		private function __form(){
		
			Administration::instance()->Page->addStylesheetToHead(URL . '/symphony/assets/css/symphony.roles.css', 'screen', 9126441);
			Administration::instance()->Page->addScriptToHead(URL . '/symphony/assets/js/jquery-ui.js', 9126442);
			Administration::instance()->Page->addScriptToHead(URL . '/symphony/assets/js/symphony.roles.js', 9126443);
			
			if(!in_array($this->_context[0], array('new', 'edit'))) throw new AdministrationPageNotFoundException;

			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
					
						$this->pageAlert(
							__(
								'Role updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Role</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									ADMIN_URL . '/system/roles/new/', 
									ADMIN_URL . '/system/roles/' 
								)
							), 
							Alert::SUCCESS);					

						break;
						
					case 'created':

						$this->pageAlert(
							__(
								'Role created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Roles</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									ADMIN_URL . '/system/roles/new/', 
									ADMIN_URL . '/system/roles/' 
								)
							), 
							Alert::SUCCESS);

						break;
					
				}
			}
			
			$this->setPageType('form');
			
			/**********

				INSERT logic for determining the current role and
				whether the user has permission to edit it

			**********/
			
			$this->setTitle(__(($this->_context[0] == 'new' ? '%1$s &ndash; %2$s &ndash; Untitled' : '%1$s &ndash; %2$s &ndash; %3$s'), array(__('Symphony'), __('Roles'), $EDIT_____rolename_____EDIT)));
			$this->appendSubheading(($this->_context[0] == 'new' ? __('Untitled') : $EDIT_____rolename_____EDIT));
			
			/**********

				ASSUMING a $role object with a basic getter method
				throughout the form below

			**********/
			$role = new Role(); /* REMOVE AFTER TESTING */
			
			/** ESSENTIALS **/
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Essentials')));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', $role->get('name')));
			$div->appendChild((isset($this->_errors['name']) ? Widget::wrapFormElementWithError($label, $this->_errors['name']) : $label));

			$label = Widget::Label(__('Description'));
			$label->appendChild(Widget::Input('fields[description]', $role->get('description')));
			$div->appendChild((isset($this->_errors['description']) ? Widget::wrapFormElementWithError($label, $this->_errors['description']) : $label));

			$group->appendChild($div);
			$this->Form->appendChild($group);	
			
			/** SECTION PERMISSIONS **/
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Permissions')));

			$sections = SectionManager::instance()->fetch(NULL, 'ASC', 'sortorder');

			if(!is_array($sections) || empty($sections)){

				$p = new XMLElement('p', 'No sections exist. ');
				$p->appendChild(Widget::Anchor(
					__('Create one'),
					URL . '/symphony/sections/new/'
				));
				$group->appendChild($p);

			}
			
			else{
				$thead = array(
					array(__('Section'), 'col'),
					array(__('Create'), 'col', array('class' => 'checkbox')),
					array(__('Edit'), 'col'),
				);
				$tbody = array();
				$bOdd = true;
				
				$td1 = Widget::TableData(__('Global Permissions'));
				
				$td2 = Widget::TableData(Widget::Input(
				'global-add',
				'1',
				'checkbox'
				), 'checkbox');
				 
				$td3 = Widget::TableData(NULL, 'edit');
				$td3->appendChild(new XMLElement('p', NULL, array('class' => 'global-slider')));
				$td3->appendChild(new XMLElement('span', 'n/a'));
				 
				$tbody[] = Widget::TableRow(array($td1, $td2, $td3), 'global');
				
				foreach($sections as $section){
					
					$td1 = Widget::TableData(
						$section->get('name')
					);
					
					$td2 = Widget::TableData(
					 	Widget::Input(
							"fields[permissions][{$section->get('handle')}][create]",
							'1',
							'checkbox',
							($permissions['create'] == 1 ? array('checked' => 'checked') : NULL)
						),
						'checkbox'
					);
					
					$td3 = Widget::TableData(NULL, 'edit');
					$td3->appendChild(new XMLElement('p', NULL, array('class' => 'slider')));
					$span = new XMLElement('span');
					$span->setSelfClosingTag(false);
					$td3->appendChild($span);
					 
					$td3->appendChild(Widget::Input(
					'fields[permissions][' . $section->get('handle') .'][edit]',
					(isset($permissions['edit']) ? $permissions['edit'] : '0'),
					'hidden'
					));
					
					$tbody[] = Widget::TableRow(array($td1, $td2, $td3), ($bOdd ? 'odd' : NULL));

					$bOdd = !$bOdd;

				}
				
				$table = Widget::Table(
					Widget::TableHead($thead),
					NULL, 
					Widget::TableBody($tbody),
					'role-permissions'
				);
				
				$group->appendChild($table);
			
			}

			/**********

				BUILD view list and set up permissions interface

			**********/
				
			$this->Form->appendChild($group);
			
			/** FORM ACTIONS **/
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$div->appendChild(Widget::Input('action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create User')), 'submit', array('accesskey' => 's')));
			
			if($this->_context[0] == 'edit' && !$isOwner){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this role')));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);
			
		}

		public function __actionNew(){
			
			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$fields = $_POST['fields'];

				/**********

					BUILD the role object and save it

				**********/

				if(is_array($this->_errors) && !empty($this->_errors)){
					$this->pageAlert(__('There were some problems while attempting to save. Please check below for problem fields.'), Alert::ERROR);
				}	
				else{
					$this->pageAlert(__('Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.', array(ADMIN_URL . '/system/log/')), Alert::ERROR);
				}
				
			}
		}
		
		public function __actionEdit(){

			/**********

				BUILD the role object and edit or delete it

			**********/
				
		}
		
	}
	
	/**********

		REMOVE this once the role manager is built

	**********/
	
	Class Role {
		function get($property){
			switch($property){
				case 'name':
					return 'Ninja';
					break;
				case 'description':
					return 'Ultra-stealthy Symphony assassins';
					break;
			}
		}
		
		function getUserCount(){
			return '9';
		}
	}
