<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');

	Class eventLogin extends Event{
		
		public static function about(){
			
			$description = new XMLElement('p', 'This is an event that displays basic login details (such as their real name, username and author type) if the person viewing the site have been authenticated by logging in to Symphony. It is useful if you want to do something special with the site if the person viewing it is an authenticated member.');
					
			return array(
						 'name' => 'Login Info',
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://www.pointybeard.com',
										   'email' => 'alistair@pointybeard.com'),
						 'version' => '1.4',
						 'release-date' => '2008-01-17',
						 'trigger-condition' => 'action[login] field or an already valid Symphony cookie',						 
						 'recognised-fields' => array(
													array('username', true), 
													array('password', true)
												));						 
		}
				
		public function load(){			
			return $this->__trigger();
		}

		public static function documentation(){
			return new XMLElement('p', 'This is an event that displays basic login details (such as their real name, username and author type) if the person viewing the site have been authenticated by logging in to Symphony. It is useful if you want to do something special with the site if the person viewing it is an authenticated member.');
		}
		
		protected function __trigger(){
			
			## Cookies only show up on page refresh. This flag helps in making sure the correct XML is being set
			$loggedin = false;
			
			if(isset($_REQUEST['action']['login'])){					
				$username = $_REQUEST['username'];
				$password = $_REQUEST['password'];			
				$loggedin = $this->_Parent->login($username, $password);
			}
			
			else $loggedin = $this->_Parent->isLoggedIn();
			
			if($loggedin){
				$result = new XMLElement('login-info');
				$result->setAttribute('logged-in', 'true');
				
				$author = $this->_Parent->Author;
				
				$result->setAttributeArray(array('id' => $author->get('id'), 
												  'user-type' => $author->get('user_type'),
												  'primary-account' => $author->get('primary')
											));
	
				$fields = array(
					'name' => new XMLElement('name', $author->getFullName()),
					'username' => new XMLElement('username', $author->get('username')),
					'email' => new XMLElement('email', $author->get('email'))
				);
	
				if($author->isTokenActive()) $fields['author-token'] = new XMLElement('author-token', $author->createAuthToken());
	
				if($section = $this->_Parent->Database->fetchRow(0, "SELECT `id`, `handle`, `name` FROM `tbl_sections` WHERE `id` = '".$author->get('default_section')."' LIMIT 1")){
					$default_section = new XMLElement('default-section', $section['name']);
					$default_section->setAttributeArray(array('id' => $section['id'], 'handle' => $section['handle']));
					$fields['default-section'] = $default_section;
				}
				
				foreach($fields as $f) $result->appendChild($f);	
			}
			
			else{
				
				$result = new XMLElement('user');
				$result->setAttribute('logged-in', 'false');
			}
			
			return $result;
			
		}
	}

?>