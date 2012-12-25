<?php
	/**
	 * @package toolkit
	 */

	/**
	 * Exceptions to be thrown by the SMTP Client class
	 */
	Class SMTPException extends Exception{}

	/**
	 * A SMTP client class, for sending text/plain emails.
	 * This class only supports the very basic SMTP functions.
	 * Inspired by the SMTP class in the Zend library
	 *
	 * @author Huib Keemink <huib.keemink@creativedutchmen.com>
	 * @version 0.1 - 20 okt 2010
	 */
	Class SMTP{

		const TIMEOUT = 30;

		protected $_host;
		protected $_port;
		protected $_user = null;
		protected $_pass = null;
		protected $_transport = 'tcp';
		protected $_secure = false;

		protected $_header_fields = array();

		protected $_from = null;
		protected $_subject = null;
		protected $_to = array();

		protected $_ip = '127.0.0.1';
		protected $_connection = false;

		protected $_helo = false;
		protected $_mail = false;
		protected $_data = false;
		protected $_rcpt = false;
		protected $_auth = false;

		/**
		 * Constructor.
		 *
		 * @param string $host
		 *  Host to connect to. Defaults to localhost (127.0.0.1)
		 * @param integer $port
		 *  When ssl is used, defaults to 465
		 *  When no ssl is used, and ini_get returns no value, defaults to 25.
		 * @param array $options
		 *  Currently supports 3 values:
		 *  	$options['secure'] can be ssl, tls or null.
		 *  	$options['username'] the username used to login to the server. Leave empty for no authentication.
		 *  	$options['password'] the password used to login to the server. Leave empty for no authentication.
		 *  	$options['local_ip'] the ip address used in the ehlo/helo commands. Only ip's are accepted.
		 * @return void
		 */
		public function __construct($host = '127.0.0.1', $port = null, $options = array()){
			if ($options['secure'] !== null) {
				switch (strtolower($options['secure'])) {
					case 'tls':
						$this->_secure = 'tls';
						break;

					case 'ssl':
						$this->_transport = 'ssl';
						$this->_secure = 'ssl';
						if ($port == null) {
							$port = 465;
						}
						break;

					case 'no':
						break;

					default:
						throw new SMTPException(__('Unsupported SSL type'));
						break;
				}
			}
			if (is_null($options['local_ip'])) {
				$this->_ip = gethostbyname(php_uname('n'));
			}
			else{
				$this->_ip = $options['local_ip'];
			}
			
			if ($port == null) {
				$port = 25;
			}
			
			if(($options['username'] !== null) && ($options['password'] !== null)){
				$this->_user = $options['username'];
				$this->_pass = $options['password'];
			}

			$this->_host = $host;
			$this->_port = $port;
		}

		/**
		 * Checks to see if `$this->_connection` is a valid resource. Throws an
		 * exception if there is no connection, otherwise returns true.
		 *
		 * @throws SMTPException
		 * @return boolean
		 */
		public function checkConnection() {
			if(!is_resource($this->_connection)){
				throw new SMTPException(__('No connection has been established to %s', array($this->_host)));
			}

			return true;
		}

		/**
		 * The actual email sending.
		 * The connection to the server (connecting, EHLO, AUTH, etc) is done here,
		 * right before the actual email is sent. This is to make sure the connection does not time out.
		 *
		 * @param string $from
		 *  The from string. Should have the following format: email@domain.tld
		 * @param string $to
		 *  The email address to send the email to.
		 * @param string $subject
		 *  The subject to send the email to.
		 * @param string $message
		 * @return boolean
		 */
		public function sendMail($from, $to, $subject, $message){
			$this->_connect($this->_host, $this->_port);
			$this->mail($from);
			if(!is_array($to)){
				$to = array($to);
			}
			foreach($to as $recipient){
				$this->rcpt($recipient);
			}
			$this->data($message);
			$this->rset();
		}

		/**
		 * Sets a header to be sent in the email.
		 *
		 * @throws SMTPException
		 * @param string $header
		 * @param string $value
		 * @return void
		 */
		public function setHeader($header, $value){
			if(is_array($value)){
				throw new SMTPException(__('Header fields can only contain strings'));
			}
			$this->_header_fields[$header] = $value;
		}


		/**
		 * Initiates the ehlo/helo requests.
		 *
		 * @throws SMTPException
		 * @return void
		 */
		public function helo(){
			if($this->_mail !== false){
				throw new SMTPException(__('Can not call HELO on existing session'));
			}
			//wait for the server to be ready
			$this->_expect(220,300);

			//send ehlo or ehlo request.
			try{
				$this->_ehlo();
			}
			catch(SMTPException $e){
				$this->_helo();
			}
			catch(Exception $e){
				throw $e;
			}

			$this->_helo = true;
		}

		/**
		 * Calls the MAIL command on the server.
		 *
		 * @throws SMTPException
		 * @param string $from
		 *  The email address to send the email from.
		 * @return void
		 */
		public function mail($from){

			if($this->_helo == false){
				throw new SMTPException(__('Must call EHLO (or HELO) before calling MAIL'));
			}
			else if($this->_mail !== false){
				throw new SMTPException(__('Only one call to MAIL may be made at a time.'));
			}
			$this->_send('MAIL FROM:<' . $from . '>');
			$this->_expect(250, 300);

			$this->_from = $from;
			$this->_mail = true;
			$this->_rcpt = false;
			$this->_data = false;
		}

		/**
		 * Calls the RCPT command on the server. May be called multiple times for more than one recipient.
		 *
		 * @throws SMTPException
		 * @param string $to
		 *  The address to send the email to.
		 * @return void
		 */
		public function rcpt($to){
			if($this->_mail == false){
				throw new SMTPException(__('Must call MAIL before calling RCPT'));
			}

			$this->_send('RCPT TO:<' . $to . '>');
			$this->_expect(array(250, 251), 300);

			$this->_rcpt = true;
		}

		/**
		 * Calls the data command on the server.
		 * Also includes header fields in the command.
		 *
		 * @throws SMTPException
		 * @param string $data
		 * @return void
		 */
		public function data($data){
			if($this->_rcpt == false){
				throw new SMTPException(__('Must call RCPT before calling DATA'));
			}

			$this->_send('DATA');
			$this->_expect(354, 120);

			foreach($this->_header_fields as $name => $body){
				// Every header can contain an array. Will insert multiple header fields of that type with the contents of array.
				// Useful for multiple recipients, for instance.
				if(!is_array($body)){
					$body = array($body);
				}
				foreach($body as $val){
					$this->_send($name . ': ' . $val);
				}

			}
			// Send an empty newline. Solves bugs with Apple Mail
			$this->_send('');

			// Because the message can contain \n as a newline, replace all \r\n with \n and explode on \n.
			// The send() function will use the proper line ending (\r\n).
			$data = str_replace("\r\n", "\n", $data);
			$data_arr = explode("\n", $data);
			foreach($data_arr as $line){
				// Escape line if first character is a period (dot). http://tools.ietf.org/html/rfc2821#section-4.5.2
				if(strpos($line, '.') === 0){
					$line = '.' . $line;
				}
				$this->_send($line);
			}

			$this->_send('.');
			$this->_expect(250, 600);
			$this->_data = true;
		}

		/**
		 * Resets the current session. This 'undoes' all rcpt, mail, etc calls.
		 *
		 * @return void
		 */
		public function rset(){
			$this->_send('RSET');
			// MS ESMTP doesn't follow RFC, see [ZF-1377]
			$this->_expect(array(250, 220));

			$this->_mail = false;
			$this->_rcpt = false;
			$this->_data = false;
		}

		/**
		 * Disconnects to the server.
		 *
		 * @return void
		 */
		public function quit(){
			$this->_send('QUIT');
			$this->_expect(221, 300);
			$this->_connection = null;
		}

		/**
		 * Authenticates to the server.
		 * Currently supports the AUTH LOGIN command.
		 * May be extended if more methods are needed.
		 *
		 * @throws SMTPException
		 * @return void
		 */
		protected function _auth(){
			if($this->_helo == false){
				throw new SMTPException(__('Must call EHLO (or HELO) before calling AUTH'));
			}
			else if($this->_auth !== false){
				throw new SMTPException(__('Can not call AUTH again.'));
			}

			$this->_send('AUTH LOGIN');
			$this->_expect(334);
			$this->_send(base64_encode($this->_user));
			$this->_expect(334);
			$this->_send(base64_encode($this->_pass));
			$this->_expect(235);
			$this->_auth = true;
		}

		/**
		 * Calls the EHLO function.
		 * This is the HELO function for more modern servers.
		 *
		 * @return void
		 */
		protected function _ehlo(){
			$this->_send('EHLO [' . $this->_ip . ']');
			$this->_expect(array(250, 220), 300);
		}

		/**
		 * Initiates the connection by calling the HELO function.
		 * This function should only be used if the server does not support the HELO function.
		 *
		 * @return void
		 */
		protected function _helo(){
			$this->_send('HELO [' . $this->_ip . ']');
			$this->_expect(array(250, 220), 300);
		}

		/**
		 * Encrypts the current session with TLS.
		 *
		 * @return void
		 */
		protected function _tls(){
			if ($this->_secure == 'tls') {
				$this->_send('STARTTLS');
				$this->_expect(220, 180);
				if (!stream_socket_enable_crypto($this->_connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
					throw new SMTPException(__('Unable to connect via TLS'));
				}
				$this->_ehlo();
			}
		}

		/**
		 * Send a request to the host, appends the request with a line break.
		 *
		 * @param string $request
		 * @return boolean|integer number of characters written.
		 */
		protected function _send($request){
			$this->checkConnection();

			$result = fwrite($this->_connection, $request . "\r\n");
			if($result === false){
				throw new SMTPException(__('Could not send request: %s', array($request)));
			}
			return $result;
		}

		/**
		 * Get a line from the stream.
		 *
		 * @param integer $timeout 
		 *  Per-request timeout value if applicable. Defaults to null which 
		 *  will not set a timeout.
		 * @return string
		 */
		protected function _receive($timeout = null) {
			$this->checkConnection();

			if ($timeout !== null) {
				stream_set_timeout($this->_connection, $timeout);
			}

			$response = fgets($this->_connection, 1024);
			$info = stream_get_meta_data($this->_connection);

			if (!empty($info['timed_out'])) {
				throw new SMTPException(__('%s has timed out', array($this->_host)));
			}

			else if ($response === false) {
				throw new SMTPException(__('Could not read from %s', array($this->_host)));
			}

			return $response;
		}

		/**
		 * Parse server response for successful codes
		 *
		 * Read the response from the stream and check for expected return code.
		 *
		 * @throws SMTPException
		 * @param  string|array $code
		 *  One or more codes that indicate a successful response
		 * @param integer $timeout 
		 *  Per-request timeout value if applicable. Defaults to null which 
		 *  will not set a timeout.
		 * @return string
		 *  Last line of response string
		 */
		protected function _expect($code, $timeout = null){
			$this->_response = array();
			$cmd  = '';
			$more = '';
			$msg  = '';
			$errMsg = '';

			if (!is_array($code)) {
				$code = array($code);
			}

			// Borrowed from the Zend Email Library
			do {
				$result = $this->_receive($timeout);
				list($cmd, $more, $msg) = preg_split('/([\s-]+)/', $result, 2, PREG_SPLIT_DELIM_CAPTURE);

				if ($errMsg !== '') {
					$errMsg .= ' ' . $msg;
				} elseif ($cmd === null || !in_array($cmd, $code)) {
					$errMsg = $msg;
				}

			} while (strpos($more, '-') === 0); // The '-' message prefix indicates an information string instead of a response string.

			if ($errMsg !== '') {
				throw new SMTPException($errMsg);
			}

			return $msg;
		}

		/**
		 * Connect to the host, and perform basic functions like helo and auth.
		 *
		 * @throws SMTPException
		 * @param string $host
		 * @param integer $port
		 * @return void
		 */
		protected function _connect($host, $port){
			$errorNum = 0;
			$errorStr = '';

			$remoteAddr = $this->_transport . '://' . $host . ':' . $port;
			if(!is_resource($this->_connection)){
				$this->_connection = @stream_socket_client($remoteAddr, $errorNum, $errorStr, self::TIMEOUT);
				if($this->_connection === false){
					if($errorNum == 0){
						throw new SMTPException(__('Unable to open socket. Unknown error'));
					}
					else{
						throw new SMTPException(__('Unable to open socket. %s', array($errorStr)));
					}
				}

				if(@stream_set_timeout($this->_connection, self::TIMEOUT) === false){
					throw new SMTPException(__('Unable to set timeout.'));
				}

				$this->helo();
				if($this->_secure == 'tls'){
					$this->_tls();
				}
				if(($this->_user !== null) && ($this->_pass !== null)){
					$this->_auth();
				}
			}
		}
	}