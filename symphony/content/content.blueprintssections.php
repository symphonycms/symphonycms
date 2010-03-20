<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.messagestack.php');	
 	//require_once(TOOLKIT . '/class.sectionmanager.php');
 	require_once(TOOLKIT . '/class.fieldmanager.php');
	//require_once(TOOLKIT . '/class.entrymanager.php');

	Class SectionException extends Exception {}

	Class SectionFilterIterator extends FilterIterator{
		public function __construct(){
			parent::__construct(new DirectoryIterator(SECTIONS));		
		}
	
		public function accept(){
			if($this->isDir() == false && preg_match('/^section\.(.+)\.php$/i', $this->getFilename())){
				return true;
			}
			return false;
		}
	}	
	
	Class SectionIterator implements Iterator{

		private $_iterator;
		private $_length;
		private $_position;

		public function __construct($path=NULL, $recurse=true){
			$this->_iterator = new SectionFilterIterator;
			$this->_length = $this->_position = 0;
			foreach($this->_iterator as $f){
				$this->_length++;
			}
			$this->_iterator->getInnerIterator()->rewind();
		}

		public function current(){
			return Section::loadFromPath($this->_iterator->current()->getPathname());
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
		
		protected static $_sections = array();
		protected $_about;

		public function __isset($name){
			//if(in_array($name, array('path', 'template', 'handle', 'guid'))){
			//	return isset($this->{"_{$name}"});
		//	}
			return isset($this->_about->$name);		
		}
		
		public function initialise(){
			if(!($this->_about instanceof StdClass)) $this->_about = new StdClass;
		}
		
		public function __get($name){
			
			if($name == 'classname'){
				$classname = Lang::createHandle($this->_about->name, NULL, '-', false, true, array('@^[^a-z]+@i' => NULL, '/[^\w-\.]/i' => NULL));
				$classname = str_replace(' ', NULL, ucwords(str_replace('-', ' ', $classname)));
				return 'section' . $classname;
			}
			elseif($name == 'handle'){
				if(!isset($this->_about->handle) || strlen(trim($this->_about->handle)) > 0){
					$this->handle = Lang::createHandle($this->_about->name, NULL, '-', false, true, array('@^[\d-]+@i' => ''));
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
		}
		
		public static function fetchUsedNavigationGroups(){
			$groups = array();
			foreach(new SectionIterator as $s){
				$groups[] = $s->{'navigation-group'};
			}
			return General::array_remove_duplicates($groups);
		}
		
		public static function loadFromPath($path){

			if(!isset(self::$_sections[$path])){
				self::$_sections[$path] = array('handle' => NULL, 'classname' => include_once($path));
			}

			$obj = new self::$_sections[$path]['classname'];

			self::$_sections[$path]['handle'] = $obj->handle;
			
			$obj->initialise();
			
			return $obj;
		}
		
		public static function loadFromHandle($handle){

			$classname = NULL;

			if(is_array(self::$_sections) && !empty(self::$_sections)){
				foreach(self::$_sections as $s){
					if($s['handle'] == $handle) $classname = $s['classname'];
				}
			}

			if(is_null($classname)){
				foreach(new SectionIterator as $section){
					if($section->handle == $handle) return $section;
				}
			}

			if(is_null($classname)){
				throw new SectionException("Could not locate section with handle '{$handle}'.");
			}

			$obj = new $classname;
			
			$obj->initialise();
			
			return $obj;
		}
		
		public static function save(Section $section, MessageStack &$messages, $simulate=false){

			## Check to ensure all the required section fields are filled
			if(!isset($section->name) || strlen(trim($section->name)) == 0){
				$messages->append('name', __('This is a required field.'));
			}

			## Check for duplicate section handle
			elseif(file_exists(SECTIONS . "/section.{$section->handle}.php")){
				$existing = self::loadFromPath(SECTIONS . "/section.{$section->handle}.php");
				if($existing->guid != $section->guid){
					$messages->append('name', __('A Section with the name <code>%s</code> already exists', array($section->name)));
				}
				unset($existing);
			}
			
			## Check to ensure all the required section fields are filled
			if(!isset($section->{'navigation-group'}) || strlen(trim($section->{'navigation-group'})) == 0){
				$messages->append('navigation-group', __('This is a required field.'));
			}
			
			if($messages->length() > 0){
				throw new SectionException(__('Section could not be saved. Validation failed.'), self::ERROR_MISSING_OR_INVALID_FIELDS);
			}
			
			return ($simulate == true ? true : file_put_contents(SECTIONS . "/section.{$section->handle}.php", (string)$section));
		}
		
		public function __toString(){
			$template = file_get_contents(TEMPLATE . '/section.tpl');

			$vars = array(
				$this->classname,
				var_export($this->name, true),
				var_export($this->handle, true),
				var_export($this->{'navigation-group'}, true),
				var_export((bool)$this->hidden, true),
				var_export($this->guid, true),
			);
			
			return vsprintf($template, $vars);
		}
	}


	Class contentBlueprintsSections extends AdministrationPage{

		public $_errors;

		public function __viewIndex(){
			$this->setPageType('table');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Sections'))));
			$this->appendSubheading(__('Sections'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a section'), 'create button'));

		    $sections = new SectionIterator;

			$aTableHead = array(
				array(__('Name'), 'col'),
				array(__('Entries'), 'col'),
				array(__('Navigation Group'), 'col'),
			);	

			$aTableBody = array();

			if($sections->length() <= 0){
				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{

				foreach($sections as $s){

					$entry_count = (int)Symphony::Database()->fetchVar('count', 0, 
						"SELECT count(*) AS `count` FROM `tbl_entries` WHERE `section_id` = '{$s->handle}' "
					);

					## Setup each cell
					$td1 = Widget::TableData(Widget::Anchor($s->name, Administration::instance()->getCurrentPageURL() . "edit/{$s->handle}/", NULL, 'content'));
					$td2 = Widget::TableData(Widget::Anchor((string)$entry_count, ADMIN_URL . "/publish/{$s->handle}/"));
					$td3 = Widget::TableData($s->{'navigation-group'});

					$td3->appendChild(Widget::Input('items['.$s->handle.']', 'on', 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3));
				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead), NULL, Widget::TableBody($aTableBody)
			);
			$table->setAttribute('id', 'sections-list');

			$this->Form->appendChild($table);
			
			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			
			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm'),
				array('delete-entries', false, __('Delete Entries'), 'confirm')
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);

		}
		
		private function __save(array $essentials, array $fields=NULL, Section $section=NULL){
			if(is_null($section)) $section = new Section;
			
			$section->name = $essentials['name'];
			$section->{'navigation-group'} = $essentials['navigation-group'];
			$section->hidden = (bool)(isset($essentials['hidden']) && $essentials['hidden'] == 'yes');
			
			$this->_errors = new MessageStack;
			try{
				Section::save($section, $this->_errors);
				return $section;
			}
			catch(SectionException $e){
				switch($e->getCode()){
					case Section::ERROR_MISSING_OR_INVALID_FIELDS:
						// Dont really need to do anything since everything was captured in the MessageStack object
						break;

					case Section::ERROR_FAILED_TO_WRITE:
						$this->pageAlert($e->getMessage(), Alert::ERROR);
						break;
				}
			}
			catch(Exception $e){
				// Errors!!
				// Not sure what happened!!
				$this->pageAlert(__("An unknown error has occurred. %s", $e->getMessage()), Alert::ERROR);
			}
			
			return false;
		}
		
		public function __actionNew(){
			if(isset($_POST['action']['save'])){
				$section = $this->__save($_POST['essentials'], (isset($_POST['fields']) ? $_POST['fields'] : NULL));
				if($section instanceof Section){
					redirect(ADMIN_URL . "/blueprints/sections/edit/{$section->handle}/:created/");
				}
			}
		}

		public function __actionEdit(){
			if(isset($_POST['action']['save'])){
				$section = $this->__save($_POST['essentials'], (isset($_POST['fields']) ? $_POST['fields'] : NULL), Section::loadFromHandle($this->_context[1]));
				if($section instanceof Section){
					redirect(ADMIN_URL . "/blueprints/sections/edit/{$section->handle}/:saved/");
				}
			}
		}
		
		private static function __loadExistingSection($handle){
			try{
				$existing = Section::loadFromHandle($handle);
				return $existing;
			}
			catch(SectionException $e){
				
				switch($e->getCode()){
					case Section::ERROR_SECTION_NOT_FOUND:
						throw new SymphonyErrorPage(
							__('The section you requested to edit does not exist.'), 
							__('Section not found'), 
							'error', 
							array(
								'header' => 'HTTP/1.0 404 Not Found'
							)
						);
						break;

					default:
					case Section::ERROR_FAILED_TO_LOAD:
						throw new SymphonyErrorPage(
							__('The section you requested could not be loaded. Please check it is readable.'), 
							__('Failed to load section'), 
							'error'
						);
						break;
				}
			}
			catch(Exception $e){
				throw new SymphonyErrorPage(
					sprintf(__("An unknown error has occurred. %s"), $e->getMessage()), 
					__('Unknown Error'), 
					'error', 
					array(
						'header' => 'HTTP/1.0 500 Internal Server Error'
					)
				);
			}
		}
		
		public function __viewNew(){
			$this->__form(new Section);
		}
		
		public function __viewEdit(){
			$this->__form(self::__loadExistingSection($this->_context[1]), self::__loadExistingSection($this->_context[1]));
		}
		
		private function __form(Section $section, Section $existing=NULL){
			
			// Status message:
			$callback = Administration::instance()->getPageCallback();
			if(isset($callback['flag']) && !is_null($callback['flag'])){

				switch($callback['flag']){
					
					case 'saved':

						$this->pageAlert(
							__(
								'Section updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Views</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									ADMIN_URL . '/blueprints/sections/new/',
									ADMIN_URL . '/blueprints/sections/',
								)
							), 
							Alert::SUCCESS);
													
						break;
						
					case 'created':

						$this->pageAlert(
							__(
								'Section created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Views</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									ADMIN_URL . '/blueprints/sections/new/',
									ADMIN_URL . '/blueprints/sections/',
								)
							), 
							Alert::SUCCESS);
							
						break;

				}
			}
			
			$this->setPageType('form');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Sections'))));
			$this->appendSubheading(($existing instanceof Section ? $existing->name : __('Untitled')));
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('essentials[name]', $section->name));
			
			if(isset($this->_errors->name)) $namediv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors->name));
			else $namediv->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('essentials[hidden]', 'yes', 'checkbox', ($section->hidden == true ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Hide this section from the Publish menu', array($input->generate(false))));
			$namediv->appendChild($label);
			$div->appendChild($namediv);
			
			$navgroupdiv = new XMLElement('div', NULL);

			$label = Widget::Label('Navigation Group <i>Created if does not exist</i>');
			$label->appendChild(Widget::Input('essentials[navigation-group]', $section->{"navigation-group"}));

			if(isset($this->_errors->{'navigation-group'})) $navgroupdiv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors->{'navigation-group'}));
			else $navgroupdiv->appendChild($label);
			
			$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
			foreach(Section::fetchUsedNavigationGroups() as $g){
				$ul->appendChild(new XMLElement('li', $g));
			}
			$navgroupdiv->appendChild($ul);

			$div->appendChild($navgroupdiv);
			
			$fieldset->appendChild($div);
			
			$this->Form->appendChild($fieldset);
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Fields')));
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Fields'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'section-' . $section_id);
			$ol->setAttribute('class', 'section-duplicator');
			
			/*if(is_array($fields) && !empty($fields)){
				foreach($fields as $position => $field){

					$wrapper = new XMLElement('li');
					
					$field->set('sortorder', $position);
					$field->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
					$ol->appendChild($wrapper);

				}
			}*/
			
			$types = array();
			foreach (FieldManager::instance()->fetchTypes() as $type) {
				if ($type = FieldManager::instance()->create($type)) {
					array_push($types, $type);
				}
			}
			
			uasort($types, create_function('$a, $b', 'return strnatcasecmp($a->_name, $b->_name);'));
			
			foreach ($types as $type) {		
				$defaults = array();
				
				$type->findDefaults($defaults);			
				$type->setArray($defaults);
				
				$wrapper = new XMLElement('li');
				$wrapper->setAttribute('class', 'template');
				
				$type->set('sortorder', '-1');
				$type->displaySettingsPanel($wrapper);
				
				$ol->appendChild($wrapper);
			}
			
			$div->appendChild($ol);
			$fieldset->appendChild($div);
			
			$this->Form->appendChild($fieldset);
			
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));
			
			if($editing == true){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this section'), 'type' => 'submit'));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);
		}
		
