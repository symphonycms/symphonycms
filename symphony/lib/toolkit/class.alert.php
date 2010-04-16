<?php

	Class Alert{

		const NOTICE = 'notice';
		const ERROR = 'error';
		const SUCCESS = 'success';

		private $_message;
		private $_type;

		public function __construct($message, $type=self::NOTICE){
			$this->message = $message;
			$this->type = $type;
		}

		public function __get($name){
			return $this->{"_$name"};
		}

		public function __set($name, $value){
			$this->{"_$name"} = $value;
		}

		public function __isset($name){
			return (isset($this->{"_$name"}) && !is_null($this->{"_$name"}));
		}

		public function asXML(){
			$p = Administration::instance()->Page->createElement('p');
			$p->setAttribute('id', 'notice');

			if($this->type != self::NOTICE){
				$p->setAttribute('class', $this->type);
			}

			$fragment = Administration::instance()->Page->createDocumentFragment();
			$fragment->appendXML($this->message);

			$p->appendChild($fragment);

			return $p;
		}

	}