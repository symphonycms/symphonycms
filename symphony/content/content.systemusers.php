<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
 	//require_once(TOOLKIT . '/class.sectionmanager.php');

	Class contentSystemUsers extends AdministrationPage{

		private $_User;
		private $_errors;

		public function __construct(){
			parent::__construct();
			$this->_errors = array();		
		}
		
		public function __viewIndex(){
			
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Users'))));
			$this->appendSubheading(
				__('Users'), Widget::Anchor(__('Add a User'), Administration::instance()->getCurrentPageURL().'new/', __('Add a new User'), 'create button')
			);
			
			$viewoptions = array(
				'subnav'	=>	array(
					'Users'	=> URL . '/symphony/system/users/',
					'Roles' => URL . '/symphony/system/roles/',
				)
			);
			
			$this->appendViewOptions($viewoptions);
			
		    $users = UserManager::fetch();

			$aTableHead = array(
				array(__('Name'), 'col'),
				array(__('Email Address'), 'col'),
				array(__('Last Seen'), 'col'),
			);	

			$aTableBody = array();

			if(!is_array($users) || empty($users)){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				$bOdd = true;
				foreach($users as $u){
					
					## Setup each cell
					$td1 = Widget::TableData(
						Widget::Anchor($u->getFullName(), Administration::instance()->getCurrentPageURL() . 'edit/' . $u->get('id') . '/', $u->get('username'))
					);
						
					$td2 = Widget::TableData(Widget::Anchor($u->get('email'), 'mailto:'.$u->get('email'), 'Email this user'));
					
					if($u->get('last_seen') != NULL){
						$td3 = Widget::TableData(DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($u->get('last_seen'))));
					}	
					else{
						$td3 = Widget::TableData('Unknown', 'inactive');
					}
					
					$td3->appendChild(Widget::Input('items['.$u->get('id').']', NULL, 'checkbox'));
					
					## Add a row to the body array, assigning each cell to the row
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
				
				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting an User. ID is provided.
				//ExtensionManager::instance()->notifyMembers('Delete', getCurrentPage(), array('user_id' => $user_id));		
				
				foreach($checked as $user_id){
					if(Administration::instance()->User->id == $user_id) continue;
					UserManager::delete($user_id);
				}

				redirect(ADMIN_URL . '/system/users/');
			}			
		}
		
		## Both the Edit and New pages need the same form
		public function __viewNew(){
			$this->__form();
		}
		
		public function __viewEdit(){
			$this->__form();			
		}
		
		private function __form(){
			$layout = new Layout(3, '1:1:1');
			
			require_once(TOOLKIT . '/class.field.php');	
			
			## Handle unknow context
			if(!in_array($this->_context[0], array('new', 'edit'))) throw new AdministrationPageNotFoundException;

			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
					
						$this->pageAlert(
							__(
								'User updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Users</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									ADMIN_URL . '/system/users/new/', 
									ADMIN_URL . '/system/users/' 
								)
							), 
							Alert::SUCCESS);					

						break;
						
					case 'created':

						$this->pageAlert(
							__(
								'User created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Users</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									ADMIN_URL . '/system/users/new/', 
									ADMIN_URL . '/system/users/' 
								)
							), 
							Alert::SUCCESS);

						break;
					
				}
			}
			
			## DEPRECATED? $this->setPageType('form');
			
			$isOwner = false;
			
			if(isset($_POST['fields']))
				$user = $this->_User;			
			
			elseif($this->_context[0] == 'edit'){
			
				if(!$user_id = $this->_context[1]) redirect(ADMIN_URL . '/system/users/');
			
				if(!$user = UserManager::fetchByID($user_id)){
					Administration::instance()->customError(E_USER_ERROR, 'User not found', 'The user profile you requested does not exist.');
				}
			}
			
			else $user = new User;

			if($this->_context[0] == 'edit' && $user->get('id') == Administration::instance()->User->id) $isOwner = true;
			
			$this->setTitle(__(($this->_context[0] == 'new' ? '%1$s &ndash; %2$s &ndash; Untitled' : '%1$s &ndash; %2$s &ndash; %3$s'), array(__('Symphony'), __('Users'), $user->getFullName())));
			$this->appendSubheading(($this->_context[0] == 'new' ? __('Untitled') : $user->getFullName()));			
			
			### Essentials ###			
			$fieldset = Widget::Fieldset(__('Essentials'));

			$label = Widget::Label(__('First Name'));
			$label->appendChild(Widget::Input('fields[first_name]', $user->get('first_name')));
			$fieldset->appendChild((isset($this->_errors['first_name']) ? $this->wrapFormElementWithError($label, $this->_errors['first_name']) : $label));


			$label = Widget::Label(__('Last Name'));
			$label->appendChild(Widget::Input('fields[last_name]', $user->get('last_name')));
			$fieldset->appendChild((isset($this->_errors['last_name']) ? $this->wrapFormElementWithError($label, $this->_errors['last_name']) : $label));

			$label = Widget::Label(__('Email Address'));	
			$label->appendChild(Widget::Input('fields[email]', $user->get('email')));
			$fieldset->appendChild((isset($this->_errors['email']) ? $this->wrapFormElementWithError($label, $this->_errors['email']) : $label));

			$layout->appendToCol($fieldset, 1);
			###

			### Login Details ###
			$fieldset = Widget::Fieldset(__('Login Details'));

			$label = Widget::Label(__('Username'));
			$label->appendChild(Widget::Input('fields[username]', $user->get('username'), NULL));
			$fieldset->appendChild((isset($this->_errors['username']) ? $this->wrapFormElementWithError($label, $this->_errors['username']) : $label));

			$label = Widget::Label(__('Default Section'));
			
		    //$sections = SectionManager::instance()->fetch(NULL, 'ASC', 'sortorder');
		
			$options = array();
			
			//if(is_array($sections) && !empty($sections)) 
			foreach(new SectionIterator as $s){
				$options[] = array($s->handle, $user->get('default_section') == $s->handle, $s->name);
			}
			
			$label->appendChild(Widget::Select('fields[default_section]', $options));
			$fieldset->appendChild($label);
			
			if($this->_context[0] == 'edit') {
				$fieldset->setAttribute('id', 'change-password');
			}

			$label = Widget::Label(($this->_context[0] == 'edit' ? __('New Password') : __('Password')));		
			$label->appendChild(Widget::Input('fields[password]', NULL, 'password'));
			$fieldset->appendChild((isset($this->_errors['password']) ? $this->wrapFormElementWithError($label, $this->_errors['password']) : $label));

			$label = Widget::Label(($this->_context[0] == 'edit' ? __('Confirm New Password') : __('Confirm Password')));
			if(isset($this->_errors['password-confirmation'])) $label->setAttributeArray(array('class' => 'contains-error', 'title' => $this->_errors['password-confirmation']));	
			$label->appendChild(Widget::Input('fields[password-confirmation]', NULL, 'password'));
			$fieldset->appendChild($label);

			if($this->_context[0] == 'edit'){
				$fieldset->appendChild(new XMLElement('p', __('Leave password fields blank to keep the current password'), array('class' => 'help')));
			}
			
			$label = Widget::Label();
			$input = Widget::Input('fields[auth_token_active]', 'yes', 'checkbox');
			if($user->get('auth_token_active') == 'yes') $input->setAttribute('checked', 'checked');
			$temp = ADMIN_URL . '/login/' . $user->createAuthToken() . '/';
			$label->setValue(__('%1$s Allow remote login via <a href="%2$s">%2$s</a>', array($input->generate(), $temp)));
			$fieldset->appendChild($label);
			
			$layout->appendToCol($fieldset, 2);
			
			###
			
			### Custom Language Selection ###
			$languages = Lang::getAvailableLanguages(true);
			if(count($languages > 1)) {
				
				// Get language names
				asort($languages);
				
				$fieldset = Widget::Fieldset(__('Custom Preferences'));
	
				$label = Widget::Label(__('Language'));

				$options = array(
					array(NULL, is_null($user->get('language')), __('System Default'))
				);
				
				foreach($languages as $code => $name) {
					$options[] = array($code, $code == $user->get('language'), $name);
				}
				$select = Widget::Select('fields[language]', $options);			
				$label->appendChild($select);
				$fieldset->appendChild($label);
				
				$layout->appendToCol($fieldset, 3);		
	
				$this->Form->appendChild($layout->generate());
			}
			###			
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$div->appendChild(Widget::Input('action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create User')), 'submit', array('accesskey' => 's')));
			
			if($this->_context[0] == 'edit' && !$isOwner){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this user')));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);
			
		}

		public function __actionNew(){
			
			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$fields = $_POST['fields'];

			    $this->_User = new User;
			
				$this->_User->email = $fields['email'];
				$this->_User->username = $fields['username'];
				$this->_User->first_name = General::sanitize($fields['first_name']);
				$this->_User->last_name = General::sanitize($fields['last_name']);
				$this->_User->last_seen = NULL;
				$this->_User->password = (trim($fields['password']) == '' ? NULL : md5($fields['password']));
				$this->_User->default_section = intval($fields['default_section']);
				$this->_User->auth_token_active = ($fields['auth_token_active'] ? $fields['auth_token_active'] : 'no');
				$this->_User->language = $fields['language'];

				###
				# Delegate: PreCreate
				# Description: Just before creation of a new User. User object, fields and error array provided
				ExtensionManager::instance()->notifyMembers(
					'PreCreate', '/system/users/new/', 
					array(
						'fields' => $fields, 
						'user' => &$this->_User, 
						'errors' => &$this->_errors
					)
				);
				
				if(empty($this->_errors) && $this->_User->validate($this->_errors)):
					
					if($fields['password'] != $fields['password-confirmation']){
						$this->_errors['password'] = $this->_errors['password-confirmation'] = __('Passwords did not match');			
					}
				
					elseif($user_id = $this->_User->commit()){

						###
						# Delegate: PostCreate
						# Description: Just after creation of a new User. The ID of the User is provided.
						ExtensionManager::instance()->notifyMembers('PostCreate', '/system/users/new/', array('user' => $this->_User));
						
			  		   redirect(ADMIN_URL . "/system/users/edit/{$this->_User->id}/created/");	
	
					}
					
				endif;

				if(is_array($this->_errors) && !empty($this->_errors)){
					$this->pageAlert(__('There were some problems while attempting to save. Please check below for problem fields.'), Alert::ERROR);
				}	
				else{
					$this->pageAlert(__('Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.', array(ADMIN_URL . '/system/log/')), Alert::ERROR);
				}
				
			}
		}
		
		public function __actionEdit(){

			if(!$user_id = $this->_context[1]) redirect(ADMIN_URL . '/system/users/');

			$isOwner = ($user_id == Administration::instance()->User->id);

			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$fields = $_POST['fields'];
				
			    $this->_User = UserManager::fetchByID($user_id);

				if($fields['email'] != $this->_User->email) $changing_email = true;

				$this->_User->id = $user_id;
					
				$this->_User->email = $fields['email'];
				$this->_User->username = $fields['username'];
				$this->_User->first_name = General::sanitize($fields['first_name']);
				$this->_User->last_name = General::sanitize($fields['last_name']);
				
				if(trim($fields['password']) != ''){
					$this->_User->password = md5($fields['password']);
					$changing_password = true;
				}
				
				$this->_User->default_section = intval($fields['default_section']);
				$this->_User->auth_token_active = ($fields['auth_token_active'] ? $fields['auth_token_active'] : 'no');
				$this->_User->language = $fields['language'];
				
				###
				# Delegate: PreSave
				# Description: Just before creation of a new User. User object, fields and error array provided
				ExtensionManager::instance()->notifyMembers(
					'PreSave', '/system/users/edit/', 
					array(
						'fields' => $fields, 
						'user' => &$this->_User, 
						'errors' => &$this->_errors
					)
				);				
				
				if(empty($this->_errors) && $this->_User->validate($this->_errors)):

					if(($fields['password'] != '' || $fields['password-confirmation'] != '') && $fields['password'] != $fields['password-confirmation']){
						$this->_errors['password'] = $this->_errors['password-confirmation'] = __('Passwords did not match');
					}
				
					elseif($this->_User->commit()){					
						
						Symphony::Database()->delete('tbl_forgotpass', " `expiry` < '".DateTimeObj::getGMT('c')."' OR `user_id` = '{$user_id}' ");
						
						if($isOwner){
							Administration::instance()->login($this->_User->username, $this->_User->password, true);
						}

						###
						# Delegate: PostSave
						# Description: Just after creation of a new User. The ID of the User is provided.
						ExtensionManager::instance()->notifyMembers('PostSave', '/system/users/edit/', array('user' => $this->_User));	

		  		    	redirect(ADMIN_URL . "/system/users/edit/{$this->_User->id}/saved/");

					}
				
					else{
						$this->pageAlert(
							__('Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.', array(ADMIN_URL . '/system/log/')), 
							Alert::ERROR
						);
					}

				endif;

			}
			
			elseif(@array_key_exists('delete', $_POST['action'])){	 	

				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting an User. ID is provided.
				//ExtensionManager::instance()->notifyMembers('Delete', getCurrentPage(), array('user_id' => $user_id));		

				UserManager::delete($user_id);

				redirect(ADMIN_URL . '/system/users/');
			}						
		}
		
	}
