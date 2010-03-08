<?php

	Class ConfigurationElement{
		protected $_doc;
		protected $_path;
		protected $_properties;
	
		public function __construct($path){
			$this->_properties = new StdClass;
			$this->_path = $path;
			$this->_doc = simplexml_load_file($this->_path);
			self::__loadVariablesFromNode($this->_doc, $this->_properties);
		}
	
		protected function __loadVariablesFromNode(SimpleXMLElement $elements, StdClass &$group){
			foreach($elements as $e){
				$name = $e->getName();
				
				if(count($e->children()) > 0){
					$group->$name = new StdClass;
					self::__loadVariablesFromNode($e, $group->$name);
				}
				else{
					$group->$name = (string)$e;
				}
			}
		}
		
		public function properties(){
			return $this->_properties;
		}
		
		public function __get($name){
			return $this->_properties->$name;
		}
	
		public function __set($name, $value){
			$this->_properties->$name = $value;
		}
	
		public function __unset($name){
			unset($this->_properties->$name);
		}
	
		public function save(){
			file_put_contents($this->_path, (string)$this);
		}
	
		public function __toString(){
			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->formatOutput = true;
		
			$root = $doc->createElement('configuration');
			$doc->appendChild($root);
		
			self::__generateXML($this->_properties, $root);
		
			return $doc->saveXML();
		}
	
		protected static function __generateXML(StdClass $elements, DOMNode &$parent){
			foreach($elements as $name => $e){
				if($e instanceof StdClass){
					$element = $parent->ownerDocument->createElement($name);
					self::__generateXML($e, $element);
				}
				else{
					$element = $parent->ownerDocument->createElement($name, (string)$e);
				}
			
				$parent->appendChild($element);
			}
		}
	}
	
	Class Configuration{
		private static $_objects;

		public function __call($handle, array $param){
			if(!isset(self::$_objects[$handle]) || !(self::$_objects[$handle] instanceof ConfigurationElement)){
				$class = 'ConfigurationElement';
				if(isset($param[0]) && strlen(trim($param[0])) > 0) $class = $param[0];
				self::$_objects[$handle] = new $class(CONFIG . "/{$handle}.xml");
			}
			return self::$_objects[$handle];
		}
		
		// Deprecated
		public function get($name=NULL, $index=NULL){
			
			$name = str_replace('_', '-', $name);
			$index = str_replace('_', '-', $index);
			
			if($index){
				return $this->core()->$index->$name;
			}
				
			return (array)$this->core()->$name;
		}
	}
