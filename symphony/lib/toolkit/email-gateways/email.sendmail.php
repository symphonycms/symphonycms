<?php
	/**
	 * @package email-gateways
	 */

	require_once(TOOLKIT . '/class.emailgateway.php');
	require_once(TOOLKIT . '/class.emailhelper.php');

	/**
	 * The basic gateway sending emails using Sendmail, php's mail function.
	 *
	 * @author Michael Eichelsdoerfer, Huib Keemink
	 */
	Class SendmailGateway extends EmailGateway {

		/**
		 * Returns the name, used in the dropdown menu in the preferences pane.
		 *
		 * @return array
		 */
		public static function about() {
			return array(
				'name' => __('Sendmail (default)'),
			);
		}

		/**
		 * Constructor. Sets basic default values based on preferences.
		 *
		 * @return void
		 */
		public function __construct(){
			parent::__construct();
			$this->setConfiguration(Symphony::Configuration()->get('email_sendmail'));
		}

		/**
		 * Send an email using the PHP mail() function
		 *
		 * Please note that 'encoded-words' should be used according to
		 * RFC2047. Basically this means that the subject should be
		 * encoded if necessary, as well as (real) names in 'From', 'To'
		 * or 'Reply-To' header field bodies. For details see RFC2047.
		 *
		 * The parts of a message body should be encoded (quoted-printable
		 * or base64) to make non-US-ASCII text work with the widest range
		 * of email transports and clients.
		 *
		 * @return bool
		 */
		public function send() {

			$this->validate();

			try {
				// Encode recipient names (but not any numeric array indexes)
				foreach($this->_recipients as $name => $email) {
					$name = empty($name) ? $name : EmailHelper::qEncode($name);
					$recipients[$name] = $email;
				}

				// Combine keys and values into a recipient list (name <email>, name <email>).
				$recipient_list = EmailHelper::arrayToList($recipients);

				// Encode the subject
				$subject = EmailHelper::qEncode((string)$this->_subject);

				// Build the 'From' header field body
				$from = empty($this->_sender_name)
				        ? $this->_sender_email_address
				        : EmailHelper::qEncode($this->_sender_name) . ' <' . $this->_sender_email_address . '>';

				// Build the 'Reply-To' header field body
				if(!empty($this->_reply_to_email_address)){
					$reply_to = empty($this->_reply_to_name)
					            ? $this->_reply_to_email_address
					            : EmailHelper::qEncode($this->_reply_to_name) . ' <'.$this->_reply_to_email_address.'>';
				}
				if(!empty($reply_to)){
					$this->_header_fields = array_merge(
						$this->_header_fields,
						array(
							'Reply-To' => $reply_to,
						)
					);
				}

				// Build the message from the attachments, the html-text and the plain-text.
				$this->prepareMessageBody();

				// Build the header fields
				$this->_header_fields = array_merge(
					$this->_header_fields,
					array(
						'Message-ID'   => sprintf('<%s@%s>', md5(uniqid()), HTTP_HOST),
						'Date'         => date('r'),
						'From'         => $from,
						'X-Mailer'     => 'Symphony Email Module',
						'MIME-Version' => '1.0',
					)
				);

				// Format header fields
				foreach ($this->_header_fields as $name => $body) {
					$header_fields[] = sprintf('%s: %s', $name, $body);
				}

				/**
				 * Make things nice for mail().
				 * - Replace CRLF in the message body by LF as required by mail().
				 * - Implode the header fields as required by mail().
				 */
				$this->_body = str_replace("\r\n", "\n", $this->_body);
				$header_fields = implode("\r\n", $header_fields);

				// Send the email
				mail($recipient_list, $subject, $this->_body, $header_fields, "-f{$this->_sender_email_address}");
			}
			catch (Exception $e) {
				throw new EmailGatewayException($e->getMessage());
			}
			return true;
		}

		/**
		 * Sets all configuration entries from an array.
		 *
		 * @throws EmailValidationException
		 * @param array $configuration
		 * @since 2.3.1
		 *  All configuration entries stored in a single array. The array should have the format of the $_POST array created by the preferences HTML.
		 * @return void
		 */
		public function setConfiguration($config){
			$this->setFrom($config['from_address'],$config['from_name']);
		}

		/**
		 * Builds the preferences pane, shown in the symphony backend.
		 *
		 * @return XMLElement
		 */
		public function getPreferencesPane() {
			parent::getPreferencesPane();
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings condensed pickable');
			$group->setAttribute('id', 'sendmail');
			$group->appendChild(new XMLElement('legend', __('Email: Sendmail')));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'two columns');

			$label = Widget::Label(__('From Name'));
			$label->setAttribute('class', 'column');
			$label->appendChild(Widget::Input('settings[email_sendmail][from_name]', $this->_sender_name));
			$div->appendChild($label);

			$label = Widget::Label(__('From Email Address'));
			$label->setAttribute('class', 'column');
			$label->appendChild(Widget::Input('settings[email_sendmail][from_address]', $this->_sender_email_address));
			$div->appendChild($label);

			$group->appendChild($div);

			return $group;
		}
	}
