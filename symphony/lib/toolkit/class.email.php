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
			if (eregi("(\r|\n)", $this->sender_name) || eregi("(\r|\n)", $this->sender_email_address)){
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
				'Return-Path'	=> $this->sender_email_address,
				'From'			=> "{$this->sender_name} <{$this->sender_email_address}>",
		 		'Reply-To'		=> $this->sender_email_address,
				'Message-ID'	=> sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['SERVER_NAME']),
				'Return-Path'	=> "<{$this->sender_email_address}>",
				'Importance'	=> 'normal',
				'Priority'		=> 'normal',
				'X-Sender'		=> 'Symphony Email Module <noreply@symphony-cms.com>',
				'X-Mailer'		=> 'Symphony Email Module',
				'X-Priority'	=> '3',
				'MIME-Version'	=> '1.0',
				'Content-Type'	=> 'text/plain; charset=UTF-8',
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

			$result = mail($this->recipient, $this->subject, @wordwrap($this->message, 70), @implode(PHP_EOL, $headers) . PHP_EOL, "-f{$this->sender_email_address}");

			if($result !== true){
				throw new EmailException('Email failed to send. Please check input.');
			}

			return true;
		}


		/***

		Method: encodeHeader
		Description: Encodes (parts of) an email header if necessary, according to RFC2047 if mbstring is available;
		Added by: Michael Eichelsdoerfer

		***/
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

