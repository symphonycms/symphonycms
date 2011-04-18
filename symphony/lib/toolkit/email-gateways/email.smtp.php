<?php
	/**
	 * @package email-gateways
	 */

	require_once(TOOLKIT . '/class.emailgateway.php');
	require_once(TOOLKIT . '/class.emailhelper.php');
	require_once(TOOLKIT . '/class.smtp.php');

	/**
	 * One of the two core email gateways.
	 * Provides simple SMTP functionalities.
	 * Supports AUTH LOGIN, SSL and TLS.
	 *
	 * @author Huib Keemink, Michael Eichelsdoerfer
	 */
	Class SMTPGateway extends EmailGateway{

		protected $_SMTP;
		protected $_host;
		protected $_port;
		protected $_protocol = 'tcp';
		protected $_secure = false;
		protected $_auth = false;
		protected $_user;
		protected $_pass;

		/**
		 * Returns the name, used in the dropdown menu in the preferences pane.
		 *
		 * @return array
		 */
		public function about(){
			return array(
				'name' => __('SMTP'),
			);
		}

		/**
		 * Constructor. Sets basic default values based on preferences.
		 *
		 * @return void
		 */
		public function __construct(){
			parent::__construct();
			$this->setSenderEmailAddress(Symphony::Configuration()->get('from_address', 'email_smtp') ? Symphony::Configuration()->get('from_address', 'email_smtp') : 'noreply@' . HTTP_HOST);
			$this->setSenderName(Symphony::Configuration()->get('from_name', 'email_smtp') ? Symphony::Configuration()->get('from_name', 'email_smtp') : 'Symphony');
			$this->setSecure(Symphony::Configuration()->get('secure', 'email_smtp'));
			$this->setHost(Symphony::Configuration()->get('host', 'email_smtp'));
			$this->setPort(Symphony::Configuration()->get('port', 'email_smtp'));
			if(Symphony::Configuration()->get('auth', 'email_smtp') == 1){
				$this->setAuth(true);
				$this->setUser(Symphony::Configuration()->get('username', 'email_smtp'));
				$this->setPass(Symphony::Configuration()->get('password', 'email_smtp'));
			}
		}

		/**
		 * Send an email using an SMTP server
		 *
		 * @return bool
		 */
		public function send(){

			$this->validate();

			$settings = array();
			if($this->_auth == true){
				$settings['username'] = $this->_user;
				$settings['password'] = $this->_pass;
			}
			$settings['secure'] = $this->_secure;
			try{
				$this->_SMTP = new SMTP($this->_host, $this->_port, $settings);

				// Encode recipient names (but not any numeric array indexes)
				foreach($this->_recipients as $name => $email){
					$name = is_numeric($name) ? $name : EmailHelper::qEncode($name);
					$recipients[$name] =  $email;
				}

				// Combine keys and values into a recipient list (name <email>, name <email>).
				$recipient_list = EmailHelper::arrayToList($recipients);

				// Encode the subject
				$this->_subject	 = EmailHelper::qEncode($this->_subject);

				// Encode the sender name if it's not empty
				$this->_sender_name = empty($this->_sender_name) ? NULL : EmailHelper::qEncode($this->_sender_name);

				// Build the 'From' header field body
				$from = empty($this->_sender_name)
						? $this->_sender_email_address
						: $this->_sender_name . ' <' . $this->_sender_email_address . '>';

				// Build the 'Reply-To' header field body
				if(!empty($this->_reply_to_email_address)){
					if(!empty($this->_reply_to_name)){
						$reply_to = EmailHelper::qEncode($this->_reply_to_name) . ' <'.$this->_reply_to_email_address.'>';
					}
					else{
						$reply_to = $this->_reply_to_email_address;
					}
				}
				if(!empty($reply_to)){
					$this->_header_fields = array_merge(array(
						'Reply-To' => $reply_to,
					),$this->_header_fields);
				}

				// Build the body text using attachments, html-text and plain-text.
				$this->prepareMessageBody();

				// Build the header fields
				$this->_header_fields = array_merge(Array(
					'Message-ID'   => sprintf('<%s@%s>', md5(uniqid()) , HTTP_HOST),
					'Date'		   => date('r'),
					'From'		   => $from,
					'Subject'	   => $this->_subject,
					'To'		   => $recipient_list,
					'X-Mailer'	   => 'Symphony Email Module',
					'MIME-Version' => '1.0'
				),$this->_header_fields);

				// Set header fields and fold header field bodies
				foreach($this->_header_fields as $name => $body){
					$this->_SMTP->setHeader($name, EmailHelper::fold($body));
				}

				// Send the email
				$this->_SMTP->sendMail($this->_sender_email_address, $this->_recipients, $this->_subject, $this->_body);
			}
			catch(SMTPException $e){
				throw new EmailGatewayException($e->getMessage());
			}
			return true;
		}

		/**
		 * Sets the host to connect to.
		 *
		 * @return void
		 */
		public function setHost($host = null){
			if($host === null){
				$host = '127.0.0.1';
			}
			if(substr($host, 0, 6) == 'ssl://'){
				$this->_protocol = 'ssl';
				$this->_secure = 'ssl';
				$host = substr($host, 6);
			}
			$this->_host = $host;
		}

		/**
		 * Sets the port, used in the connection.
		 *
		 * @return void
		 */
		public function setPort($port = null){
			if($port == null){
				if($this->_protocol == 'ssl'){
					$port = 465;
				}
				else{
					$port = 25;
				}
			}
			$this->_port = $port;
		}

		/**
		 * Sets the username to use with AUTH LOGIN
		 *
		 * @param string $user
		 * @return void
		 */
		public function setUser($user = null){
			$this->_user = $user;
		}

		/**
		 * Sets the password to use with AUTH LOGIN
		 *
		 * @param string $pass
		 * @return void
		 */
		public function setPass($pass = null){
			$this->_pass = $pass;
		}

		/**
		 * Use AUTH login or no auth.
		 *
		 * @param bool $auth
		 * @return void
		 */
		public function setAuth($auth = false){
			$this->_auth = $auth;
		}

		/**
		 * Sets the encryption used.
		 *
		 * @param string|null|false $secure The encryption used. Can be 'ssl', 'tls' or false
		 * @return void
		 */
		public function setSecure($secure = null){
			if($secure == 'tls'){
				$this->_protocol = 'tcp';
				$this->_secure = 'tls';
			}
			elseif($secure == 'ssl'){
				$this->_protocol = 'ssl';
				$this->_secure = 'ssl';
			}
			else{
				$this->_protocol = 'tcp';
				$this->_secure = 'no';
			}
		}

		/**
		 * Builds the preferences pane, shown in the symphony backend.
		 *
		 * @return XMLElement
		 */
		public function getPreferencesPane(){
			parent::getPreferencesPane();
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings pickable');
			$group->setAttribute('id', 'smtp');
			$group->appendChild(new XMLElement('legend', __('Email: SMTP')));

			$div = new XMLElement('div');
			$div->appendChild(new XMLElement('p', __('The following default settings will be used to send emails unless they are overwritten.')));
			$group->appendChild($div);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('From Name'));
			$label->appendChild(Widget::Input('settings[email_smtp][from_name]', $this->_sender_name));
			$div->appendChild($label);

			$label = Widget::Label(__('From Email Address'));
			$label->appendChild(Widget::Input('settings[email_smtp][from_address]', $this->_sender_email_address));
			$div->appendChild($label);

			$group->appendChild($div);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('Host'));
			$label->appendChild(Widget::Input('settings[email_smtp][host]',	 $this->_host));
			$div->appendChild($label);

			$label = Widget::Label(__('Port'));
			$label->appendChild(Widget::Input('settings[email_smtp][port]', $this->_port));
			$div->appendChild($label);
			$group->appendChild($div);

			$label = Widget::Label();
			// To fix the issue with checkboxes that do not send a value when unchecked.
			$options = array(
				array('no',$this->_secure == 'no', __('No encryption')),
				array('ssl',$this->_secure == 'ssl', __('SSL encryption')),
				array('tls',$this->_secure == 'tls', __('TLS encryption')),
			);
			$select = Widget::Select('settings[email_smtp][secure]', $options);
			$label->appendChild($select);
			$group->appendChild($label);

			$group->appendChild(new XMLElement('p', __('For a secure connection, SSL and TLS are supported. Please check the manual of your email provider for more details.'), array('class' => 'help')));

			$label = Widget::Label();
			// To fix the issue with checkboxes that do not send a value when unchecked.
			$group->appendChild(Widget::Input('settings[email_smtp][auth]', '0', 'hidden'));
			$input = Widget::Input('settings[email_smtp][auth]', '1', 'checkbox');
			if($this->_auth == true) $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Requires authentication', array($input->generate())));
			$group->appendChild($label);

			$group->appendChild(new XMLElement('p', __('Some SMTP connections require authentication. If that is the case, enter the username/password combination below.'), array('class' => 'help')));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('Username'));
			$label->appendChild(Widget::Input('settings[email_smtp][username]', $this->_user));
			$div->appendChild($label);

			$label = Widget::Label(__('Password'));
			$label->appendChild(Widget::Input('settings[email_smtp][password]', $this->_pass, 'password'));
			$div->appendChild($label);
			$group->appendChild($div);

			return $group;
		}
	}