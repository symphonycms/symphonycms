<?php

	Class EmailException extends Exception{
	}

	Class Email{

		private static $drivers;

		protected $headers;
		protected $recipient;
		protected $sender_name;
		protected $sender_email_address;
		protected $subject;
		protected $message;

		public static function create($driver=NULL){
			if(is_null($driver)) return new self;

			elseif(!isset(self::$drivers[$driver])){
				self::$drivers[$driver] = include_once(sprintf('%s/%s', EXTENSIONS, $driver));
			}

			return new self::$drivers[$driver];
		}

		public function __construct(){
			self::$drivers = array();
			$this->headers = array();
			$this->recipient = $this->sender_name  = $this->sender_email_address = $this->subject = $this->message = NULL;
		}

		public function validate(){
			if (preg_match('%[\r\n]%', $this->sender_name . $this->sender_email_address)){
				throw new EmailException("The sender name and/or email address contain invalid data. It cannot include new line or carriage return characters.");
			}

			// Make sure the Message, Recipient, Sender Name and Sender Email values are set
			if(strlen(trim($this->message)) <= 0){
				throw new EmailException('Email message cannot be empty.');
			}

			elseif(strlen(trim($this->subject)) <= 0){
				throw new EmailException('Email subject cannot be empty.');
			}

			elseif(strlen(trim($this->sender_name)) <= 0){
				throw new EmailException('Sender name cannot be empty.');
			}

			elseif(strlen(trim($this->sender_email_address)) <= 0){
				throw new EmailException('Sender email address cannot be empty.');
			}

			return true;
		}

		public function send(){

			$this->validate();

			$this->subject = self::encodeHeader($this->subject, 'UTF-8');
			$this->sender_name = self::encodeHeader($this->sender_name, 'UTF-8');

			$default_headers = array(
				'Return-path'	=> "<{$this->sender_email_address}>",
				'From'			=> "{$this->sender_name} <{$this->sender_email_address}>",
				'Reply-To'		=> $this->sender_email_address,
				'Message-ID'	=> sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['SERVER_NAME']),
				'X-Mailer'		=> 'Symphony Email Module',
				'MIME-Version'	=> '1.0',
				'Content-Type'	=> 'text/plain; charset=UTF-8',
				'Content-Transfer-Encoding' => 'quoted-printable',
			);

			foreach($default_headers as $key => $value){
				try{
					$this->appendHeader($key, $value, false);
				}
				catch(Exception $e){
					//Its okay to discard errors. They mean the header was already set.
				}
			}

			foreach ($this->headers as $header => $value) {
				$headers[] = sprintf('%s: %s', $header, $value);
			}

			// Quoted-printable encoding of the message body (RFC 2045)
			$this->message = $this->quotedPrintableEncode($this->message);

			// The PHP mail() function requires 'linefeed only' for the message body;
			$this->message = str_replace("\r\n", "\n", $this->message);

			$result = mail($this->recipient, $this->subject, $this->message, @implode("\r\n", $headers) . "\r\n", "-f{$this->sender_email_address}");

			if($result !== true){
				throw new EmailException('Email failed to send. Please check input.');
			}

			return true;
		}

		/**
		 * encode email headers
		 *
		 * Encodes (parts of) an email header if necessary,
		 * according to RFC2047 if mbstring is available;
		 *
		 * @param string $input
		 * @param string $charset
		 * @return string
		 * @author Michael Eichelsdörfer
		 */
		public static function encodeHeader($input, $charset='ISO-8859-1')
		{
		    if(preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches))
		    {
		        if(function_exists('mb_internal_encoding'))
		        {
		            mb_internal_encoding($charset);
		            $input = mb_encode_mimeheader($input, $charset, 'Q');
		        }
		        else
		        {
		            foreach ($matches[1] as $value)
		            {
		                $replacement = preg_replace('/([\x20\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
		                $input = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
		            }
		        }
		    }
		    return $input;
		}

		/**
		 * quoted-printable body encoding
		 *
		 * method to quoted-printable-encode an email message body according to RFC 2045;
		 * includes line wrapping according to RFC 822/2822 (using CRLF) as required by RFC 2045;
		 * for PHP >= 5.3 we might use the built-in quoted_printable_encode() function instead,
		 * but we should keep in mind that the (human-)readabilty of its output is much worse;
		 *
		 * http://php.net/manual/en/function.quoted-printable-encode.php
		 * http://php.net/manual/en/function.quoted-printable-decode.php
		 *
		 * @param string $input
		 * @param string $line_max; maximum line length
		 * @return string
		 */
		public static function quotedPrintableEncode($input, $line_max = 76) {
			$hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
			$lines = preg_split("/(?:\r\n|\r|\n)/", $input);
			$linebreak = "\r\n";
			$softlinebreak = "=\r\n";

			$line_max = $line_max - strlen($softlinebreak);

			$output = "";
			$cur_conv_line = "";
			$length = 0;
			$whitespace_pos = 0;
			$addtl_chars = 0;

			// iterate lines
			for ($j=0; $j < count($lines); $j++) {
				$line = $lines[$j];
				$linlen = strlen($line);

				// iterate chars
				for ($i = 0; $i < $linlen; $i++) {
					$c = substr($line, $i, 1);
					$dec = ord($c);
					$length++;

					// watch out for spaces
					if ($dec == 32) {
						// space occurring at end of line, need to encode
						if (($i == ($linlen - 1))) {
							$c = "=20";
							$length += 2;
						}
						$addtl_chars = 0;
						$whitespace_pos = $i;
					// characters to be encoded
					} elseif ( ($dec == 61) || ($dec < 32 ) || ($dec > 126) ) {
						$h2 = floor($dec/16);
						$h1 = floor($dec%16);
						$c = "=" . $hex["$h2"] . $hex["$h1"];
						$length += 2;
						$addtl_chars += 2;
						$enc_pos = $i;
					}

					// length for wordwrap exceeded, get a newline into the text
					if ($length >= $line_max) {
						$cur_conv_line .= $c;

						// read only up to the whitespace for the current line
						$whitesp_diff = $i - $whitespace_pos + $addtl_chars;
						$enc_diff = $i - $enc_pos + $addtl_chars;
						/*
						 * the text after the whitespace will have to
						 * be read again ( + any additional characters
						 * that came into existence as a result of the
						 * encoding process after the whitespace)
						 *
						 * Also, do not start at 0, if there was *no*
						 * whitespace in the whole line
						 */
						if (($i + $addtl_chars) > $whitesp_diff) {
							$output .= substr($cur_conv_line, 0,
							(strlen($cur_conv_line) - $whitesp_diff)) . $softlinebreak;
							$i = $i - $whitesp_diff + $addtl_chars;
						} else {
							$output .= $cur_conv_line . $softlinebreak;
						}

						$cur_conv_line = "";
						$length = 0;
						$whitespace_pos = 0;
					} else {
						// length for wordwrap not reached, continue reading
						$cur_conv_line .= $c;
					}
				}

				$length = 0;
				$whitespace_pos = 0;
				$output .= $cur_conv_line;
				$cur_conv_line = "";

				if ($j <= count($lines)-1) {
					$output .= $linebreak;
				}
			}

			return trim($output);
		}

		public function __set($name, $value){
			if(!property_exists($this, $name)){
				throw new EmailException("The property '{$name}' does not exist so cannot be set.");
			}
			$this->$name = $value;
		}

		public function appendHeader($name, $value, $replace=true){
			if($replace === false && array_key_exists($name, $this->headers)){
				throw new EmailException("The header '{$name}' has already been set.");
			}
			$this->headers[$name] = $value;
		}

	}
