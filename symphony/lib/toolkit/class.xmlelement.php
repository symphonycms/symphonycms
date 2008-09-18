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
		
		const CRLF = "\r\n";
		
		function XMLElement($name, $value=NULL, $attributes=array()){
	
			$this->_name = $name;
			$this->_placeValueAfterChildElements = false;
			$this->_attributes = array();
			$this->_children = array();
			$this->_processingInstructions = array();
			
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
			return $this->_attributes[$name];
		}
	
		public function addProcessingInstruction($pi){
			$this->_processingInstructions[] = $pi;
		}
		
		public function setDTD($dtd){
			$this->_dtd = $dtd;
		}
	
		public function appendChild($child){
			array_push($this->_children, $child);
		}
		
		public function prependChild($child){
			array_unshift($this->_children, $child);
		}
	
		public function getNumberOfChildren(){	
			return count($this->_children);
		}
		
		public function generate($indent=false, $tab_depth=0, $hasParent=false){
	
			$result = NULL;
		
			$newline = ($indent ? self::CRLF : '');
			
			if(!$hasParent){
				if($this->_includeHeader){
					$result .= '<?xml version="'.$this->_version.'" encoding="'.$this->_encoding.'" ?>' . $newline;
				}
				
				if($this->_dtd) $result .= $this->_dtd . $newline;
			
				if(is_array($this->_processingInstructions) && !empty($this->_processingInstructions)){
					$result .= implode(self::CRLF, $this->_processingInstructions);
				}
			}
			
			$result .= ($indent ? General::repeatStr("\t", $tab_depth) : '') . '<' . $this->_name;
		
			if(count($this->_attributes ) > 0 ){
			
				foreach($this->_attributes as $attribute => $value ){

					if(strlen($value) != 0 || (strlen($value) == 0 && $this->_allowEmptyAttributes)){
						$result .= " $attribute=\"$value\"";
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
						$child->setElementStyle($this->_elementStyle);
						$result .= $child->generate($indent, $tab_depth + 1, true);
					}
				
					if($indent) $result .= General::repeatStr("\t", $tab_depth);
				}
				
				if($this->_value != NULL && $this->_placeValueAfterChildElements){
					if($indent) $result .= General::repeatStr("\t", max(1, $tab_depth));
					$result .= $this->_value . $newline;
				}
				
				$result .= '</' . $this->_name . '>' . $newline;	
			
			
			}
			
			else $result .= ($this->_elementStyle == 'xml' ? ' />' : '>') . $newline;
		
			return $result;
		}
	}

