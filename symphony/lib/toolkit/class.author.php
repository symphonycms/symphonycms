<?php
	
	Class Author{
			
		private $_fields;
		private $_accessSections;
				
		public function __construct($id=NULL){
			
			$this->_fields = array();
			$this->_accessSections = NULL; 
			
			if(!is_null($id) && is_numeric($id)){
				$this->loadAuthor($id);
			}
		}
		
		public function loadAuthor($id){
			if(!is_object(Symphony::Database()) || !is_numeric($id)) return false;

			$row = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_authors` WHERE `id` = '$id' LIMIT 1");

			if(!is_array($row) || empty($row)) return false;
			
			foreach($row as $key => $val){
				$this->set($key, $val);
			}
			
			return true;
		}
		
		public function loadAuthorFromUsername($username){
			if(!$row = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_authors` WHERE `username` = '$username' LIMIT 1")) return false;
			
			foreach($row as $key => $val)
				$this->set($key, $val);
			
			return true;			
		}

		public function verifyToken($token){
		
			if($this->get('auth_token_active') == 'no') return false;

			$t = General::substrmin(General::hash($this->get('username') . $this->get('password')), 8);
		
			if($t == $token) return true; 
		
			return false;
	
		}
	
		public function createAuthToken(){
			return General::substrmin(General::hash($this->get('username') . $this->get('password')), 8);	
		}
		
		public function isTokenActive(){
			return ($this->get('auth_token_active') == 'no' ? false : true);
		}
		
		public function isDeveloper(){
			return ($this->get('user_type') == 'developer');
		}
		
		public function isPrimaryAccount(){
			return ($this->get('primary') == 'yes');	
		}
		
		public function getFullName(){
			return $this->get('first_name') . ' ' . $this->get('last_name');
		}

		public function getAuthorAllowableSections(){
			
			if(!$sections = $this->get('allow_sections')) return array();
			
			$sections = preg_split('/,/', $sections, -1, PREG_SPLIT_NO_EMPTY);
			@array_map('trim', $sections);
			
			return (is_array($sections) && !empty($sections) ? $sections : array());
		}
		
		public function canAccessSection($section_id){
			if(!$id = $this->get('id')) return false;
			
			if($this->get('user_type') == 'developer') return true;

			$sections = $this->get('allow_sections');

			if($this->_accessSections == NULL){
				$sections = preg_split('/,/', $sections, -1, PREG_SPLIT_NO_EMPTY);
				$this->_accessSections = $sections;
			}
			
			if(in_array($section_id, $this->_accessSections)) return true;
			
			return false;
		}
		
		public function set($field, $value){
			$this->_fields[trim($field)] = trim($value);
		}

		public function get($field){
			if(!isset($this->_fields[$field]) || $this->_fields[$field] == '') return NULL;
			return $this->_fields[$field];
		}
		
		public function validate(&$errors){
			
			$errors = array();
			
			if($this->get('first_name') == '') $errors['first_name'] = __('First name is required');
			
			if($this->get('last_name') == '') $errors['last_name'] = __('Last name is required');
			
			if($this->get('email') == '') $errors['email'] = __('E-mail address is required');
			elseif(!General::validateString($this->get('email'), '/^[^@]+@[^\.@]+\.[^@]+$/i')) $errors['email'] = __('E-mail address entered is invalid');
			
			if($this->get('username') == '') $errors['username'] = __('Username is required');
			elseif($this->get('id')){			
				$current_username = Symphony::Database()->fetchVar('username', 0, "SELECT `username` FROM `tbl_authors` WHERE `id` = " . $this->get('id'));	
				if($current_username != $this->get('username') && Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_authors` WHERE `username` = '".$this->get('username')."' LIMIT 1"))
					$errors['username'] = __('Username is already taken');			
			}
				
			elseif(Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_authors` WHERE `username` = '".$this->get('username')."' LIMIT 1"))
				$errors['username'] = __('Username is already taken');
			
			if($this->get('password') == '') $errors['password'] = __('Password is required');
			
			return (empty($errors) ? true : false);
		}
		
		public function commit(){
						
			$fields = $this->_fields;	
				
			if(isset($fields['id'])){
				$id = $fields['id'];
				unset($fields['id']);
				return AuthorManager::edit($id, $fields);
						
			}
			
			else{
				return AuthorManager::add($fields);	
			}		
			
		}

	}

