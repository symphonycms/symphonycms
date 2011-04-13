<?php

	/**
	 * @package toolkit
	 */

	/**
	 * The standard exception to be thrown by all email gateways.
	 */
	class EmailGatewayException extends Exception{

		/**
		 * Creates a new exception, and logs the error.
		 *
		 * @param string $message
		 * @param int $code
		 * @param Exception $previous
		 *  The previous exception, if nested. See
		 *  http://www.php.net/manual/en/language.exceptions.extending.php
		 * @return void
		 */
		public function __construct($message, $code = 0, $previous = null){
			$trace = parent::getTrace();
			// Best-guess to retrieve classname of email-gateway.
			// Might fail in non-standard uses, will then return an
			// empty string.
			$gateway_class = $trace[1]['class']?' (' . $trace[1]['class'] . ')':'';
			Symphony::$Log->pushToLog(__('Email Gateway Error') . $gateway_class  . ': ' . $message, $code, true);
			parent::__construct($message);
		}
	}

	/**
	 * The validation exception to be thrown by all email gateways.
	 * This exception is thrown if data does not pass validation.
	 */
	class EmailValidationException extends EmailGatewayException{
	}

	/**
	 * A base class for email gateways.
	 * All email-gateways should extend this class in order to work.
	 *
	 * @todo add validation to all set functions.
	 */
	Abstract Class EmailGateway{

		protected $_recipients = array();
		protected $_sender_name;
		protected $_sender_email_address;
		protected $_subject;
		protected $_body;
		protected $_text_plain;
		protected $_text_html;
		protected $_attachments = array();
		protected $_reply_to_name;
		protected $_reply_to_email_address;
		protected $_header_fields = array();
		protected $_boundary_mixed;
		protected $_boundary_alter;
		protected $_text_encoding = 'quoted-printable';

		/**
		 * @return void
		 */
		public function __construct(){
			$this->_boundary_mixed = '=_mix_'.md5(uniqid());
			$this->_boundary_alter = '=_alt_'.md5(uniqid());
		}

		/**
		 * Sends the actual email.
		 * This function should be set on the email-gateway itself.
		 * See the default gateway for an example.
		 *
		 * @return void
		 */
		public function send(){
		}

		/**
		 * Sets the sender-email and sender-name.
		 *
		 * @param string $email
		 *  The email-address emails will be sent from.
		 * @param string $name
		 *  The name the emails will be sent from.
		 * @return void
		 */
		public function setFrom($email, $name){
			$this->setSenderEmailAddress($email);
			$this->setSenderName($name);
		}

		/**
		 * Sets the sender-email.
		 *
		 * @param string $email
		 *  The email-address emails will be sent from.
		 * @return void
		 */
		public function setSenderEmailAddress($email){
			if(preg_match('%[\r\n]%', $email)){
				throw new EmailValidationException(__('Sender Email Address can not contain carriage return or newlines.'));
			}
			$this->_sender_email_address = $email;
		}

		/**
		 * Sets the sender-name.
		 *
		 * @param string $name
		 *  The name emails will be sent from.
		 * @return void
		 */
		public function setSenderName($name){
			if(preg_match('%[\r\n]%', $name)){
				throw new EmailValidationException(__('Sender Name can not contain carriage return or newlines.'));
			}
			$this->_sender_name = $name;
		}

		/**
		 * Sets the recipients.
		 *
		 * @param string|array $email
		 *  The email-address(es) to send the email to.
		 * @return void
		 */
		public function setRecipients($email){
			//TODO: sanitizing and security checking
			if(!is_array($email)){
				$email = Array($email);
			}
			$this->_recipients = $email;
		}

		/**
		 * This functions takes a string to be used as the plaintext
		 * content for the Email
		 *
		 * @todo sanitizing and security checking
		 * @param string $text_plain
		 */
		public function setTextPlain($text_plain){
			//TODO:
			$this->_text_plain = $text_plain;
		}

		/**
		 * This functions takes a string to be used as the HTML
		 * content for the Email
		 *
		 * @todo sanitizing and security checking
		 * @param string $text_html
		 */
		public function setTextHtml($text_html){
			$this->_text_html = $text_html;
		}

		/**
		 * @todo Document this function
		 * @param string|array $file
		 */
		public function setAttachments($file){
			if(!is_array($file)){
				$file = array($file);
			}
			$this->_attachments = $file;
		}

		/**
		 * @todo Document this function
		 * @param string $encoding
		 *  Must be either `quoted-printable` or `base64`.
		 */
		public function setTextEncoding($encoding = null){
			if($encoding == 'quoted-printable'){
				$this->_text_encoding = 'quoted-printable';
			}
			elseif($encoding == 'base64'){
				$this->_text_encoding = 'base64';
			}
			elseif(!$encoding){
				$this->_text_encoding = false;
			}
			else{
				throw new EmailGatewayException(__('%s is not a supported encoding type. Please use "quoted-printable" or "base64". You can also use false for no encoding.', array($encoding)));
			}
		}

		/**
		 * Sets the subject.
		 *
		 * @param string $subject
		 *  The subject that the email will have.
		 * @return void
		 */
		public function setSubject($subject){
			//TODO: sanitizing and security checking;
			$this->_subject = $subject;
		}

		/**
		 * Sets the reply-to-email.
		 *
		 * @param string $email
		 *  The email-address emails should be replied to.
		 * @return void
		 */
		public function setReplyToEmailAddress($email){
			if(preg_match('%[\r\n]%', $email)){
				throw new EmailValidationException(__('Reply-To Email Address can not contain carriage return or newlines.'));
			}
			$this->_reply_to_email_address = $email;
		}

		/**
		 * Sets the reply-to-name.
		 *
		 * @param string $name
		 *  The name emails should be replied to.
		 * @return void
		 */
		public function setReplyToName($name){
			if(preg_match('%[\r\n]%', $name)){
				throw new EmailValidationException(__('Reply-To Name can not contain carriage return or newlines.'));
			}
			$this->_reply_to_name = $name;
		}

		/**
		 * Appends a single header field to the header fields array.
		 * The header field should be presented as a name/body pair.
		 *
		 * @param string $name
		 *  The header field name. Examples are From, X-Sender and Reply-to.
		 * @param string $body
		 *  The header field body.
		 * @return void
		 */
		public function appendHeaderField($name, $body){
			if(is_array($body)){
				throw new EmailGatewayException(__('appendHeaderField accepts strings only; arrays are not allowed.'));
			}
			$this->_header_fields[$name] = $body;
		}

		/**
		 * Appends one or more header fields to the header fields array.
		 * Header fields should be presented as an array with name/body pairs.
		 *
		 * @param array $header_array
		 *  The header fields. Examples are From, X-Sender and Reply-to.
		 * @return void
		 */
		public function appendHeaderFields(array $header_array = array()){
			foreach($header_array as $name => $body){
				$this->appendHeaderField($name, $body);
			}
		}
		/**
		 * Check to see if all required data is set.
		 *
		 * @return boolean
		 */
		public function validate(){
			/*
			 * Make sure the Recipient, Sender Name and Sender Email values
			 * are set.
			 * The message body will be checked in the prepareMessage
			 * function.
			 */
			if(strlen(trim($this->_subject)) <= 0){
				throw new EmailValidationException(__('Email subject cannot be empty.'));
			}

			elseif(strlen(trim($this->_sender_email_address)) <= 0){
				throw new EmailValidationException(__('Sender email address cannot be empty.'));
			}

			else{
				foreach($this->_recipients as $address){
					if(strlen(trim($address)) <= 0){
						throw new EmailValidationException(__('Recipient email address cannot be empty.'));
					}
					elseif(!filter_var($address, FILTER_VALIDATE_EMAIL)) {
						throw new EmailValidationException(__('The email address "%s" is invalid.', array($address)));
					}
				}
			}
			return true;
		}

		/**
		 * Build the message body and the content-describing header fields
		 *
		 * The result of this building is an updated body variable in the
		 * gateway itself.
		 *
		 * @return boolean
		 */
		protected function prepareMessageBody(){
			if (!empty($this->_attachments)) {
				$this->appendHeaderFields($this->contentInfoArray('multipart/mixed'));
				if (!empty($this->_text_plain) && !empty($this->_text_html)) {
					$this->_body = $this->boundaryDelimiterLine('multipart/mixed')
								. $this->contentInfoString('multipart/alternative')
								. $this->getSectionMultipartAlternative()
								. $this->getSectionAttachments()
					;
				}
				else if (!empty($this->_text_plain)) {
					$this->_body = $this->boundaryDelimiterLine('multipart/mixed')
								. $this->contentInfoString('text/plain')
								. $this->getSectionTextPlain()
								. $this->getSectionAttachments()
					;
				}
				else if (!empty($this->_text_html)) {
					$this->_body = $this->boundaryDelimiterLine('multipart/mixed')
								. $this->contentInfoString('text/html')
								. $this->getSectionTextHtml()
								. $this->getSectionAttachments()
					;
				}
				else {
					$this->_body = $this->getSectionAttachments();
				}
				$this->_body	.= $this->finalBoundaryDelimiterLine('multipart/mixed');
			}
			else if (!empty($this->_text_plain) && !empty($this->_text_html)) {
				$this->appendHeaderFields($this->contentInfoArray('multipart/alternative'));
				$this->_body	 = $this->getSectionMultipartAlternative();
			}
			else if (!empty($this->_text_plain)) {
				$this->appendHeaderFields($this->contentInfoArray('text/plain'));
				$this->_body	 = $this->getSectionTextPlain();
			}
			else if (!empty($this->_text_html)) {
				$this->appendHeaderFields($this->contentInfoArray('text/html'));
				$this->_body	 = $this->getSectionTextHtml();
			}
			else{
				throw new EmailGatewayException(__('No attachments or body text was set. Can not send empty email.'));
			}
		}

		/**
		 * Build multipart email section. Used by sendmail and smtp classes to
		 * send multipart email.
		 *
		 * Will return a string containing the section. Can be used to send to
		 * an email server directly.
		 * @return string
		 */
		protected function getSectionMultipartAlternative() {
			$output = $this->boundaryDelimiterLine('multipart/alternative')
					. $this->contentInfoString('text/plain')
					. $this->getSectionTextPlain()
					. $this->boundaryDelimiterLine('multipart/alternative')
					. $this->contentInfoString('text/html')
					. $this->getSectionTextHtml()
					. $this->finalBoundaryDelimiterLine('multipart/alternative')
			;
			return $output;
		}

		/**
		 * Builds the attachment section of a multipart email.
		 *
		 * Will return a string containing the section. Can be used to send to
		 * an email server directly.
		 * @return string
		 */
		protected function getSectionAttachments() {
			$output = '';
			foreach ($this->_attachments as $file) {
				$output .= $this->boundaryDelimiterLine('multipart/mixed')
						 . $this->contentInfoString(NULL, $file)
						 . EmailHelper::base64ContentTransferEncode(file_get_contents($file))
				;
			}
			return $output;
		}

		/**
		 * Builds the text section of a text/plain email.
		 *
		 * Will return a string containing the section. Can be used to send to
		 * an email server directly.
		 * @return string
		 */
		protected function getSectionTextPlain() {
			if ($this->_text_encoding == 'quoted-printable') {
				return EmailHelper::qpContentTransferEncode($this->_text_plain)."\r\n";
			}
			elseif ($this->_text_encoding == 'base64') {
				// don't add CRLF if using base64 - spam filters don't
				// like this
				return EmailHelper::base64ContentTransferEncode($this->_text_plain);
			}
			return $this->_text_plain."\r\n";
		}

		/**
		 * Builds the html section of a text/html email.
		 *
		 * Will return a string containing the section. Can be used to send to
		 * an email server directly.
		 * @return string
		 */
		protected function getSectionTextHtml() {
			if ($this->_text_encoding == 'quoted-printable') {
				return EmailHelper::qpContentTransferEncode($this->_text_html)."\r\n";
			}
			elseif ($this->_text_encoding == 'base64') {
				// don't add CRLF if using base64 - spam filters don't
				// like this
				return EmailHelper::base64ContentTransferEncode($this->_text_html);
			}
			return $this->_text_html."\r\n";
		}

		/**
		 * Builds the right content-type/encoding types based on file and
		 * content-type.
		 *
		 * Will return a string containing the section, or an empty array on
		 * failure. Can be used to send to an email server directly.
		 * @return string
		 */
		public function contentInfoArray($type = NULL, $file = NULL) {
			$description = array(
				'multipart/mixed' => array(
					"Content-Type" => 'multipart/mixed; boundary="'
									  .$this->getBoundary('multipart/mixed').'"',
				),
				'multipart/alternative' => array(
					'Content-Type' => 'multipart/alternative; boundary="'
									  .$this->getBoundary('multipart/alternative').'"',
				),
				'text/plain' => array(
					'Content-Type'				=> 'text/plain; charset=UTF-8',
					'Content-Transfer-Encoding' => $this->_text_encoding ? $this->_text_encoding : '8bit',
				),
				'text/html' => array(
					'Content-Type'				=> 'text/html; charset=UTF-8',
					'Content-Transfer-Encoding' => $this->_text_encoding ? $this->_text_encoding : '8bit',
				),
			);
			$binary = array(
				'Content-Type'				=> EmailHelper::getMimeType($file).'; name="'.basename($file).'"',
				'Content-Transfer-Encoding' => 'base64',
				'Content-Disposition'		=> 'attachment; filename="'.basename($file).'"',
			);
			return !empty($description[$type]) ? $description[$type] : ($file ? $binary : array());
		}

		/**
		 * TODO
		 *
		 * @return string
		 */
		protected function contentInfoString($type = NULL, $file = NULL) {
			$data = $this->contentInfoArray($type, $file);
			foreach ($data as $key => $value) {
				$field[] = EmailHelper::fold(sprintf('%s: %s', $key, $value));
			}
			return !empty($field) ? implode("\r\n", $field)."\r\n\r\n" : NULL;
		}

		protected function getBoundary($type) {
			switch ($type) {
				case 'multipart/mixed':
					return $this->_boundary_mixed;
					break;
				case 'multipart/alternative':
					return $this->_boundary_alter;
					break;
			}
		}

		protected function boundaryDelimiterLine($type) {
			// As requested by RFC 2046: 'The CRLF preceding the boundary
			// delimiter line is conceptually attached to the boundary.'
			return $this->getBoundary($type) ? "\r\n--".$this->getBoundary($type)."\r\n" : NULL;
		}

		protected function finalBoundaryDelimiterLine($type) {
			return $this->getBoundary($type) ? "\r\n--".$this->getBoundary($type)."--\r\n" : NULL;
		}

		/**
		 * Sets a property.
		 *
		 * Magic function, supplied by php.
		 * This function will try and find a method of this class, by
		 * camelcasing the name, and appending it with set.
		 * If the function can not be found, an exception will be thrown.
		 *
		 * @param string $name
		 *  The property name.
		 * @param string $value
		 *  The property value;
		 * @return void|boolean
		 */
		public function __set($name, $value){
			if(method_exists(get_class($this), 'set'.$this->__toCamel($name, true))){
				return $this->{'set'.$this->__toCamel($name, true)}($value);
			}
			else{
				throw new EmailGatewayException(__('The %s gateway does not support the use of %s', array(get_class($this), $name)));
			}
		}

		/**
		 * The preferences to add to the preferences pane in the admin area.
		 *
		 * @return XMLElement
		 */
		public function getPreferencesPane(){
			return new XMLElement('fieldset');
		}

		/**
		 * Internal function to turn underscored variables into camelcase, for
		 * use in methods.
		 * Because Symphony has a difference in naming between properties and
		 * methods (underscored vs camelcased) and the Email class uses the
		 * magic __set function to find property-setting-methods, this
		 * conversion is needed.
		 *
		 * @param string $string
		 *  The string to convert
		 * @param boolean $caseFirst
		 *  If this is true, the first character will be uppercased. Useful
		 *  for method names (setName).
		 *  If set to false, the first character will be lowercased. This is
		 *  default behaviour.
		 * @return string
		 */
		private function __toCamel($string, $caseFirst = false){
			$string = strtolower($string);
			$a = explode('_', $string);
			$a = array_map(ucfirst, $a);
			if(!$caseFirst){
				$a[0] = lcfirst($a[0]);
			}
			return implode('', $a);
		}

		/**
		 * The reverse of the __toCamel function.
		 *
		 * @param string $string
		 *  The string to convert
		 * @return string
		 */
		private function __fromCamel($string){
			$string[0] = strtolower($string[0]);
			$func = create_function('$c', 'return "_" . strtolower($c[1]);');
			return preg_replace_callback('/([A-Z])/', $func, $str);
		}

	}
