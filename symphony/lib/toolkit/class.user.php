<?php

	Class User{

		private $_fields;

		public function __construct($id=NULL){

			$this->_fields = array();

			if(!is_null($id)){
				$this->loadUser($id);
			}
		}

		public function loadUser($id){
			$result = Symphony::Database()->query("SELECT * FROM `tbl_users` WHERE `id` = '%s' LIMIT 1", array($id));

			if (!$result->valid()) return false;

			$row = $result->current();

			foreach ($row as $key => $value) {
				$this->$key = $value;
			}

			return true;
		}

		public function loadUserFromUsername($username){
			$result = Symphony::Database()->query("
					SELECT
						u.*
					FROM
						`tbl_users` AS u
					WHERE
						u.username = '%s'
					LIMIT
						1
				",
				array($username)
			);

			if(!$result->valid()) return null;

			$row = $result->current();

			foreach($row as $key => $val){
				$this->$key = $val;
			}

			return true;
		}

		public function verifyToken($token){

			if($this->auth_token_active == 'no') return false;

			$t = General::substrmin(md5($this->username . $this->password), 8);

			if($t == $token){
				return true;
			}

			return false;

		}

		public function createAuthToken(){
			return General::substrmin(md5($this->username . $this->password), 8);
		}

		public function isTokenActive(){
			return ($this->auth_token_active == 'no' ? false : true);
		}

		public function getFullName(){
			return "{$this->first_name} {$this->last_name}";
		}

		// NOTICE - set() and get() have been will be removed in a later release in favour of using the
		// __get() and __set() magic functions
		public function set($field, $value){
			$this->$field = $value;
		}

		public function get($field){
			return $this->$field;
		}

		public function __get($name){
			if(!isset($this->_fields[$name]) || strlen(trim($this->_fields[$name])) == 0) return NULL;
			return $this->_fields[$name];
		}

		public function __set($name, $value){
			$this->_fields[trim($name)] = $value;
		}

		public function __isset($name){
			return isset($this->_fields[$name]);
		}

		public function validate(&$errors){

			$errors = array();

			if(is_null($this->first_name)) $errors['first_name'] = __('First name is required');

			if(is_null($this->last_name)) $errors['last_name'] = __('Last name is required');

			if(is_null($this->email)) $errors['email'] = __('E-mail address is required');
			elseif(!General::validateString($this->email, '/^[^@]+@[^\.@]+\.[^@]+$/i')) $errors['email'] = __('E-mail address entered is invalid');

			if(is_null($this->username)) $errors['username'] = __('Username is required');
			elseif($this->id){
				$result = Symphony::Database()->query("SELECT `username` FROM `tbl_users` WHERE `id` = %d", array($this->id));
				$current_username = $result->resultColumn('username');

				if($current_username != $this->username && Symphony::Database()->query("SELECT `id` FROM `tbl_users` WHERE `username` = '%s'", array($this->username))->valid())
					$errors['username'] = __('Username is already taken');
			}

			elseif(Symphony::Database()->query("SELECT `id` FROM `tbl_users` WHERE `username` = '%s'", array($this->username))->valid()){
				$errors['username'] = __('Username is already taken');
			}

			if(is_null($this->password)) $errors['password'] = __('Password is required');

			return (empty($errors) ? true : false);
		}

		public function commit(){

			$fields = $this->_fields;

			if(isset($this->id) && !is_null($this->id)){
				unset($fields['id']);
				return UserManager::edit($this->id, $fields);
			}

			$this->id = UserManager::add($fields);
			return $this->id;
		}

	}

