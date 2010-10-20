<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');

	Class contentSystemAuthors extends AdministrationPage{

		var $_Author;
		var $_errors;

		function __construct(&$parent){
			parent::__construct($parent);

			$this->_errors = array();
		}

		function __viewIndex(){

			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Authors'))));
			if (Administration::instance()->Author->isDeveloper()) $this->appendSubheading(__('Authors'), Widget::Anchor(__('Add an Author'), $this->_Parent->getCurrentPageURL().'new/', __('Add a new author'), 'create button'));
			else $this->appendSubheading(__('Authors'));

			$authors = AuthorManager::fetch();

			$aTableHead = array(

				array(__('Name'), 'col'),
				array(__('Email Address'), 'col'),
				array(__('Last Seen'), 'col'),

			);

			$aTableBody = array();

			if(!is_array($authors) || empty($authors)){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				$bOdd = true;
				foreach($authors as $a){

					if(intval($a->get('superuser')) == 1) $group = 'admin'; else $group = 'author';

					## Setup each cell
					if(Administration::instance()->Author->isDeveloper() || Administration::instance()->Author->get('id') == $a->get('id')) {
						$td1 = Widget::TableData(Widget::Anchor($a->getFullName(), $this->_Parent->getCurrentPageURL() . 'edit/' . $a->get('id') . '/', $a->get('username'), $group));
					} else {
						$td1 = Widget::TableData($a->getFullName(), 'inactive');
					}

					$td2 = Widget::TableData(Widget::Anchor($a->get('email'), 'mailto:'.$a->get('email'), 'Email this author'));

					if($a->get('last_seen') != NULL)
						$td3 = Widget::TableData(DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($a->get('last_seen'))));

					else
						$td3 = Widget::TableData('Unknown', 'inactive');

					if (Administration::instance()->Author->isDeveloper()) {
						if ($a->get('id') != Administration::instance()->Author->get('id')) $td3->appendChild(Widget::Input('items['.$a->get('id').']', NULL, 'checkbox'));
					}

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

			if(Administration::instance()->Author->isDeveloper()) {
				$tableActions = new XMLElement('div');
				$tableActions->setAttribute('class', 'actions');

				$options = array(
					array(NULL, false, __('With Selected...')),
					array('delete', false, __('Delete'))
				);

				$tableActions->appendChild(Widget::Select('with-selected', $options));
				$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

				$this->Form->appendChild($tableActions);
			}

		}

		function __actionIndex(){
			if($_POST['with-selected'] == 'delete'){

				$checked = @array_keys($_POST['items']);

				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting an author. ID is provided.
				//$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array('author_id' => $author_id));

				if(is_array($checked) && !empty($checked)) foreach($checked as $author_id) {
					$a = AuthorManager::fetchByID($author_id);
					if(is_object($a) && $a->get('id') != Administration::instance()->Author->get('id')) AuthorManager::delete($author_id);
				}

				redirect(URL . '/symphony/system/authors/');
			}
		}

		## Both the Edit and New pages need the same form
		function __viewNew(){
			$this->__form();
		}

		function __viewEdit(){
			$this->__form();
		}

		function __form(){

			require_once(TOOLKIT . '/class.field.php');

			## Handle unknow context
			if(!in_array($this->_context[0], array('new', 'edit'))) $this->_Parent->errorPageNotFound();

			if($this->_context[0] == 'new' && !Administration::instance()->Author->isDeveloper())
				$this->_Parent->customError(E_USER_ERROR, 'Access Denied', 'You are not authorised to access this page.');

			if(isset($this->_context[2])){
				switch($this->_context[2]){

					case 'saved':

						$this->pageAlert(
							__(
								'Author updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Authors</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									URL . '/symphony/system/authors/new/',
									URL . '/symphony/system/authors/'
								)
							),
							Alert::SUCCESS);

						break;

					case 'created':

						$this->pageAlert(
							__(
								'Author created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Authors</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									URL . '/symphony/system/authors/new/',
									URL . '/symphony/system/authors/'
								)
							),
							Alert::SUCCESS);

						break;

				}
			}

			$this->setPageType('form');

			$isOwner = false;

			if(isset($_POST['fields']))
				$author = $this->_Author;

			elseif($this->_context[0] == 'edit'){

				if(!$author_id = $this->_context[1]) redirect(URL . '/symphony/system/authors/');

				if(!$author = AuthorManager::fetchByID($author_id)){
					$this->_Parent->customError(E_USER_ERROR, 'Author not found', 'The author profile you requested does not exist.');
				}
			}

			else $author = new Author;

			if($this->_context[0] == 'edit' && $author->get('id') == Administration::instance()->Author->get('id')) $isOwner = true;

			if ($this->_context[0] == 'edit' && !$isOwner && !Administration::instance()->Author->isDeveloper())
				$this->_Parent->customError(E_USER_ERROR, 'Access Denied', 'You are not authorised to edit other authors.');



			$this->setTitle(__(($this->_context[0] == 'new' ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'), array(__('Symphony'), __('Authors'), $author->getFullName())));
			$this->appendSubheading(($this->_context[0] == 'new' ? __('Untitled') : $author->getFullName()));

			### Essentials ###
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Essentials')));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('First Name'));
			$label->appendChild(Widget::Input('fields[first_name]', $author->get('first_name')));
			$div->appendChild((isset($this->_errors['first_name']) ? $this->wrapFormElementWithError($label, $this->_errors['first_name']) : $label));


			$label = Widget::Label(__('Last Name'));
			$label->appendChild(Widget::Input('fields[last_name]', $author->get('last_name')));
			$div->appendChild((isset($this->_errors['last_name']) ? $this->wrapFormElementWithError($label, $this->_errors['last_name']) : $label));

			$group->appendChild($div);

			$label = Widget::Label(__('Email Address'));
			$label->appendChild(Widget::Input('fields[email]', $author->get('email')));
			$group->appendChild((isset($this->_errors['email']) ? $this->wrapFormElementWithError($label, $this->_errors['email']) : $label));

			$this->Form->appendChild($group);
			###

			### Login Details ###
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Login Details')));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('Username'));
			$label->appendChild(Widget::Input('fields[username]', $author->get('username'), NULL));
			$div->appendChild((isset($this->_errors['username']) ? $this->wrapFormElementWithError($label, $this->_errors['username']) : $label));

			// Only developers can change the user type. Primary account should NOT be able to change this
			if (Administration::instance()->Author->isDeveloper() && !$author->isPrimaryAccount()) {
				$label = Widget::Label(__('User Type'));

				$options = array(
					array('author', false, __('Author')),
					array('developer', $author->isDeveloper(), __('Developer'))
				);

				$label->appendChild(Widget::Select('fields[user_type]', $options));
				$div->appendChild($label);
			}

			$group->appendChild($div);

			$div = new XMLElement('div', NULL, array('class' => 'group'));

			if($this->_context[0] == 'edit') {
				$div->setAttribute('id', 'change-password');

				if(!Administration::instance()->Author->isDeveloper() || $isOwner === true){
					$div->setAttribute('class', 'triple group');

					$label = Widget::Label(__('Old Password'));
					if(isset($this->_errors['old-password'])) $label->setAttributeArray(array('class' => 'contains-error', 'title' => $this->_errors['old-password']));
					$label->appendChild(Widget::Input('fields[old-password]', NULL, 'password'));
					$div->appendChild((isset($this->_errors['old-password']) ? $this->wrapFormElementWithError($label, $this->_errors['old-password']) : $label));
				}
			}

			$label = Widget::Label(($this->_context[0] == 'edit' ? __('New Password') : __('Password')));
			$label->appendChild(Widget::Input('fields[password]', NULL, 'password'));
			$div->appendChild((isset($this->_errors['password']) ? $this->wrapFormElementWithError($label, $this->_errors['password']) : $label));

			$label = Widget::Label(($this->_context[0] == 'edit' ? __('Confirm New Password') : __('Confirm Password')));
			if(isset($this->_errors['password-confirmation'])) $label->setAttributeArray(array('class' => 'contains-error', 'title' => $this->_errors['password-confirmation']));
			$label->appendChild(Widget::Input('fields[password-confirmation]', NULL, 'password'));
			$div->appendChild($label);
			$group->appendChild($div);

			if($this->_context[0] == 'edit'){
				$group->appendChild(new XMLElement('p', __('Leave password fields blank to keep the current password'), array('class' => 'help')));
			}

			if(Administration::instance()->Author->isDeveloper()) {
				$label = Widget::Label();
				$input = Widget::Input('fields[auth_token_active]', 'yes', 'checkbox');
				if($author->get('auth_token_active') == 'yes') $input->setAttribute('checked', 'checked');
				$temp = URL . '/symphony/login/' . $author->createAuthToken() . '/';
				$label->setValue(__('%1$s Allow remote login via <a href="%2$s">%2$s</a>', array($input->generate(), $temp)));
				$group->appendChild($label);
			}
			$label = Widget::Label(__('Default Section'));

			$sectionManager = new SectionManager($this->_Parent);
			$sections = $sectionManager->fetch(NULL, 'ASC', 'sortorder');

			$options = array();

			if(is_array($sections) && !empty($sections))
				foreach($sections as $s) $options[] = array($s->get('id'), $author->get('default_section') == $s->get('id'), $s->get('name'));

			$label->appendChild(Widget::Select('fields[default_section]', $options));
			$group->appendChild($label);

			$this->Form->appendChild($group);
			###

			### Custom Language Selection ###
			$languages = Lang::getAvailableLanguages(Administration::instance()->ExtensionManager);
			if(count($languages) > 1) {

				// Get language names
				asort($languages);

				$group = new XMLElement('fieldset');
				$group->setAttribute('class', 'settings');
				$group->appendChild(new XMLElement('legend', __('Custom Preferences')));

				$div = new XMLElement('div');
				$div->setAttribute('class', 'group');

				$label = Widget::Label(__('Language'));

				$options = array(
					array(NULL, is_null($author->get('language')), __('System Default'))
				);

				foreach($languages as $code => $name) {
					$options[] = array($code, $code == $author->get('language'), $name);
				}
				$select = Widget::Select('fields[language]', $options);
				$label->appendChild($select);
				$group->appendChild($label);

				$this->Form->appendChild($group);
			}
			###

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$div->appendChild(Widget::Input('action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create Author')), 'submit', array('accesskey' => 's')));

			if($this->_context[0] == 'edit' && !$isOwner && !$author->isPrimaryAccount()){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this author'), 'type' => 'submit'));
				$div->appendChild($button);
			}

			$this->Form->appendChild($div);

		}

		function __actionNew(){

			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$fields = $_POST['fields'];

			$this->_Author = new Author;

				$this->_Author->set('user_type', $fields['user_type']);
				$this->_Author->set('primary', 'no');
				$this->_Author->set('email', $fields['email']);
				$this->_Author->set('username', $fields['username']);
				$this->_Author->set('first_name', General::sanitize($fields['first_name']));
				$this->_Author->set('last_name', General::sanitize($fields['last_name']));
				$this->_Author->set('last_seen', NULL);
				$this->_Author->set('password', (trim($fields['password']) == '' ? '' : General::hash($fields['password'])));
				$this->_Author->set('default_section', intval($fields['default_section']));
				$this->_Author->set('auth_token_active', ($fields['auth_token_active'] ? $fields['auth_token_active'] : 'no'));
				$this->_Author->set('language', $fields['language']);

				if($this->_Author->validate($this->_errors)):

					if($fields['password'] != $fields['password-confirmation']){
						$this->_errors['password'] = $this->_errors['password-confirmation'] = __('Passwords did not match');
					}

					elseif($author_id = $this->_Author->commit()){

						## TODO: Fix Me
						###
						# Delegate: Create
						# Description: Creation of a new Author. The ID of the author is provided.
						//$ExtensionManager->notifyMembers('Create', getCurrentPage(), array('author_id' => $author_id));

						redirect(URL."/symphony/system/authors/edit/$author_id/created/");

					}

				endif;

				if(is_array($this->_errors) && !empty($this->_errors))
					$this->pageAlert(__('There were some problems while attempting to save. Please check below for problem fields.'), Alert::ERROR);

				else
					$this->pageAlert(__('Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.', array(URL.'/symphony/system/log/')), Alert::ERROR);

			}
		}

		function __actionEdit(){

			if(!$author_id = $this->_context[1]) redirect(URL . '/symphony/system/authors/');

			$isOwner = ($author_id == Administration::instance()->Author->get('id'));

			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$fields = $_POST['fields'];
				$this->_Author = AuthorManager::fetchByID($author_id);
				$authenticated = false;

				if($fields['email'] != $this->_Author->get('email')) $changing_email = true;

				// Check the old password was correct
				if(isset($fields['old-password']) && strlen(trim($fields['old-password'])) > 0 && General::hash(trim($fields['old-password'])) == $this->_Author->get('password')) {
					$authenticated = true;
				}

				// Developers don't need to specify the old password, unless it's their own account
				elseif(Administration::instance()->Author->isDeveloper() && $isOwner === false){
					$authenticated = true;
				}

				$this->_Author->set('id', $author_id);

				if ($this->_Author->isPrimaryAccount() || ($isOwner && Administration::instance()->Author->isDeveloper())){
					$this->_Author->set('user_type', 'developer'); // Primary accounts are always developer, Developers can't lower their level
				}

				elseif (Administration::instance()->Author->isDeveloper() && isset($fields['user_type'])){
					$this->_Author->set('user_type', $fields['user_type']); // Only developer can change user type
				}

				$this->_Author->set('email', $fields['email']);
				$this->_Author->set('username', $fields['username']);
				$this->_Author->set('first_name', General::sanitize($fields['first_name']));
				$this->_Author->set('last_name', General::sanitize($fields['last_name']));
				$this->_Author->set('language', $fields['language']);

				if(trim($fields['password']) != ''){
					$this->_Author->set('password', General::hash($fields['password']));
					$changing_password = true;
				}
				$this->_Author->set('default_section', intval($fields['default_section']));
				$this->_Author->set('auth_token_active', ($fields['auth_token_active'] ? $fields['auth_token_active'] : 'no'));

				if($this->_Author->validate($this->_errors)):

					if(!$authenticated && ($changing_password || $changing_email)){
						if($changing_password) $this->_errors['old-password'] = __('Wrong password. Enter old password to change it.');
						elseif($changing_email) $this->_errors['old-password'] = __('Wrong password. Enter old one to change email address.');
					}

					elseif(($fields['password'] != '' || $fields['password-confirmation'] != '') && $fields['password'] != $fields['password-confirmation']){
						$this->_errors['password'] = $this->_errors['password-confirmation'] = __('Passwords did not match');
					}

					elseif($this->_Author->commit()){

						Symphony::Database()->delete('tbl_forgotpass', " `expiry` < '".DateTimeObj::getGMT('c')."' OR `author_id` = '".$author_id."' ");

						if($isOwner) $this->_Parent->login($this->_Author->get('username'), $this->_Author->get('password'), true);

						## TODO: Fix me
						###
						# Delegate: Edit
						# Description: After editing an author. ID of the author is provided.
						//$ExtensionManager->notifyMembers('Edit', getCurrentPage(), array('author_id' => $_REQUEST['id']));

						redirect(URL . '/symphony/system/authors/edit/' . $author_id . '/saved/');

					}

					else $this->pageAlert(__('Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.', array(URL.'/symphony/system/log/')), Alert::ERROR);

				endif;

			}

			elseif(@array_key_exists('delete', $_POST['action'])){

				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting an author. ID is provided.
				//$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array('author_id' => $author_id));

				if(!$isOwner) {
					AuthorManager::delete($author_id);
					redirect(URL . '/symphony/system/authors/');
				}

				else {
					$this->pageAlert(__('You cannot remove yourself as you are the active Author.'), Alert::ERROR);
				}
			}
		}

	}
