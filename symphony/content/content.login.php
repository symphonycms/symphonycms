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

		public function __construct(){
			parent::__construct();
			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

			$this->Html->setElementStyle('html');
			$this->Html->setDTD('<!DOCTYPE html>'); //PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"
			$this->Html->setAttribute('lang', Lang::get());
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.css', 'screen', 30);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.forms.css', 'screen', 31);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.frames.css', 'screen', 32);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Login'), __('Symphony'))));

			$this->Body->setAttribute('id', 'login');

			Symphony::Profiler()->sample('Page template created', PROFILE_LAP);
		}

		public function build($context=NULL){
			if($context) $this->_context = $context;
			if(isset($_REQUEST['action'])) $this->action();
			$this->view();
		}

		public function view(){
			$emergency = false;
			if(isset($this->_context[0]) && in_array(strlen($this->_context[0]), array(6, 8))){
				if(!$this->__loginFromToken($this->_context[0])) {
					if(Administration::instance()->isLoggedIn()) redirect(SYMPHONY_URL);
				}
			}

			$this->Form = Widget::Form(SYMPHONY_URL . '/login/', 'post');
			$this->Form->setAttribute('class', 'frame');

			$this->Form->appendChild(new XMLElement('h1', __('Symphony')));

			$fieldset = new XMLElement('fieldset');

			if($this->_context[0] == 'retrieve-password'):

				$this->Form->setAttribute('action', SYMPHONY_URL.'/login/retrieve-password/');

				if(isset($this->_email_sent) && $this->_email_sent){
					$fieldset->appendChild(new XMLElement('p', __('An email containing a customised login link has been sent. It will expire in 2 hours.')));
					$this->Form->appendChild($fieldset);
				}

				else{

					$fieldset->appendChild(new XMLElement('p', __('Enter your email address to be sent a remote login link with further instructions for logging in.')));

					$label = Widget::Label(__('Email Address'));
					$label->appendChild(Widget::Input('email', $_POST['email'], 'text', array('autofocus' => 'autofocus')));
					if(isset($this->_email_sent) && !$this->_email_sent){
						$label = Widget::Error($label, __('There was a problem locating your account. Please check that you are using the correct email address.'));
					}
					$fieldset->appendChild($label);

					$this->Form->appendChild($fieldset);

					$div = new XMLElement('div', NULL, array('class' => 'actions'));
					$div->appendChild(new XMLElement('button', __('Send Email'), array('name' => 'action[reset]', 'type' => 'submit')));
					$this->Form->appendChild($div);

				}

			else:

				$fieldset->appendChild(new XMLElement('legend', __('Login')));

				$label = Widget::Label(__('Username'));
				$username = Widget::Input('username', $_POST['username']);
				if(!$this->_invalidPassword) {
					$username->setAttribute('autofocus', 'autofocus');
				}
				$label->appendChild($username);
				if(isset($_POST['action'], $_POST['action']['login']) && empty($_POST['username'])) {
					$username->setAttribute('autofocus', 'autofocus');
					$label = Widget::Error($label, __('No username was entered.'));
				}
				$fieldset->appendChild($label);

				$label = Widget::Label(__('Password'));
				$password = Widget::Input('password', NULL, 'password');
				$label->appendChild($password);
				if($this->_invalidPassword){
					$password->setAttribute('autofocus', 'autofocus');
					$label =  Widget::Error($label, __('The supplied password was rejected.') .
						' <br /><a href="' . SYMPHONY_URL.'/login/retrieve-password/">'. __('Retrieve password?') . '</a>'
					);
				}
				$fieldset->appendChild($label);

				$this->Form->appendChild($fieldset);

				$div = new XMLElement('div', NULL, array('class' => 'actions'));
				$div->appendChild(new XMLElement('button', __('Login'), array('name' => 'action[login]', 'type' => 'submit', 'accesskey' => 's')));
				$this->Form->appendChild($div);

			endif;

			$this->Body->appendChild($this->Form);

		}

		public function action(){
			if(isset($_POST['action'])){

				$actionParts = array_keys($_POST['action']);
				$action = end($actionParts);

				// Login Attempted
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

				// Reset of password requested
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
							$email->text_plain = __('Hi %s,', array($author['first_name'])) . PHP_EOL .
									__('A new password has been requested for your account. Login using the following link, and change your password via the Authors area:') . PHP_EOL .
									PHP_EOL . '	' . SYMPHONY_URL . "/login/{$token}/" . PHP_EOL . PHP_EOL .
									__('It will expire in 2 hours. If you did not ask for a new password, please disregard this email.') . PHP_EOL . PHP_EOL .
									__('Best Regards,') . PHP_EOL .
									__('The Symphony Team');

							$email->send();
							$this->_email_sent = true;
						}
						catch(Exception $e) {}

						catch(EmailGatewayException $e){
							throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
						}

						/**
						 * When a password reset has occurred and after the Password
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
						 *  The sanitised Email of the Author who tried to request the password reset
						 */
						Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetFailure', '/login/', array('email' => Symphony::Database()->cleanValue($_POST['email'])));

						$this->_email_sent = false;
					}

				endif;
			}
		}

		public function __loginFromToken($token){
			// If token is invalid, return to login page
			if(!Administration::instance()->loginFromToken($token)) return false;

			// If token is valid and is an 8 char shortcut
			if(strlen($token) != 6) redirect(SYMPHONY_URL); // Regular token-based login

			return false;
		}

	}
