<?php

	Final Class MessageStack implements Iterator{

	    private $messages = array();

	    public function __construct(array $messages=NULL){
			$this->messages = array();
	
	        if(!is_null($messages)){
	            $this->messages = $messages;
	        }
	    }

	    public function rewind(){
	        reset($this->messages);
	    }

	    public function current(){
	        return current($this->messages);
	    }

	    public function key(){
	        return key($this->messages);
	    }

	    public function next(){
	        return next($this->messages);
	    }

	    public function valid(){
	        return ($this->current() !== false);
	    }

		public function length(){
			return count($this->messages);
		}
	
		## TODO: This is a bit voodoo. Maybe come up with a better solution
		private static function __sanitiseIdentifier($identifier){
			return str_replace('_', '-', $identifier);
		}

		public function append($identifier, $message){
			if($identifier == NULL) $identifier = count($this->messages);
			$this->messages[self::__sanitiseIdentifier($identifier)] = $message;
			
			return $identifier;
		}

		public function remove($identifier){
			$element = self::__sanitiseIdentifier($identifier);
		
			if(isset($this->messages[$identifier])){
				unset($this->messages[$identifier]);
			}
		}
		
		public function flush(){
			$this->messages = array();
		}
		
		public function __get($identifier){
			return (isset($this->messages[$identifier]) ? $this->messages[$identifier] : NULL);
		}
		
		public function __isset($identifier){
			return isset($this->messages[$identifier]);
		}

	}