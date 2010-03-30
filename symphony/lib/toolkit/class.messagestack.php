<?php

	Final Class MessageStack implements Iterator{

	    private $_messages = array();

	    public function __construct(array $messages=NULL){
			$this->_messages = array();
	
	        if(!is_null($messages)){
	            $this->_messages = $messages;
	        }
	    }

	    public function rewind(){
	        reset($this->_messages);
	    }

	    public function current(){
	        return current($this->_messages);
	    }

	    public function key(){
	        return key($this->_messages);
	    }

	    public function next(){
	        return next($this->_messages);
	    }

	    public function valid(){
	        return ($this->current() !== false);
	    }

		public function length(){
			return count($this->_messages);
		}
	
		## TODO: This is a bit voodoo. Maybe come up with a better solution
		private static function __sanitiseElementName($element){
			return str_replace('_', '-', $element);
		}

		public function append($element, $message){
			$this->_messages[self::__sanitiseElementName($element)] = $message;
		}

		public function remove($element){
			$element = self::__sanitiseElementName($element);
		
			if(isset($this->_messages[$element])){
				unset($this->_messages[$element]);
			}
		}
		
		public function __get($element){
			return (isset($this->_messages[$element]) ? $this->_messages[$element] : NULL);
		}
		
		public function __isset($element){
			return isset($this->_messages[$element]);
		}

	}