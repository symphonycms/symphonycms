<?php

	Class Layout{
		protected $_columns;
		protected $_proportions;
		public $div;
		
		public function __construct($cols=NULL, $proportions=NULL){
			$this->div = new XMLElement('div');
			$this->div->setAttribute('id', 'layout');
			$this->div->setAttribute('class', 'cols-' . $cols);
			
			$this->_columns = array();
			$this->_proportions = explode(':', $proportions);
			
			$i = 1;
			
			while($i <= $cols){
				$this->_columns[$i] = new XMLElement('div', NULL, array (
					'class' => 'column span-' . $this->_proportions[$i - 1]
				));
				$i++;
			}
		}
		
		public function appendToCol(XMLElement $element, $col){
			if($this->_columns[$col]){
				$this->_columns[$col]->appendChild($element);
			}
		}
		
		public function generate(){
			foreach($this->_columns as $col){
				$this->div->appendChild($col);
			}
			return $this->div;
		}
	}
