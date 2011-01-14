<?php

	/**
	 * @package content
	 */

	/**
	 * The default Symphony login page that is shown to users who attempt
	 * to access `SYMPHONY_URL` but are not logged in. This page has logic
	 * to allow users to reset their passwords should they forget.
	 */
	Class contentLogin extends HTMLPage{

		public $_invalidPassword = false;

		public function __construct(&$parent){
			parent::__construct($parent);

			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

			$this->Html->setElementStyle('html');
			$this->Html->setDTD('<!DOCTYPE html>'); //PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"
			$this->Html->setAttribute('lang', Lang::get());
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/basic.css', 'screen', 40);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/login.css', 'screen', 40);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Login'))));

			Administration::instance()->Profiler->sample('Page template created', PROFILE_LAP);
		}

		public function build($context=NULL){
			if($context) $this->_context = $context;
			if(isset($_REQUEST['action'])) $this->action();
			$this->view();
		}

		public function view(){
			$emergency = false;
			if(isset($this->_context[0]) && in_array(strlen($this->_context[0]), array(6, 8))){
				$emergency = $this->__loginFromToken($this->_context[0]);
			}

			if(!$emergency && Administration::instance()->isLoggedIn()) redirect(SYMPHONY_URL);

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
					$div->appendChild(new XMLElement('button', __('Send Email'), array('name' => 'action[reset]', 'type' => 'submit')));
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
				$div->appendChild(new XMLElement('button', __('Save Changes'), array('name' => 'action[change]', 'type' => 'submit')));
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
					$div->appendChild(new XMLElement('p', __('The supplied password was rejected. <a href="%s">Retrieve password?</a>', array(SYMPHONY_URL.'/login/retrieve-password/'))));
					$fieldset->appendChild($div);
				}

				else $fieldset->appendChild($label);

				$this->Form->appendChild($fieldset);

				$div = new XMLElement('div', NULL, array('class' => 'actions'));
				$div->appendChild(new XMLElement('button', __('Login'), array('name' => 'action[login]', 'type' => 'submit', 'accesskey' => 's')));
				if(!preg_match('@\/symphony\/login\/@i', $_SERVER['REQUEST_URI'])) $div->appendChild(Widget::Input('redirect', $_SERVER['REQUEST_URI'], 'hidden'));
				$this->Form->appendChild($div);

			endif;

			$this->Body->appendChild($this->Form);

		}

		public function __loginFromToken($token){
			##If token is invalid, return to login page
			if(!Administration::instance()->loginFromToken($token)) return false;

			##If token is valid and it is not "emergency" login (forgotten password case), redirect to administration pages
			if(strlen($token) != 6) redirect(SYMPHONY_URL); // Regular token-based login

			##Valid, emergency token - ask user to change password
			return true;
		}

		public function action(){

			if(isset($_POST['action'])){

				$actionParts = array_keys($_POST['action']);
				$action = end($actionParts);

				##Login Attempted
				if($action == 'login'):

					if(empty($_POST['username']) || empty($_POST['password']) || !Administration::instance()->login($_POST['username'], $_POST['password'])) {
						/**
						 * A failed login attempt into the Symphony backend
						 *
						 * @delegate AuthorLoginFailure
						 * @since Symphony 2.2
						 * @param string $context
						 * '/login/'
						 * @param string $username
						 *  The username of the Author who attempted to login.
						 */
						Symphony::ExtensionManager()->notifyMembers('AuthorLoginFailure', '/login/', array('username' => $_POST['username']));
						$this->_invalidPassword = true;
					}

					else{
						/**
						 * A successful login attempt into the Symphony backend
						 *
						 * @delegate AuthorLoginSuccess
						 * @since Symphony 2.2
						 * @param string $context
						 * '/login/'
						 * @param string $username
						 *  The username of the Author who logged in.
						 */
						Symphony::ExtensionManager()->notifyMembers('AuthorLoginSuccess', '/login/', array('username' => $_POST['username']));

						if(isset($_POST['redirect'])) redirect(URL . str_replace(parse_url(URL, PHP_URL_PATH), '', $_POST['redirect']));

						redirect(SYMPHONY_URL);
					}

				##Reset of password requested
				elseif($action == 'reset'):

					$author = Symphony::Database()->fetchRow(0, "SELECT `id`, `email`, `first_name` FROM `tbl_authors` WHERE `email` = '".Symphony::Database()->cleanValue($_POST['email'])."'");

					if(!empty($author)){

						Symphony::Database()->delete('tbl_forgotpass', " `expiry` < '".DateTimeObj::getGMT('c')."' ");

						if(!$token = Symphony::Database()->fetchVar('token', 0, "SELECT `token` FROM `tbl_forgotpass` WHERE `expiry` > '".DateTimeObj::getGMT('c')."' AND `author_id` = ".$author['id'])){
							$token = substr(General::hash(time() . rand(0, 1000)), 0, 6);
							Symphony::Database()->insert(array('author_id' => $author['id'], 'token' => $token, 'expiry' => DateTimeObj::getGMT('c', time() + (120 * 60))), 'tbl_forgotpass');
						}

						try{
							$email = Email::create();

							$email->recipients = $author['email'];
							$email->subject = __('New Symphony Account Password');
							$email->text_plain = __('Hi %s,', array($author['first_name'])) . self::CRLF .
									__('A new password has been requested for your account. Login using the following link, and change your password via the Authors area:') . self::CRLF .
									self::CRLF . '	' . SYMPHONY_URL . "/login/{$token}/" . self::CRLF . self::CRLF .
									__('It will expire in 2 hours. If you did not ask for a new password, please disregard this email.') . self::CRLF . self::CRLF .
									__('Best Regards,') . self::CRLF .
									__('The Symphony Team');

							$email->send();
							$this->_email_sent = true;
						}
						catch(Exception $e) {}

						catch(EmailGatewayException $e){
							throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
						}

						/**
						 * When a password reset has occured and after the Password
						 * Reset email has been sent.
						 *
						 * @delegate AuthorPostPasswordResetSuccess
						 * @since Symphony 2.2
						 * @param string $context
						 * '/login/'
						 * @param integer $author_id
						 *  The ID of the Author who requested the password reset
						 */
						Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetSuccess', '/login/', array('author_id' => $author['id']));
					}

					else{

						/**
						 * When a password reset has been attempted, but Symphony doesn't
						 * recognise the credentials the user has given.
						 *
						 * @delegate AuthorPostPasswordResetFailure
						 * @since Symphony 2.2
						 * @param string $context
						 * '/login/'
						 * @param string $email
						 *  The santizied Email of the Author who tried to request the password reset
						 */
						Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetFailure', '/login/', array('email' => Symphony::Database()->cleanValue($_POST['email'])));

						$this->_email_sent = false;
					}

				##Change of password requested
				elseif($action == 'change' && Administration::instance()->isLoggedIn()):

					if(empty($_POST['password']) || empty($_POST['password-confirmation']) || $_POST['password'] != $_POST['password-confirmation']){
						$this->_mismatchedPassword = true;
					}

					else{
						$author_id = Administration::instance()->Author->get('id');
						$author = AuthorManager::fetchByID($author_id);

						$author->set('password', General::hash(Symphony::Database()->cleanValue($_POST['password'])));

						if(!$author->commit() || !Administration::instance()->login($author->get('username'), $_POST['password'])){
							redirect(SYMPHONY_URL . "/system/authors/edit/{$author_id}/error/");
						}

						/**
						 * When an Author changes their password as the result of a login
						 * with an emergency token (ie. forgot password). Just after their
						 * new password has been set successfully
						 *
						 * @delegate AuthorPostPasswordChange
						 * @since Symphony 2.2
						 * @param string $context
						 * '/login/'
						 * @param integer $author_id
						 *  The ID of the Author who has just changed their password
						 */
						Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordChange', '/login/', array('author_id' => $author_id));

						redirect(SYMPHONY_URL);
					}

				endif;
			}

			elseif($_REQUEST['action'] == 'resetpass' && isset($_REQUEST['token'])){

				$author = Symphony::Database()->fetchRow(0, "SELECT t1.`id`, t1.`email`, t1.`first_name`
						FROM `tbl_authors` as t1, `tbl_forgotpass` as t2
					 	WHERE t2.`token` = '".Symphony::Database()->cleanValue($_REQUEST['token'])."' AND t1.`id` = t2.`author_id`
					 	LIMIT 1");

				if(!empty($author)){

					$newpass = General::generatePassword();

					General::sendEmail(
						$author['email'],
						Symphony::Database()->fetchVar('email', 0, "SELECT `email` FROM `tbl_authors` ORDER BY `id` ASC LIMIT 1"),
						__('Symphony Concierge'),
						__('New Symphony Account Password'),
						__('Hi %s,', array($author['first_name'])) . self::CRLF .
						__("As requested, here is your new Symphony Author Password for ") . URL . " " .self::CRLF ." $newpass" . self::CRLF . self::CRLF .
						__('Best Regards,') . self::CRLF .
						__('The Symphony Team')
					);

					Symphony::Database()->update(array('password' => General::hash($newpass)), 'tbl_authors', " `id` = '".$author['id']."' LIMIT 1");
					Symphony::Database()->delete('tbl_forgotpass', " `author_id` = '".$author['id']."'");

					/**
					 * Just after a Forgot Password email has been sent to the Author
					 * who has requested a password reset.
					 *
					 * @delegate AuthorPostPasswordResetRequest
					 * @since Symphony 2.2
					 * @param string $context
					 * '/login/'
					 * @param integer $author_id
					 *  The ID of the Author who has requested their password be reset
					 */
					Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetRequest', '/login/', array('author_id' => $author['id']));

					$this->_alert = __('Password reset. Check your email');

				}
			}

		}

	}
