<?php

	Class Widget{

		public static function Label($name=null, SymphonyDOMElement $child=null, array $attributes = array()){
			$label = $this->createElement('label', $name, $attributes);

			if($child instanceof SymphonyDOMElement) $label->appendChild($child);

			return $label;
		}

		public static function Input($name, $value=null, $type='text', array $attributes = array()){
			$obj = $this->createElement('input', null, $attributes);
			$obj->setAttribute('name', $name);

			if($type) $obj->setAttribute('type', $type);

			if(strlen($value) != 0) $obj->setAttribute('value', $value);

			return $obj;
		}

		public static function Textarea($name, $rows, $cols, $value=null, array $attributes = array()){
			$obj = $this->createElement('textarea', $value, $attributes);

			$obj->appendChild($this->createTextNode(''));

			$obj->setAttribute('name', $name);
			$obj->setAttribute('rows', $rows);
			$obj->setAttribute('cols', $cols);

			return $obj;
		}

		public static function Anchor($value, $href, array $attributes = array()){
			$a = $this->createElement('a', $value, $attributes);
			$a->setAttribute('href', $href);

			return $a;
		}

		public static function Form($action, $method, array $attributes = array()){
			$form = $this->createElement('form', null, $attributes);
			$form->setAttribute('action', $action);
			$form->setAttribute('method', $method);

			return $form;
		}

		## First take at a generic fieldset builder for the new form layout
		public static function Fieldset($title=null, $help=null, array $attributes = array()){
			$fieldset = $this->createElement('fieldset', null, $attributes);

			if($title){
				$fieldset->appendChild(
					$this->createElement('h3',$title)
				);
			}
			if($help){
				$fieldset->appendChild(
					$this->createElement('p', $help, array(
							'class' => 'help'
					))
				);
			}

			return $fieldset;
		}

		###
		# Simple way to create generic Symphony table wrapper
		public static function Table($head=null, $foot=null, $body=null, array $attributes = array()){
 			$table = $this->createElement('table');

			$table->setAttributeArray($attributes);

			if($head) $table->appendChild($head);
			if($foot) $table->appendChild($foot);
			if($body) $table->appendChild($body);

			return $table;
		}

		## Provided with an array of colum names, this will return an XML object
		public static function TableHead($array){

			$thead = $this->createElement('thead');
			$tr = $this->createElement('tr');

			foreach($array as $col){

				$th = $this->createElement('th');

				$value = $scope = $attr = null;

				$value = $col[0];
				if(isset($col[1])) $scope = $col[1];
				if(isset($col[2])) $attr = $col[2];

				$th->setValue($value);

				if($scope && $scope != '') $th->setAttribute('scope', $scope);

				if(is_array($attr) && !empty($attr)) $th->setAttributeArray($attr);

				$tr->appendChild($th);
			}

			$thead->appendChild($tr);

			return $thead;

		}

		public static function TableBody($rows, array $attributes = array()){
			$tbody = $this->createElement('tbody');
			$tbody->setAttributeArray($attributes);

			foreach($rows as $r) $tbody->appendChild($r);

			return $tbody;
		}

		public static function TableData($value, array $attributes = array()){

			$td = $this->createElement('td');
			$td->setValue($value);

			$td->setAttributeArray($attributes);

			return $td;
		}

		public static function TableRow($data, array $attributes = array()){
			$tr = $this->createElement('tr');
			$tr->setAttributeArray($attributes);

			foreach($data as $d) $tr->appendChild($d);

			return $tr;
		}

		public static function Select($name, $options, array $attributes = array()){
			$obj = $this->createElement('select', null, $attributes);
			$obj->setAttribute('name', $name);

			$obj->appendChild($this->createTextNode(''));

			if(!is_array($options) || empty($options)){
				if(!isset($attributes['disabled'])) $obj->setAttribute('disabled', 'disabled');

				return $obj;
			}

			foreach($options as $o){

				## Opt Group
				if(isset($o['label'])){

					$optgroup = $this->createElement('optgroup');
					$optgroup->setAttribute('label', $o['label']);

					foreach($o['options'] as $opt){
						$optgroup->appendChild(Widget::__SelectBuildOption($opt));
					}

					$obj->appendChild($optgroup);
				}

				## Non-Opt group
				else $obj->appendChild(Widget::__SelectBuildOption($o));

			}

			return $obj;
		}

		private static function __SelectBuildOption($option){

			@list($value, $selected, $desc, $class, $id, $attr) = $option;
			if(!$desc) $desc = $value;

			$obj = $this->createElement('option', "$desc");
			$obj->appendChild($this->createTextNode(''));
			$obj->setAttribute('value', (string)$value);

			if(!empty($class)) $obj->setAttribute('class', $class);
			if(!empty($id)) $obj->setAttribute('id', $id);
			if($selected) $obj->setAttribute('selected', 'selected');

			if(!empty($attr)) $obj->setAttributeArray($attr);

			return $obj;

		}

		public static function wrapFormElementWithError($element, $message=null){
			$div = $this->createElement('div');
			$div->setAttributeArray(array('id' => 'error', 'class' => 'invalid'));

			$div->appendChild($element);

			if(!is_null($message)){
				$div->appendChild(
					$this->createElement('p', $message)
				);
			}

			return $div;

		}

	}

