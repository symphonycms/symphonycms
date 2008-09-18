<?php
	
	## This class should never be instanciated. Always extend
	
	Class Page{
		
		const CRLF = "\r\n";
		
		function addHeaderToPage($name, $value=NULL){
			$this->_headers[$name] = $value;
		}

		function generate(){
			$this->__renderHeaders();
		}
		
		function __renderHeaders(){

			if(!is_array($this->_headers) || empty($this->_headers)) return;
		
			foreach($this->_headers as $name => $value){
				if($value) header("$name: $value");
				else header($name);
			}
		}		
	}

?>