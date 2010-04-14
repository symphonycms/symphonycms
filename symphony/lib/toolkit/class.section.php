<?php

	require_once(TOOLKIT . '/class.fieldmanager.php');

	Class SectionException extends Exception {}

	Class SectionFilterIterator extends FilterIterator{
		public function __construct(){
			parent::__construct(new DirectoryIterator(SECTIONS));
		}

		public function accept(){
			if($this->isDir() == false && preg_match('/^(.+)\.xml$/i', $this->getFilename())){
				return true;
			}
			return false;
		}
	}

	Class SectionIterator implements Iterator{

		private $_iterator;
		private $_length;
		private $_position;

		public function __construct(){
			$this->_iterator = new SectionFilterIterator;
			$this->_length = $this->_position = 0;
			foreach($this->_iterator as $f){
				$this->_length++;
			}
			$this->_iterator->getInnerIterator()->rewind();
		}

		public function current(){
			return Section::load($this->_iterator->current()->getPathname());
		}

		public function innerIterator(){
			return $this->_iterator;
		}

		public function next(){
			$this->_position++;
			$this->_iterator->next();
		}

		public function key(){
			return $this->_iterator->key();
		}

		public function valid(){
			return $this->_iterator->valid();
		}

		public function rewind(){
			$this->_position = 0;
			$this->_iterator->rewind();
		}

		public function position(){
			return $this->_position;
		}

		public function length(){
			return $this->_length;
		}

	}


	Class Section{

		const ERROR_SECTION_NOT_FOUND = 0;
		const ERROR_FAILED_TO_LOAD = 1;
		const ERROR_DOES_NOT_ACCEPT_PARAMETERS = 2;
		const ERROR_TOO_MANY_PARAMETERS = 3;

		const ERROR_MISSING_OR_INVALID_FIELDS = 4;
		const ERROR_FAILED_TO_WRITE = 5;

		protected static $sections = array();

		protected $parameters;
		protected $fields;

		public function __construct(){
			$this->parameters = new StdClass;
			$this->fields = array();
		}

		public function __isset($name){
			return isset($this->parameters->$name);
		}

		public function __get($name){

			if($name == 'handle'){

				/*
				[4]   	NameStartChar	   ::=   	":" | [A-Z] | "_" | [a-z] | [#xC0-#xD6] | [#xD8-#xF6] | [#xF8-#x2FF] | [#x370-#x37D] | [#x37F-#x1FFF] | [#x200C-#x200D] | [#x2070-#x218F] | [#x2C00-#x2FEF] | [#x3001-#xD7FF] | [#xF900-#xFDCF] | [#xFDF0-#xFFFD] | [#x10000-#xEFFFF]
				[4a]   	NameChar	   ::=   	NameStartChar | "-" | "." | [0-9] | #xB7 | [#x0300-#x036F] | [#x203F-#x2040]
				*/

				//if(!isset($this->handle) || strlen(trim($this->parameters->handle)) < 0){
					$this->handle = Lang::createHandle($this->parameters->name, '-', false, true, array('/^[^:_a-z]+/i' => NULL, '/[^:_a-z0-9\.-]/i' => NULL));
				//}
			}

			elseif($name == 'guid'){
				if(is_null($this->parameters->guid)){
					$this->parameters->guid = uniqid();
				}
			}

			elseif($name == 'fields'){
				return $this->fields;
			}

			return $this->parameters->$name;
		}

		public function __set($name, $value){
			$this->parameters->$name = $value;
		}

		//public function initialise(){
		//	if(!($this->_about instanceof StdClass)) $this->_about = new StdClass;
		//}

		/*public function __get($name){

			if($name == 'classname'){
				$classname = Lang::createHandle($this->_about->name, '-', false, true, array('@^[^a-z]+@i' => NULL, '/[^\w-\.]/i' => NULL));
				$classname = str_replace(' ', NULL, ucwords(str_replace('-', ' ', $classname)));
				return 'section' . $classname;
			}
			elseif($name == 'handle'){
				if(!isset($this->_about->handle) || strlen(trim($this->_about->handle)) > 0){
					$this->handle = Lang::createHandle($this->_about->name, '-', false, true, array('@^[\d-]+@i' => ''));
				}
				return $this->_about->handle;

			}
			elseif($name == 'guid'){
				if(is_null($this->_about->guid)){
					$this->_about->guid = uniqid();
				}
				return $this->_about->guid;
			}
			return $this->_about->$name;
		}

		public function __set($name, $value){
			//if(in_array($name, array('path', 'template', 'handle', 'guid'))){
			//	$this->{"_{$name}"} = $value;
		//	}
		//	else
			if($name == 'guid') return; //guid cannot be set manually
			$this->_about->$name = $value;
		}*/

		public function appendField($type, array $data=NULL){
			
			$field = fieldManager::instance()->create($type);

			if(!is_null($data)){
				$field->setFromPOST($data);
			}

			$this->fields[] = $field;

			return $field;
		}

		public function removeAllFields(){
			$this->fields = array();
		}

		public function removeField($name){
			foreach($this->fields as $index => $f){
				if($f->get('label') == $name || $f->get('element_name') == $name){
					unset($this->fields[$index]);
				}
			}
		}

		public static function fetchUsedNavigationGroups(){
			$groups = array();
			foreach(new SectionIterator as $s){
				$groups[] = $s->{'navigation-group'};
			}
			return General::array_remove_duplicates($groups);
		}

		public static function load($path){
			$section = new self;

			$section->handle = preg_replace('/\.xml$/', NULL, basename($path));
			$section->path = dirname($path);

			if(!file_exists($path)){
				throw new SectionException(__('Section `%s` could not be found.', array(basename($path))), self::ERROR_SECTION_NOT_FOUND);
			}

			$doc = @simplexml_load_file($path);

			if(!($doc instanceof SimpleXMLElement)){
				throw new SectionException(__('Failed to load section configuration file: %s', array($path)), self::ERROR_FAILED_TO_LOAD);
			}

			foreach($doc as $name => $value){
				if($name == 'fields' && isset($value->field)){
					foreach($value->field as $field){
						$data = array();
						foreach($field as $property_name => $property_value){
							$data[(string)$property_name] = (string)$property_value;
						}
						try{
							$section->appendField($data['type'], $data);
						}
						catch(Exception $e){
							// Couldnt find the field. Ignore it for now
							// TO DO: Might need to more than just ignore it
						}
							
					}
				}

				elseif($name == 'layout' && isset($value->fieldset)){
					$section->layout = (object)array(
						'fieldsets' => array()
					);

					foreach($value->fieldset as $fieldset){
						$array = (object)array(
							'label' => (string)$fieldset->label,
							'rows' => array()
						);

						foreach($fieldset->row as $row){
							$new_row = array();
							foreach($row->fields->item as $field){
								$new_row[] = (string)$field;
							}
							$array->rows[] = $new_row;
						}

						$section->layout->fieldsets[] = $array;
					}
				}

				elseif(isset($value->item)){
					$stack = array();
					foreach($value->item as $item){
						array_push($stack, (string)$item);
					}
					$section->$name = $stack;
				}

				else{
					$section->$name = (string)$value;
				}
			}

			if(isset($doc->attributes()->guid)){
				$section->guid = (string)$doc->attributes()->guid;
			}
			else{
				$section->guid = uniqid();
			}

			return $section;
/*
			if(!isset(self::$_sections[$path])){
				self::$_sections[$path] = array('handle' => NULL, 'classname' => include_once($path));
			}

			$obj = new self::$_sections[$path]['classname'];

			self::$_sections[$path]['handle'] = $obj->handle;

			$obj->initialise();

			return $obj;*/
		}

		public function loadFromHandle($handle){
			return self::load(SECTIONS . '/' . $handle . '.xml');
		}

		public function synchroniseDataTables(){
			if(is_array($this->fields) && !empty($this->fields)){
				foreach($this->fields as $index => $field){
					$field->createTable();
				}
			}
		}

		public static function save(Section $section, MessageStack &$messages, array $additional_fragments=NULL, $simulate=false){

			$pathname = sprintf('%s/%s.xml', $section->path, $section->handle);

			## Check to ensure all the required section fields are filled
			if(!isset($section->name) || strlen(trim($section->name)) == 0){
				$messages->append('name', __('This is a required field.'));
			}

			## Check for duplicate section handle
			elseif(file_exists($pathname)){
				$existing = self::load($pathname);
				if($existing->guid != $section->guid){
					$messages->append('name', __('A Section with the name <code>%s</code> already exists', array($section->name)));
				}
				unset($existing);
			}

			## Check to ensure all the required section fields are filled
			if(!isset($section->{'navigation-group'}) || strlen(trim($section->{'navigation-group'})) == 0){
				$messages->append('navigation-group', __('This is a required field.'));
			}


			if(is_array($section->fields) && !empty($section->fields)){
				foreach($section->fields as $index => $field){
					$errors = NULL;
					if($field->checkFields($errors, false, false) != Field::__OK__ && !empty($errors)){
						$messages->append("field::{$index}", $errors);
					}
				}
			}

			if($messages->length() > 0){
				throw new SectionException(__('Section could not be saved. Validation failed.'), self::ERROR_MISSING_OR_INVALID_FIELDS);
			}

			$section->synchroniseDataTables();

			$doc = $section->toDoc($additional_fragments);

			return ($simulate == true ? true : file_put_contents($pathname, $doc->saveXML()));
		}

		public function toDoc(array $additional_fragments=NULL){
			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->formatOutput = true;

			$root = $doc->createElement('section');
			$doc->appendChild($root);

			if(!isset($this->guid) || is_null($this->guid)){
				$this->guid = uniqid();
			}

			$root->setAttribute('guid', $this->guid);

			$name = $doc->createElement('name', General::sanitize($this->name));
			$name->setAttribute('handle', $this->handle);

			$root->appendChild($name);
			$root->appendChild($doc->createElement('hidden-from-publish-menu', (
				isset($this->{'hidden-from-publish-menu'}) && strtolower(trim($this->{'hidden-from-publish-menu'})) == 'yes'
					? 'yes'
					: 'no'
			)));
			$root->appendChild($doc->createElement('navigation-group', General::sanitize($this->{'navigation-group'})));

			if(is_array($this->fields) && !empty($this->fields)){
				$fields = $doc->createElement('fields');
				foreach($this->fields as $index => $field){

					// the XML returned will have a declaration. Need to remove that.
					$string = trim(preg_replace('/<\?xml.*\?>/i', NULL, (string)$field, 1));

					// Prepare indenting by adding an 4 spaces to each line (except the first one)
					$string = preg_replace('/[\r\n]/', "\n    ", $string);

					$fragment = $doc->createDocumentFragment();
					$fragment->appendXML($string);
					$fields->appendChild($fragment);
				}
				$root->appendChild($fields);
			}

			if(!is_null($additional_fragments)){
				foreach($additional_fragments as $fragment){
					if(!($fragment instanceof DOMDocument)) continue;

					$node = $doc->importNode($fragment->documentElement, true);
					$root->appendChild($node);
				}
			}

			return $doc;
		}

		public function __toString(){
			return $this->toDoc()->saveXML();
		}

		/*public function __toString(){
			$template = file_get_contents(TEMPLATES . '/template.section.php');

			$vars = array(
				$this->classname,
				var_export($this->name, true),
				var_export($this->handle, true),
				var_export($this->{'navigation-group'}, true),
				var_export((bool)$this->hidden, true),
				var_export($this->guid, true),
			);

			return vsprintf($template, $vars);
		}*/
	}


	/*Class Section{

		var $_data;
		var $_Parent;
		var $_fields;
		var $_fieldManager;

		public function __construct(&$parent){
			$this->_Parent = $parent;
			$this->_data = $this->_fields = array();

			$this->_fieldManager = new FieldManager($this->_Parent);
		}

		public function fetchAssociatedSections(){
			return Symphony::Database()->fetch("SELECT *
													FROM `tbl_sections_association` AS `sa`, `tbl_sections` AS `s`
													WHERE `sa`.`parent_section_id` = '".$this->get('id')."'
													AND `s`.`id` = `sa`.`child_section_id`
													ORDER BY `s`.`sortorder` ASC
													");

		}

		public function set($field, $value){
			$this->_data[$field] = $value;
		}

		public function get($field=NULL){
			if($field == NULL) return $this->_data;
			return $this->_data[$field];
		}

		public function addField(){
			$this->_fields[] = new Field($this->_fieldManager);
		}

		public function fetchVisibleColumns(){
			return $this->_fieldManager->fetch(NULL, $this->get('id'), 'ASC', 'sortorder', NULL, NULL, " AND t1.show_column = 'yes' ");
		}

		public function fetchFields($type=NULL, $location=NULL){
			return $this->_fieldManager->fetch(NULL, $this->get('id'), 'ASC', 'sortorder', $type, $location);
		}

		public function fetchFilterableFields($location=NULL){
			return $this->_fieldManager->fetch(NULL, $this->get('id'), 'ASC', 'sortorder', NULL, $location, NULL, Field::__FILTERABLE_ONLY__);
		}

		public function fetchToggleableFields($location=NULL){
			return $this->_fieldManager->fetch(NULL, $this->get('id'), 'ASC', 'sortorder', NULL, $location, NULL, Field::__TOGGLEABLE_ONLY__);
		}

		public function fetchFieldsSchema(){
			return Symphony::Database()->fetch("SELECT `id`, `element_name`, `type`, `location` FROM `tbl_fields` WHERE `parent_section` = '".$this->get('id')."' ORDER BY `sortorder` ASC");
		}

		public function commit(){
			$fields = $this->_data;
			$retVal = NULL;

			if(isset($fields['id'])){
				$id = $fields['id'];
				unset($fields['id']);
				$retVal = $this->_Parent->edit($id, $fields);

				if($retVal) $retVal = $id;

			}else{
				$retVal = $this->_Parent->add($fields);
			}

			if(is_numeric($retVal) && $retVal !== false){
				for($ii = 0; $ii < count($this->_fields); $ii++){
					$this->_fields[$ii]->set('parent_section', $retVal);
					$this->_fields[$ii]->commit();
				}
			}
		}
	}
*/