/*	
		public function __viewNew(){
			
			$this->setPageType('form');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Sections'))));
			$this->appendSubheading(__('Untitled'));
			
			$types = array();
			
		    $fields = $_POST['fields'];
			$meta = $_POST['meta'];
			
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			
			@ksort($fields);

			$showEmptyTemplate = (is_array($fields) && !empty($fields) ? false : true);

			$meta['entry_order'] = (isset($meta['entry_order']) ? $meta['entry_order'] : 'date');
			$meta['subsection'] = (isset($meta['subsection']) ? 1 : 0);	
			$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');	
			$meta['navigation_group'] = (isset($meta['navigation_group']) ? $meta['navigation_group'] : 'Content');

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('meta[name]', $meta['name']));
			
			if(isset($this->_errors['name'])) $namediv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $namediv->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Hide this section from the Publish menu', array($input->generate(false))));
			$namediv->appendChild($label);
			$div->appendChild($namediv);
			
			$navgroupdiv = new XMLElement('div', NULL);
			$sections = SectionManager::instance()->fetch(NULL, 'ASC', 'sortorder');
			$label = Widget::Label('Navigation Group <i>Created if does not exist</i>');
			$label->appendChild(Widget::Input('meta[navigation_group]', $meta['navigation_group']));

			if(isset($this->_errors['navigation_group'])) $navgroupdiv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['navigation_group']));
			else $navgroupdiv->appendChild($label);
			
			if(is_array($sections) && !empty($sections)){
				$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
				$groups = array();
				foreach($sections as $s){
					if(in_array($s->get('navigation_group'), $groups)) continue;
					$ul->appendChild(new XMLElement('li', $s->get('navigation_group')));
					$groups[] = $s->get('navigation_group');
				}

				$navgroupdiv->appendChild($ul);
			}
			
			$div->appendChild($navgroupdiv);
			
			$fieldset->appendChild($div);						
			
			$this->Form->appendChild($fieldset);		

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Fields')));
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Fields'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'section-duplicator');
			
			if(!$showEmptyTemplate){
				foreach($fields as $position => $data){
					if($input = fieldManager::instance()->create($data['type'])){
						$input->setArray($data);

						$wrapper = new XMLElement('li');
						
						$input->set('sortorder', $position);
						$input->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
						$ol->appendChild($wrapper);

					}
				}
			}
			
			foreach (fieldManager::instance()->fetchTypes() as $type) {
				if ($type = fieldManager::instance()->create($type)) {
					array_push($types, $type);
				}
			}
			
			uasort($types, create_function('$a, $b', 'return strnatcasecmp($a->_name, $b->_name);'));
			
			foreach ($types as $type) {		
				$defaults = array();
				
				$type->findDefaults($defaults);			
				$type->setArray($defaults);
				
				$wrapper = new XMLElement('li');
				$wrapper->setAttribute('class', 'template');
				
				$type->set('sortorder', '-1');
				$type->displaySettingsPanel($wrapper);
				
				$ol->appendChild($wrapper);
			}

			$div->appendChild($ol);
			$fieldset->appendChild($div);

			$this->Form->appendChild($fieldset);
		
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Create Section'), 'submit', array('accesskey' => 's')));

			$this->Form->appendChild($div);			
			
		}
		
		public function __viewEdit(){
			
			$section_id = $this->_context[1];	


		    if(!$section = SectionManager::instance()->fetch($section_id)) 
				Administration::instance()->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking for could not be found.'), false, true);

			$meta = $section->get();
			
			$types = array();

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));			
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);	


			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
						$this->pageAlert(
							__(
								'Section updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Sections</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									ADMIN_URL . '/blueprints/sections/new/', 
									ADMIN_URL . '/blueprints/sections/' 
								)
							), 
							Alert::SUCCESS);
						break;
						
					case 'created':
						$this->pageAlert(
							__(
								'Section created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Sections</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									ADMIN_URL . '/blueprints/sections/new/', 
									ADMIN_URL . '/blueprints/sections/' 
								)
							), 
							Alert::SUCCESS);
						break;
					
				}
			}
			
			if(isset($_POST['fields'])){
				$fields = array();

				if(is_array($_POST['fields']) && !empty($_POST['fields'])){
					foreach($_POST['fields'] as $position => $data){
						if($fields[$position] = fieldManager::instance()->create($data['type'])){
							$fields[$position]->setArray($data);
							$fields[$position]->set('sortorder', $position);
						}
					}
				}
			}

			else $fields = fieldManager::instance()->fetch(NULL, $section_id);

			$meta['subsection'] = ($meta['subsection'] == 'yes' ? 1 : 0);
			$meta['entry_order'] = (isset($meta['entry_order']) ? $meta['entry_order'] : 'date');
			
			if(isset($_POST['meta'])){ 
				$meta = $_POST['meta'];
				$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');
				
				if($meta['name'] == '') $meta['name'] = $section->get('name');
			}
			
			$this->setPageType('form');
			$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(__('Symphony'), __('Sections'), $meta['name'])));
			$this->appendSubheading($meta['name']);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('meta[name]', $meta['name']));
			
			if(isset($this->_errors['name'])) $namediv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $namediv->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Hide this section from the Publish menu', array($input->generate(false))));
			$namediv->appendChild($label);
			$div->appendChild($namediv);
			
			$navgroupdiv = new XMLElement('div', NULL);
			$sections = SectionManager::instance()->fetch(NULL, 'ASC', 'sortorder');
			$label = Widget::Label(__('Navigation Group ') . '<i>' . __('Choose only one. Created if does not exist') . '</i>');
			$label->appendChild(Widget::Input('meta[navigation_group]', $meta['navigation_group']));

			if(isset($this->_errors['navigation_group'])) $navgroupdiv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['navigation_group']));
			else $navgroupdiv->appendChild($label);
			
			if(is_array($sections) && !empty($sections)){
				$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
				$groups = array();
				foreach($sections as $s){
					if(in_array($s->get('navigation_group'), $groups)) continue;
					$ul->appendChild(new XMLElement('li', $s->get('navigation_group')));
					$groups[] = $s->get('navigation_group');
				}

				$navgroupdiv->appendChild($ul);
			}

			$div->appendChild($navgroupdiv);
			
			$fieldset->appendChild($div);
			
			$this->Form->appendChild($fieldset);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Fields')));
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Fields'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'section-' . $section_id);
			$ol->setAttribute('class', 'section-duplicator');
			
			if(is_array($fields) && !empty($fields)){
				foreach($fields as $position => $field){

					$wrapper = new XMLElement('li');
					
					$field->set('sortorder', $position);
					$field->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
					$ol->appendChild($wrapper);

				}
			}
			
			foreach (fieldManager::instance()->fetchTypes() as $type) {
				if ($type = fieldManager::instance()->create($type)) {
					array_push($types, $type);
				}
			}
			
			uasort($types, create_function('$a, $b', 'return strnatcasecmp($a->_name, $b->_name);'));
			
			foreach ($types as $type) {		
				$defaults = array();
				
				$type->findDefaults($defaults);			
				$type->setArray($defaults);
				
				$wrapper = new XMLElement('li');
				$wrapper->setAttribute('class', 'template');
				
				$type->set('sortorder', '-1');
				$type->displaySettingsPanel($wrapper);
				
				$ol->appendChild($wrapper);
			}
			
			$div->appendChild($ol);
			$fieldset->appendChild($div);
			
			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));
		
			$button = new XMLElement('button', __('Delete'));
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this section'), 'type' => 'submit'));
			$div->appendChild($button);

			$this->Form->appendChild($div);
		}
		
		public function __actionIndex(){

			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

					case 'delete':
					
						foreach($checked as $section_id) SectionManager::instance()->delete($section_id);

						redirect(ADMIN_URL . '/blueprints/sections/');
						break;
						
					case 'delete-entries':

						foreach($checked as $section_id) {
							$entries = EntryManager::instance()->fetch(NULL, $section_id, NULL, NULL, NULL, NULL, false, false);
							$entry_ids = array();
							foreach($entries as $entry) {
								$entry_ids[] = $entry['id'];
							}
							EntryManager::instance()->delete($entry_ids);
						}
						
						redirect(ADMIN_URL . '/blueprints/sections/');
						break;
				}
			}
						
		}
		
		public function __actionNew(){		
			
			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$canProceed = true;

			    $fields = $_POST['fields'];
				$meta = $_POST['meta'];
				
				$this->_errors = array();
					
				## Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || strlen(trim($meta['name'])) == 0){
					$required = array('Name');
					$this->_errors['name'] = __('This is a required field.');
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif(Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `name` = '" . $meta['name'] . "' LIMIT 1")){
					$this->_errors['name'] = __('A Section with the name <code>%s</code> name already exists', array($meta['name']));
					$canProceed = false;
				}
				
				## Check to ensure all the required section fields are filled
				if(!isset($meta['navigation_group']) || strlen(trim($meta['navigation_group'])) == 0){
					$required = array('Navigation Group');
					$this->_errors['navigation_group'] = __('This is a required field.');
					$canProceed = false;
				}				
				
				## Basic custom field checking
				if(is_array($fields) && !empty($fields)){

					$name_list = array();

					foreach($fields as $position => $data){
						if(trim($data['element_name']) == '') 
							$data['element_name'] = $fields[$position]['element_name'] = Lang::createHandle($data['label'], NULL, '-', false, true, array('@^[\d-]+@i' => ''));

						if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
							$this->_errors[$position] = array('element_name' => __('Two custom fields have the same element name. All element names must be unique.'));
							$canProceed = false;
							break;						
						}		
						$name_list[] = $data['element_name'];
					}	


					$unique = array();

					foreach($fields as $position => $data){
						$required = NULL;

						$field = fieldManager::instance()->create($data['type']);
						$field->setFromPOST($data);

						if($field->mustBeUnique() && !in_array($field->get('type'), $unique)) $unique[] = $field->get('type');
						elseif($field->mustBeUnique() && in_array($field->get('type'), $unique)){
							## Warning. cannot have 2 of this field!
							$canProceed = false;
							$this->_errors[$position] = array('label' => __('There is already a field of type <code>%s</code>. There can only be one per section.', array($field->name())));
						}

						$errors = array();

						if(Field::__OK__ != $field->checkFields($errors, false, false) && !empty($errors)){
							$this->_errors[$position] = $errors;
							$canProceed = false;
							break;					
						}
					}
				}


				if($canProceed){

			        $query = 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_sections LIMIT 1';
			        $next = Symphony::Database()->fetchVar('next', 0, $query);

			        $meta['sortorder'] = ($next ? $next : '1');
					$meta['handle'] = Lang::createHandle($meta['name']);
					

					if(!$section_id = SectionManager::instance()->add($meta)){
						$this->pageAlert(__('An unknown database occurred while attempting to create the section.'), Alert::ERROR);
					}

					else{

						## Save each custom field
						if(is_array($fields) && !empty($fields)){
							foreach($fields as $position => $data){

								$field = fieldManager::instance()->create($data['type']);
								$field->setFromPOST($data);
								$field->set('sortorder', $position);
								$field->set('parent_section', $section_id);

								$field->commit();	

								$field_id = $field->get('id');

						        if($field_id){

									###
									# Delegate: FieldPostCreate
									# Description: After creation of an Field. New Field object is provided.
									ExtensionManager::instance()->notifyMembers('FieldPostCreate', '/blueprints/sections/', array('field' => &$field, 'data' => &$data));

						        }
							}
						}

						## TODO: Fix me
						###
						# Delegate: Create
						# Description: Creation of a new Section. Section ID and Primary Field ID are provided.
						#ExtensionManager::instance()->notifyMembers('Create', getCurrentPage(), array('section_id' => $section_id));

		               	redirect(ADMIN_URL . "/blueprints/sections/edit/$section_id/created/");
								

			        }       
			    }
			}
			
		}
		
		public function __actionEdit(){


			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$canProceed = true;

			    $fields = $_POST['fields'];
				$meta = $_POST['meta'];

				$section_id = $this->_context[1];	
				$existing_section = SectionManager::instance()->fetch($section_id);


				$this->_errors = array();

				## Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || trim($meta['name']) == ''){
					$required = array('Name');
					$this->_errors['name'] = __('This is a required field.');
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif($meta['name'] != $existing_section->get('name') && Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `name` = '" . $meta['name'] . "' AND `id` != {$section_id} LIMIT 1")){
					$this->_errors['name'] = __('A Section with the name <code>%s</code> name already exists', array($meta['name']));
					$canProceed = false;
				}

				## Check to ensure all the required section fields are filled
				if(!isset($meta['navigation_group']) || strlen(trim($meta['navigation_group'])) == 0){
					$required = array('Navigation Group');
					$this->_errors['navigation_group'] = __('This is a required field.');
					$canProceed = false;
				}

				## Basic custom field checking
				elseif(is_array($fields) && !empty($fields)){

					## Check for duplicate CF names
					if($canProceed){
						$name_list = array();

						foreach($fields as $position => $data){
							if(trim($data['element_name']) == '') 
								$data['element_name'] = $fields[$position]['element_name'] = Lang::createHandle($data['label'], NULL, '-', false, true, array('@^[\d-]+@i' => ''));

							if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
								$this->_errors[$position] = array('label' => __('Two custom fields have the same element name. All element names must be unique.'));
								$canProceed = false;
								break;						
							}		
							$name_list[] = $data['element_name'];
						}	
					}

					if($canProceed){

						
						$unique = array();
						
						foreach($fields as $position => $data){
							$required = NULL;

							$field = fieldManager::instance()->create($data['type']);
							$field->setFromPOST($data);

							if($field->mustBeUnique() && !in_array($field->get('type'), $unique)) $unique[] = $field->get('type');
							elseif($field->mustBeUnique() && in_array($field->get('type'), $unique)){
								## Warning. cannot have 2 of this field!
								$canProceed = false;
								$this->_errors[$position] = array('label' => __('There is already a field of type <code>%s</code>. There can only be one per section.', array($field->name())));
							}

							$errors = array();

							if(Field::__OK__ != $field->checkFields($errors, false, false) && !empty($errors)){
								$this->_errors[$position] = $errors;
								$canProceed = false;
								break;					
							}
						}
					}
				}

				if($canProceed){

					$meta['handle'] = Lang::createHandle($meta['name']);
					$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');

			        if(!SectionManager::instance()->edit($section_id, $meta)){
						$this->pageAlert(__('An unknown database occurred while attempting to create the section.'), Alert::ERROR);
					}

					else{

						## Delete missing CF's
						$id_list = array();
						if(is_array($fields) && !empty($fields)){
							foreach($fields as $position => $data){
								if(isset($data['id'])) $id_list[] = $data['id'];
							}
						}

						$missing_cfs = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '$section_id' AND `id` NOT IN ('".@implode("', '", $id_list)."')");

						if(is_array($missing_cfs) && !empty($missing_cfs)){
							foreach($missing_cfs as $id){
								fieldManager::instance()->delete($id);
							}
						}

						## Save each custom field
						if(is_array($fields) && !empty($fields)){				
							foreach($fields as $position => $data){

								$field = fieldManager::instance()->create($data['type']);
								$field->setFromPOST($data);
								$field->set('sortorder', (string)$position);
								$field->set('parent_section', $section_id);

								$bEdit = true;					
								if(!$field->get('id')) $bEdit = false;

								## Creation
								if($field->commit()){

									$field_id = $field->get('id');

									###
									# Delegate: FieldPostCreate
									# Delegate: FieldPostEdit
									# Description: After creation/editing of an Field. New Field object is provided.
									ExtensionManager::instance()->notifyMembers(($bEdit ? 'FieldPostEdit' : 'FieldPostCreate'), '/blueprints/sections/', array('field' => &$field, 'data' => &$data));

								}
							}
						}

						## TODO: Fix Me
						###
						# Delegate: Edit
						# Description: After editing a Section. The ID is provided.
						#ExtensionManager::instance()->notifyMembers('Edit', getCurrentPage(), array('section_id' => $section_id));

		                redirect(ADMIN_URL . "/blueprints/sections/edit/$section_id/saved/");							

			        }       
			    }
			}

			if(@array_key_exists("delete", $_POST['action'])){
				$section_id = $this->_context[1];
				SectionManager::instance()->delete($section_id);
				redirect(ADMIN_URL . '/blueprints/sections/');
			}
	
		}
*/

	}
