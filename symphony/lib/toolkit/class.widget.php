<?php
	/**
	 * @package toolkit
	 */
	/**
	 * Widget is a utility class that offers a number miscellaneous of
	 * functions to help generate common HTML Forms elements as XMLElement
	 * objects for inclusion in Symphony backend pages.
	 */
	Class Widget{

		/**
		 * Generates a XMLElement representation of `<label>`
		 *
		 * @param string $name (optional)
		 *  The text for the resulting `<label>`
		 * @param XMLElement $child (optional)
		 *	An XMLElement that this <label> will become the parent of.
		 *  Commonly used with `<input>`.
		 * @param string $class (optional)
		 *  The class attribute of the resulting `<label>`
		 * @param string $id (optional)
		 *  The id attribute of the resulting `<label>`
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function Label($name = null, XMLElement $child = null, $class = null, $id = null, Array $attributes = null){
			$obj = new XMLElement('label', ($name ? $name : null));

			if(is_object($child)) $obj->appendChild($child);

			if($class) $obj->setAttribute('class', $class);
			if($id) $obj->setAttribute('id', $id);

			if(is_array($attributes) && !empty($attributes)){
				$obj->setAttributeArray($attributes);
			}

			return $obj;
		}

		/**
		 * Generates a XMLElement representation of `<input>`
		 *
		 * @param string $name
		 *  The name attribute of the resulting `<input>`
		 * @param string $value (optional)
		 *	The value attribute of the resulting `<input>`
		 * @param string $type
		 *  The type attribute for this `<input>`, defaults to "text".
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function Input($name, $value = null, $type = 'text', Array $attributes = null){
			$obj = new XMLElement('input');
			$obj->setAttribute('name', $name);

			if($type) $obj->setAttribute('type', $type);

			if(strlen($value) != 0) $obj->setAttribute('value', $value);

			if(is_array($attributes) && !empty($attributes)){
				$obj->setAttributeArray($attributes);
			}

			return $obj;
		}

		/**
		 * Generates a XMLElement representation of `<textarea>`
		 *
		 * @param string $name
		 *  The name of the resulting `<textarea>`
		 * @param integer $rows (optional)
		 *	The height of the `<textarea>`, using the rows attribute. Defaults to 15
		 * @param integer $cols (optional)
		 *  The width of the `<textarea>`, using the cols attribute. Defaults to 50.
		 * @param string $value (optional)
		 *	The content to be displayed inside the `<textarea>`
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function Textarea($name, $rows = 15, $cols = 50, $value = null, Array $attributes = null){
			$obj = new XMLElement('textarea', $value);

			$obj->setSelfClosingTag(false);

			$obj->setAttribute('name', $name);
			$obj->setAttribute('rows', $rows);
			$obj->setAttribute('cols', $cols);

			if(is_array($attributes) && !empty($attributes)){
				$obj->setAttributeArray($attributes);
			}

			return $obj;
		}

		/**
		 * Generates a XMLElement representation of `<a>`
		 *
		 * @param string $value
		 *  The text of the resulting `<a>`
		 * @param string $href
		 *	The href attribute of the resulting `<a>`
		 * @param string $title (optional)
		 *  The title attribute of the resulting `<a>`
		 * @param string $class (optional)
		 *	The class attribute of the resulting `<a>`
		 * @param string $id (optional)
		 *	The id attribute of the resulting `<a>`
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function Anchor($value, $href, $title = null, $class = null, $id = null, Array $attributes = null){
			$obj = new XMLElement('a', $value);
			$obj->setAttribute('href', $href);

			if($title) $obj->setAttribute('title', $title);
			if($class) $obj->setAttribute('class', $class);
			if($id) $obj->setAttribute('id', $id);

			if(is_array($attributes) && !empty($attributes)){
				$obj->setAttributeArray($attributes);
			}

			return $obj;
		}

		/**
		 * Generates a XMLElement representation of `<form>`
		 *
		 * @param string $action
		 *  The text of the resulting `<form>`
		 * @param string $method
		 *	The method attribute of the resulting `<form>`. Defaults to "post".
		 * @param string $class (optional)
		 *	The class attribute of the resulting `<form>`
		 * @param string $id (optional)
		 *	The id attribute of the resulting `<form>`
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function Form($action = null, $method = 'post', $class = null, $id = null, Array $attributes = null){
			$obj = new XMLElement('form');
			$obj->setAttribute('action', $action);
			$obj->setAttribute('method', $method);

			if($class) $obj->setAttribute('class', $class);
			if($id) $obj->setAttribute('id', $id);

			if(is_array($attributes) && !empty($attributes)){
				$obj->setAttributeArray($attributes);
			}

			return $obj;
		}

		/**
		 * Generates a XMLElement representation of `<table>`
		 * This is a simple way to create generic Symphony table wrapper
		 *
		 * @param XMLElement $header
		 *  An XMLElement containing the `<thead>`. See Widget::TableHead
		 * @param XMLElement $footer
		 *  An XMLElement containing the `<tfoot>`
		 * @param XMLElement $body
		 *  An XMLElement containing the `<tbody>`. See Widget::TableBody
		 * @param string $class (optional)
		 *	The class attribute of the resulting `<table>`
		 * @param string $id (optional)
		 *	The id attribute of the resulting `<table>`
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function Table(XMLElement $header = null, XMLElement $footer = null, XMLElement $body = null, $class = null, $id = null, Array $attributes = null){
 			$obj = new XMLElement('table');

			if($class) $obj->setAttribute('class', $class);
			if($id) $obj->setAttribute('id', $id);

			if($header) $obj->appendChild($header);
			if($footer) $obj->appendChild($footer);
			if($body) $obj->appendChild($body);

			if(is_array($attributes) && !empty($attributes)){
				$obj->setAttributeArray($attributes);
			}

			return $obj;
		}


		/**
		 * Generates a XMLElement representation of `<thead>` from an array
		 * containing column names and any other attributes.
		 *
		 * @param Array $columns
		 *  An array of column arrays, where the first item is the name of the
		 *  column, the second is the scope attribute, and the third is an array
		 *  of possible attributes.
		 *  `
		 *   array(
		 *	 	array('Column Name', 'scope', array('class'=>'th-class'))
		 *	 )
		 *  `
		 * @return XMLElement
		 */
		public static function TableHead(Array $columns = null){

			$thead = new XMLElement('thead');
			$tr = new XMLElement('tr');

			if(is_array($columns) && !empty($columns)) {
				foreach($columns as $col) {
					$th = new XMLElement('th');

					$value = $scope = $attributes = null;

					$value = $col[0];
					if(isset($col[1])) $scope = $col[1];
					if(isset($col[2])) $attributes = $col[2];

					if(is_object($value)) {
						$th->appendChild($value);
					}
					else $th->setValue($value);

					if($scope && $scope != '') {
						$th->setAttribute('scope', $scope);
					}

					if(is_array($attributes) && !empty($attributes)) {
						$th->setAttributeArray($attributes);
					}

					$tr->appendChild($th);
				}
			}

			$thead->appendChild($tr);

			return $thead;
		}

		/**
		 * Generates a XMLElement representation of `<tbody>` from an array
		 * containing `<tr>` XMLElements
		 *
         * @see toolkit.Widget#TableRow()
		 * @param Array $rows
		 *  An array of XMLElements of `<tr>`'s.
		 * @param string $class (optional)
		 *	The class attribute of the resulting `<tbody>`
		 * @param string $id (optional)
		 *	The id attribute of the resulting `<tbody>`
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function TableBody(Array $rows, $class = null, $id = null, Array $attributes = null){
			$tbody = new XMLElement('tbody');

			if($class) $tbody->setAttribute('class', $class);
			if($id) $tbody->setAttribute('id', $id);

			if(is_array($attributes) && !empty($attributes)){
				$tbody->setAttributeArray($attributes);
			}

			foreach($rows as $row) $tbody->appendChild($row);

			return $tbody;
		}

		/**
		 * Generates a XMLElement representation of `<tr>` from an array
		 * containing column names and any other attributes.
         *
		 * @param Array $cells
		 *  An array of XMLElements of `<td>`'s. See Widget::TableData
		 * @param string $class (optional)
		 *	The class attribute of the resulting `<tr>`
		 * @param string $id (optional)
		 *	The id attribute of the resulting `<tr>`
		 * @param string $rowspan (optional)
		 *	The rowspan attribute of the resulting `<tr>`
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function TableRow(Array $cells, $class = null, $id = null, $rowspan = null, Array $attributes = null){
			$tr = new XMLElement('tr');

			if($class) $tr->setAttribute('class', $class);
			if($id) $tr->setAttribute('id', $id);
			if($rowspan) $tr->setAttribute('rowspan', $rowspan);

			if(is_array($attributes) && !empty($attributes)){
				$tr->setAttributeArray($attributes);
			}

			foreach($cells as $cell) $tr->appendChild($cell);

			return $tr;
		}

		/**
		 * Generates a XMLElement representation of a `<td>`.
         *
		 * @param XMLElement|string $value
		 *  Either an XMLElement object, or a string for the value of the
		 *  resulting `<td>`
		 * @param string $class (optional)
		 *	The class attribute of the resulting `<td>`
		 * @param string $id (optional)
		 *	The id attribute of the resulting `<td>`
		 * @param string $colspan (optional)
		 *	The colspan attribute of the resulting `<td>`
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function TableData($value, $class = null, $id = null, $colspan = null, Array $attributes = null){

			if(is_object($value)){
				$td = new XMLElement('td');
				$td->appendChild($value);
			}
			else {
				$td = new XMLElement('td', $value);
			}

			if($class) $td->setAttribute('class', $class);
			if($id) $td->setAttribute('id', $id);
			if($colspan) $td->setAttribute('colspan', $colspan);

			if(is_array($attributes) && !empty($attributes)){
				$td->setAttributeArray($attributes);
			}

			return $td;
		}

		/**
		 * Generates a XMLElement representation of a `<select>`. This uses
		 * the private function `__SelectBuildOption()` to build XMLElements of
		 * options given the `$options` array.
         * 
		 * @see toolkit.Widget::__SelectBuildOption()
		 * @param string $name
		 *  The name attribute of the resulting `<select>`
		 * @param Array $options (optional)
		 *	An array containing the data for each `<option>` for this
		 *  `<select>`. If the array is associative, it is assumed that
		 *  `<optgroup>` are to be created, otherwise it's an array of the
		 *  containing the option data. If no options are provided an empty
		 *  `<select>` XMLElement is returned.
		 *  `
		 *   array(
		 *	 	array($value, $selected, $desc, $class, $id, $attr)
		 *	 )
		 *   array(
		 *	 	array('label' => 'Optgroup', 'options' = array(
		 *			array($value, $selected, $desc, $class, $id, $attr)
		 *	 	)
		 *	 )
		 *  `
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public static function Select($name, Array $options = null, Array $attributes = null){
			$obj = new XMLElement('select');
			$obj->setAttribute('name', $name);

			$obj->setSelfClosingTag(false);

			if(is_array($attributes) && !empty($attributes)){
				$obj->setAttributeArray($attributes);
			}

			if(!is_array($options) || empty($options)){
				if(!isset($attributes['disabled'])) {
					$obj->setAttribute('disabled', 'disabled');
				}

				return $obj;
			}

			foreach($options as $o) {
				//	Optgroup
				if(isset($o['label'])) {
					$optgroup = new XMLElement('optgroup');
					$optgroup->setAttribute('label', $o['label']);

					foreach($o['options'] as $option){
						$optgroup->appendChild(
							Widget::__SelectBuildOption($option)
						);
					}

					$obj->appendChild($optgroup);
				}
				else {
					$obj->appendChild(Widget::__SelectBuildOption($o));
				}
			}

			return $obj;
		}

		/**
		 * This function is used internally by the `Widget::Select()` to build
		 * an XMLElement of an `<option>` from an array.
		 *
		 * @param Array $option
		 *	An array containing the data a single `<option>` for this
		 *  `<select>`. The array can contain the following params:
		 *		string $value
		 * 			The value attribute of the resulting `<option>`
		 * 		boolean $selected
		 * 			Whether this `<option>` should be selected
		 *		string $desc (optional)
		 * 			The text of the resulting `<option>`. If omitted $value will
		 * 			be used a default.
		 *		string $class (optional)
		 *			The class attribute of the resulting `<option>`
		 * 		string $id (optional)
		 * 			The id attribute of the resulting `<option>`
		 * 		array $attributes (optional)
		 * 			Any additional attributes can be included in an associative
		 *  		array with the key being the name and the value being the
		 *  		value of the attribute. Attributes set from this array
		 *  		will override existing attributes set by previous params.
		 *  `array(
		 *	 	array('one-shot', false, 'One Shot', 'my-option')
		 *	 )`
		 * @return XMLElement
		 */
		private static function __SelectBuildOption($option){

			@list($value, $selected, $desc, $class, $id, $attributes) = $option;
			if(!$desc) $desc = $value;

			$obj = new XMLElement('option', "$desc");
			$obj->setSelfClosingTag(false);
			$obj->setAttribute('value', "$value");

			if(!empty($class)) $obj->setAttribute('class', $class);

			if(!empty($id)) $obj->setAttribute('id', $id);

			if(is_array($attributes) && !empty($attributes)) {
				$obj->setAttributeArray($attributes);
			}

			if($selected) $obj->setAttribute('selected', 'selected');

			return $obj;

		}

		/**
		 * Will wrap a `<div>` around a desired element to trigger the default
		 * Symphony error styling.
		 *
		 * @param XMLElement $element
		 *	The element that should be wrapped with an error
		 * @param string $message
		 *	The text for this error. This will be appended after the $element,
		 *  but inside the wrapping `<div>`
		 * @return XMLElement
		 */
		public static function wrapFormElementWithError(XMLElement $element, $message){
			$div = new XMLElement('div');
			$div->setAttributeArray(array('id' => 'error', 'class' => 'invalid'));

			$div->appendChild($element);
			$div->appendChild(new XMLElement('p', $message));

			return $div;
		}

	}
