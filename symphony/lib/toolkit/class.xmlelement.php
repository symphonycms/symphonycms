<?php

	Class XMLElement {

		private	$_name;
		private	$_value;
		private	$_attributes;
		private $_processingInstructions;
		private $_dtd;
		private	$_children;
		private $_encoding;
		private $_version;
		private $_includeHeader;
		private $_selfclosing;
		private $_allowEmptyAttributes;
		private $_elementStyle;
		private $_placeValueAfterChildElements;
		private $_no_end_tags;
		
		const CRLF = PHP_EOL;
		
		function XMLElement($name, $value=NULL, $attributes=array()){
	
			$this->_name = $name;
			$this->_placeValueAfterChildElements = false;
			$this->_attributes = array();
			$this->_children = array();
			$this->_processingInstructions = array();
			
			$this->_no_end_tags = array(
				'area', 'base', 'basefont', 'br', 'col', 'frame', 'hr', 'img',
				'input', 'isindex', 'link', 'meta', 'param'
			);
			
			$this->setEncoding('utf-8');
			$this->setVersion('1.0');
			$this->setIncludeHeader();
			$this->setSelfClosingTag();
			$this->setAllowEmptyAttributes();
			$this->setElementStyle();
			
			$this->setValue($value);		
			if(is_array($attributes) && !empty($attributes)) $this->setAttributeArray($attributes);			
			
		}
		
		public function getValue(){
			return $this->_value;
		}
		
		public function getChildren(){
			return $this->_children;
		}
		
		public function getAttributes(){ 
			return $this->_attributes;
		}
		
		public function getName(){ 
			return $this->_name;
		}
		
		public function setElementStyle($style='xml'){
			$this->_elementStyle = $style;
		}
		
		public function setEncoding($value){
			$this->_encoding = $value;
		}
	
		public function setVersion($value){
			$this->_version = $value;		
		}
	
		public function setIncludeHeader($value = false){
			$this->_includeHeader = $value;		
		}
	
		public function setValue($value, $prepend=true){
			if(!$prepend) $this->_placeValueAfterChildElements = true;
			$this->_value = $value;
		}
		
		public function setSelfClosingTag($value = true){
			$this->_selfclosing = $value;
		}
		
		public function setAllowEmptyAttributes($value = true){
			$this->_allowEmptyAttributes = $value;
		}
		
		public function setAttributeArray($array){
			if(empty($array) || !is_array($array)) return;
			
			foreach($array as $name => $value)
				$this->setAttribute($name, $value);
		}
	
		public function setAttribute($name, $value){
			$this->_attributes[$name] = $value;
		}
		
		public function getAttribute($name){
			if(!isset($this->_attributes[$name])) return NULL;			
			return $this->_attributes[$name];
		}
	
		public function addProcessingInstruction($pi){
			$this->_processingInstructions[] = $pi;
		}
		
		public function setDTD($dtd){
			$this->_dtd = $dtd;
		}
	
		public function appendChild(XMLElement $child){
			$this->_children[] = $child;
		}
		
		public function appendChildArray(array $children){
			foreach($children as $child) $this->appendChild($child);
		}
				
		public function prependChild(XMLElement $child){
			array_unshift($this->_children, $child);
		}
	
		public function getNumberOfChildren(){	
			return count($this->_children);
		}
		
		public function generate($indent=false, $tab_depth=0, $hasParent=false){
	
			$result = NULL;
		
			$newline = ($indent ? self::CRLF : NULL);
			
			if(!$hasParent){
				if($this->_includeHeader){
					$result .= sprintf('<?xml version="%s" encoding="%s" ?>', $this->_version, $this->_encoding) . $newline;
				}
				
				if($this->_dtd) $result .= $this->_dtd . $newline;
			
				if(is_array($this->_processingInstructions) && !empty($this->_processingInstructions)){
					$result .= implode(self::CRLF, $this->_processingInstructions);
				}
			}
			
			$result .= ($indent ? str_repeat("\t", $tab_depth) : NULL) . '<' . $this->_name;
		
			if(count($this->_attributes ) > 0 ){
			
				foreach($this->_attributes as $attribute => $value ){

					if(strlen($value) != 0 || (strlen($value) == 0 && $this->_allowEmptyAttributes)){
						$result .= sprintf(' %s="%s"', $attribute, $value);
					}
				}
			}
		
			$numberOfchildren = $this->getNumberOfChildren();
		
			if($numberOfchildren > 0 || strlen($this->_value) != 0 || !$this->_selfclosing){
		
				$result .= '>';
			
				if($this->_value != NULL && !$this->_placeValueAfterChildElements) $result .= $this->_value;
			
				if($numberOfchildren > 0 ){
			
					$result .= $newline;
				
					foreach($this->_children as $child ){
						if(!($child instanceof self)) throw new Exception('Child is not of type XMLElement');
						$child->setElementStyle($this->_elementStyle);
						$result .= $child->generate($indent, $tab_depth + 1, true);
					}
				
					if($indent) $result .= str_repeat("\t", $tab_depth);
				}
				
				if($this->_value != NULL && $this->_placeValueAfterChildElements){
					if($indent) $result .= str_repeat("\t", max(1, $tab_depth));
					$result .= $this->_value . $newline;
				}
				
				$result .= "</{$this->_name}>{$newline}";	
			
			// Empty elements:
			} else {
				if ($this->_elementStyle == 'xml') {
					$result .= ' />';
				} else if (in_array($this->_name, $this->_no_end_tags) || (substr($this->_name, 0, 3) == '!--')) {
					$result .= '>';					
				} else {
					$result .= "></{$this->_name}>";
				}
				
				$result .= $newline;
			}
		
			return $result;
		}
	}

