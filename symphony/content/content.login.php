<?php

	Class contentLogin extends HTMLPage{

		private $_context;
		private $_invalidPassword;

		function __construct(){
			parent::__construct();

			$this->_invalidPassword = false;

			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

			$this->Html->setElementStyle('html');
			$this->Html->setDTD('<!DOCTYPE html>'); //PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"
			$this->Html->setAttribute('lang', Symphony::lang());
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addStylesheetToHead(ADMIN_URL . '/assets/css/login.css', 'screen', 40);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Login'))));

			Administration::instance()->Profiler->sample('Page template created', PROFILE_LAP);

		}

		function build($context=NULL){
			if($context) $this->_context = $context;
			if(isset($_REQUEST['action'])) $this->action();
			$this->view();
		}

		function view(){
			$emergency = false;
			if(isset($this->_context[0]) && in_array(strlen($this->_context[0]), array(6, 8))){
				$emergency = $this->__loginFromToken($this->_context[0]);
			}

			if(!$emergency && Administration::instance()->isLoggedIn()) redirect(ADMIN_URL . '/');

			$this->Form = Widget::Form('', 'post');

			$this->Form->appendChild(new XMLElement('h1', __('Symphony')));

			$fieldset = new XMLElement('fieldset');

			if($this->_context[0] == 'retrieve-password'):

				if(isset($this->_email_sent) && $this->_email_sent){
					$fieldset->appendChild(new XMLElement('p', __('An email containing a customised login link has been sent. It will expire in 2 hours.')));
					$this->Form->appendChild($fieldset);
				}

				else{

					$fieldset->appendChild(new XMLElement('p', __('Enter your email address to be sent a remote login link with further instructions for logging in.')));

					$label = Widget::Label(__('Email Address'));
					$label->appendChild(Widget::Input('email', $_POST['email']));

					$this->Body->setAttribute('onload', 'document.forms[0].elements.email.focus()');

					if(isset($this->_email_sent) && !$this->_email_sent){
						$div = new XMLElement('div', NULL, array('class' => 'invalid'));
						$div->appendChild($label);
						$div->appendChild(new XMLElement('p', __('There was a problem locating your account. Please check that you are using the correct email address.')));
						$fieldset->appendChild($div);
					}

					else $fieldset->appendChild($label);

					$this->Form->appendChild($fieldset);

					$div = new XMLElement('div', NULL, array('class' => 'actions'));
					$div->appendChild(Widget::Input('action[reset]', __('Send Email'), 'submit'));
					$this->Form->appendChild($div);

				}

			elseif($emergency):

				$fieldset->appendChild(new XMLElement('legend', __('New Password')));

				$label = Widget::Label(__('New Password'));
				$label->appendChild(Widget::Input('password', NULL, 'password'));
				$fieldset->appendChild($label);

				$label = Widget::Label(__('Confirm New Password'));
				$label->appendChild(Widget::Input('password-confirmation', NULL, 'password'));

				if($this->_mismatchedPassword){
					$div = new XMLElement('div', NULL, array('class' => 'invalid'));
					$div->appendChild($label);
					$div->appendChild(new XMLElement('p', __('The supplied password was rejected. Make sure it is not empty and that password matches password confirmation.')));
					$fieldset->appendChild($div);
				}

				else $fieldset->appendChild($label);

				$this->Form->appendChild($fieldset);

				$div = new XMLElement('div', NULL, array('class' => 'actions'));
				$div->appendChild(Widget::Input('action[change]', __('Save Changes'), 'submit'));
				if(!preg_match('@\/symphony\/login\/@i', $_SERVER['REQUEST_URI'])) $div->appendChild(Widget::Input('redirect', $_SERVER['REQUEST_URI'], 'hidden'));
				$this->Form->appendChild($div);

			else:

				$fieldset->appendChild(new XMLElement('legend', __('Login')));

				$label = Widget::Label(__('Username'));
				$label->appendChild(Widget::Input('username'));
				$fieldset->appendChild($label);

				$this->Body->setAttribute('onload', 'document.forms[0].elements.username.focus()');

				$label = Widget::Label(__('Password'));
				$label->appendChild(Widget::Input('password', NULL, 'password'));

				if($this->_invalidPassword){
					$div = new XMLElement('div', NULL, array('class' => 'invalid'));
					$div->appendChild($label);
					$div->appendChild(new XMLElement('p', __('The supplied password was rejected. <a href="%s">Retrieve password?</a>', array(ADMIN_URL . '/login/retrieve-password/'))));
					$fieldset->appendChild($div);
				}

				else $fieldset->appendChild($label);

				$this->Form->appendChild($fieldset);

				$div = new XMLElement('div', NULL, array('class' => 'actions'));
				$div->appendChild(Widget::Input('action[login]', __('Login'), 'submit'));
				if(!preg_match('@\/symphony\/login\/@i', $_SERVER['REQUEST_URI'])) $div->appendChild(Widget::Input('redirect', $_SERVER['REQUEST_URI'], 'hidden'));
				$this->Form->appendChild($div);

			endif;

		}

		function __loginFromToken($token){
			##If token is invalid, return to login page
			if(!Administration::instance()->loginFromToken($token)) return false;

			##If token is valid and it is not "emergency" login (forgotten password case), redirect to administration pages
			if(strlen($token) != 6) redirect(ADMIN_URL . '/'); // Regular token-based login

			##Valid, emergency token - ask user to change password
			return true;
		}

		function action(){

			if(isset($_POST['action'])){

				$actionParts = array_keys($_POST['action']);
				$action = end($actionParts);

				##Login Attempted
				if($action == 'login'):

					if(empty($_POST['username']) || empty($_POST['password']) || !Administration::instance()->login($_POST['username'], $_POST['password'])) {

						## TODO: Fix Me
						###
						# Delegate: LoginFailure
						# Description: Failed login attempt. Username is provided.
						//ExtensionManager::instance()->notifyMembers('LoginFailure', getCurrentPage(), array('username' => $_POST['username']));

						//$this->Body->appendChild(new XMLElement('p', 'Login invalid. <a href="'.ADMIN_URL . '/?forgot">Forgot your password?</a>'));
						//$this->_alert = 'Login invalid. <a href="'.ADMIN_URL . '/?forgot">Forgot your password?</a>';
						$this->_invalidPassword = true;
					}

					else{

						## TODO: Fix Me
						###
						# Delegate: LoginSuccess
						# Description: Successful login attempt. Username is provided.
						//ExtensionManager::instance()->notifyMembers('LoginSuccess', getCurrentPage(), array('username' => $_POST['username']));

						if(isset($_POST['redirect'])) redirect(URL . str_replace(parse_url(URL, PHP_URL_PATH), '', $_POST['redirect']));

						redirect(ADMIN_URL . '/');
					}

				##Reset of password requested
				elseif($action == 'reset'):

					$user = Symphony::Database()->query("SELECT id, email, first_name FROM `tbl_users` WHERE `email` = '%s'", array($_POST['email']));

					if($user->valid()){
						$user = $user->current();

						Symphony::Database()->delete('tbl_forgotpass', array(DateTimeObj::getGMT('c')), " `expiry` < '%s'");

						$token = Symphony::Database()->query("
							SELECT
								token
							FROM
								`tbl_forgotpass`
							WHERE
								expiry > '%s'
							AND
								user_id = %d
							",
							DateTimeObj::getGMT('c'),
							$user->id
						);

						if($token->valid()){
							$token = substr(md5(time() . rand(0, 200)), 0, 6);
							Symphony::Database()->insert('tbl_forgotpass',
								array(
									'user_id' => $user->id, 
									'token' => $token, 
									'expiry' => DateTimeObj::getGMT('c', time() + (120 * 60))
								)								
							);
						}
						
						// TODO: Is this really the best way to get the a Symphony Concierge email
						// Should Conceirge really be the Admin's name??
						$from = Symphony::Database()->query("SELECT `email` FROM `tbl_users` ORDER BY `id` ASC LIMIT 1");
						if($from->valid()) $from = $from->current();

						$this->_email_sent = General::sendEmail($user['email'],
									$from->email,
									__('Symphony Concierge'),
									__('New Symphony Account Password'),
									__('Hi %s,', array($user->first_name)) . self::CRLF .
									__('A new password has been requested for your account. Login using the following link, and change your password via the Users area:') . self::CRLF .
									self::CRLF . '	' . ADMIN_URL . "/login/$token/" . self::CRLF . self::CRLF .
									__('It will expire in 2 hours. If you did not ask for a new password, please disregard this email.') . self::CRLF . self::CRLF .
									__('Best Regards,') . self::CRLF .
									__('The Symphony Team'));


						## TODO: Fix Me
						###
						# Delegate: PasswordResetSuccess
						# Description: A successful password reset has taken place. User ID is provided
						//ExtensionManager::instance()->notifyMembers('PasswordResetSuccess', getCurrentPage(), array('user_id' => $user['id']));

					}

					else{

						## TODO: Fix Me
						###
						# Delegate: PasswordResetFailure
						# Description: A failed password reset has taken place. User ID is provided
						//ExtensionManager::instance()->notifyMembers('PasswordResetFailure', getCurrentPage(), array('user_id' => $user['id']));

						$this->_email_sent = false;
					}

				##Change of password requested
				elseif($action == 'change' && Administration::instance()->isLoggedIn()):

					if(empty($_POST['password']) || empty($_POST['password-confirmation']) || $_POST['password'] != $_POST['password-confirmation']){
						$this->_mismatchedPassword = true;
					}

					else{
						$user_id = Administration::instance()->User->id;

						$user = UserManager::fetchByID($user_id);

						$user->set('password', md5(Symphony::Database()->escape($_POST['password'])));

						if(!$user->commit() || !Administration::instance()->login($user->get('username'), $_POST['password'])){
							redirect(URL . "symphony/system/users/edit/{$user_id}/error/");
						}

						## TODO: Fix me
						###
						# Delegate: PasswordChanged
						# Description: After editing an User. ID of the User is provided.
						//ExtensionManager::instance()->notifyMembers('PasswordChanged', getCurrentPage(), array('user_id' => $user_id));

						redirect(ADMIN_URL . '/');
					}

				endif;
			}



			elseif($_REQUEST['action'] == 'resetpass' && isset($_REQUEST['token'])){

				$user = Symphony::Database()->query("
						SELECT
							u.id, u.email, u.first_name
						FROM
							`tbl_users` as u, `tbl_forgotpass` as t2
						WHERE
							t2.`token` = '%s'
						AND
							u.`id` = t2.`user_id`
						LIMIT 1
					",
					$_REQUEST['token']
				);

				if($user->valid()){
					$user = $user->current();

					$newpass = General::generatePassword();

					General::sendEmail($user->email,
								'noreply@symphony-cms.com',
								'Symphony Concierge',
								'RE: New Symphony Account Password',
								'Hi ' . $user['first_name']. ',' . self::CRLF .
								"As requested, here is your new Symphony User Password for '". URL ."'".self::CRLF ."	{$newpass}" . self::CRLF . self::CRLF .
								'Best Regards,' . self::CRLF .
								'The Symphony Team');

					Symphony::Database()->update('tbl_users', array('password' => md5($newpass)), array($user->id), "`id` = '%d'");
					Symphony::Database()->delete('tbl_forgotpass', array($user->id), " `user_id` = '%d'");


					## TODO: Fix Me
					###
					# Delegate: PasswordResetRequest
					# Description: User has requested a password reset. User ID is provided.
					//ExtensionManager::instance()->notifyMembers('PasswordResetRequest', getCurrentPage(), array('user_id' => $user['id']));

					$this->_alert = 'Password reset. Check your email';

				}
			}

		}

	}

