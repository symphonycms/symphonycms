<?php

	require_once(TOOLKIT . '/class.page.php');

	Class HTMLPage extends Page{
	
		public $Head;
		public $Html;
		public $Body;
		public $Form;
		protected $_title;
		protected $_head;
			
		public function __construct(){
			
			parent::__construct();
			
			$this->Html = new XMLElement('html');
			$this->Html->setIncludeHeader(false);
		
			$this->Head = new XMLElement('head');
		
			$this->_head = array();
		
			$this->Body = new XMLElement('body');
			$this->Form = NULL;
			
			
		}
	
		protected function __build(){
			$this->__generateHead();
			$this->Html->appendChild($this->Head);
			if(is_object($this->Form)) $this->Body->appendChild($this->Form);
			$this->Html->appendChild($this->Body);
		}
		
		public function generate(){
			$this->__build();	
			parent::generate();			
			return $this->Html->generate(true);
		}

		public function __buildQueryString($exclude=array()){
			static $q;
			if (!is_array($q)) {
				$q = array();
				foreach($_GET as $k => $v){
					if (is_array($v)) $q[$k] = self::__flattenQueryArray($v, $k);
					else $q[$k] = "{$k}={$v}";
				}
			}
			$exclude[] = 'page';
			return implode('&', array_diff_key($q, array_fill_keys($exclude, true)));
		}

		private static function __flattenQueryArray(&$array, $parent){
			$values = array();
			foreach($array as $k => $v){
				if(is_array($v)) $values[] = self::__flattenQueryArray($v, $parent."[{$k}]");
				else $values[] = "{$parent}[{$k}]={$v}";
			}
			return implode('&', $values);
		}
		
		public function setTitle($val){
			return $this->addElementToHead(new XMLElement('title', $val));
		}
		
		public function addElementToHead($obj, $position=NULL){
			if(($position && isset($this->_head[$position]))) $position = General::array_find_available_index($this->_head, $position);
			elseif(!$position) $position = max(0, count($this->_head));			
			$this->_head[$position] = $obj;
			return $position;
		}
		
		public function addScriptToHead($path, $position=NULL, $duplicate=true){
	        if($duplicate === true || ($duplicate === false && $this->checkElementsInHead($path, 'src') !== true)){
	            $script = new XMLElement('script');
	            $script->setSelfClosingTag(false);
	            $script->setAttributeArray(array('type' => 'text/javascript', 'src' => $path));
	            return $this->addElementToHead($script, $position);
	        }
	    }
	
	    public function addStylesheetToHead($path, $type='screen', $position=NULL, $duplicate=true){
	        if($duplicate === true || ($duplicate === false && $this->checkElementsInHead($path, 'href') !== true)){
	            $link = new XMLElement('link');
	            $link->setAttributeArray(array('rel' => 'stylesheet', 'type' => 'text/css', 'media' => $type, 'href' => $path));
	            return $this->addElementToHead($link, $position);
	        }
	    }
	
	    public function checkElementsInHead($path, $attr){
	        foreach($this->_head as $element) {
	            if(basename($element->getAttribute($attr)) == basename($path)) return true;
	        }   
	    }
        
		private function __generateHead(){
			
			ksort($this->_head);

			foreach($this->_head as $position => $obj){
				if(is_object($obj)) $this->Head->appendChild($obj);
			}

		}

		public function removeFromHead($elementName, $attribute=NULL, $attributeValue=NULL){
			foreach($this->_head as $index => $element){
				if($element->getName() != $elementName) continue;
				
				if(!is_null($attribute) && !is_null($attributeValue)){
					$value = $element->getAttribute($attribute);
					
					if(is_null($value) || $attributeValue != $value) continue;
				}
				
				unset($this->_head[$index]);
			}
		}

	}

