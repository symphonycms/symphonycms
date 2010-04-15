<?php

	Class DocumentHeaders{
		protected $headers;
	
		public function __construct(array $headers=array()){
			$this->headers = $headers;
		}
	
		public function append($name, $value=NULL){
			$this->headers[strtolower($name)] = $name . (is_null($value) ? NULL : ":{$value}");
		}

		public function render(){
			if(!is_array($this->headers) || empty($this->headers)) return;

			foreach($this->headers as $value){
				header($value);
			}
		}
	
		public function headers(){
			return $this->headers;
		}
	}

