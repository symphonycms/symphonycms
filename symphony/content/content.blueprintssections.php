<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.messagestack.php');
 	require_once(TOOLKIT . '/class.section.php');
	//require_once(TOOLKIT . '/class.entrymanager.php');

	Class contentBlueprintsSections extends AdministrationPage{

		private $errors;
		private $section;

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
			else {
				foreach ($sections as $s) {
					$entry_count = 0;
					$result = Symphony::Database()->query(
						"
							SELECT
								count(*) AS `count`
							FROM
								`tbl_entries` AS e
							WHERE
								e.section = '%s'
						",
						array($s->handle)
					);
					
					if ($result->valid()) {
						$entry_count = (integer)$result->current()->count;
					}
					
					// Setup each cell
					$td1 = Widget::TableData(Widget::Anchor($s->name, Administration::instance()->getCurrentPageURL() . "edit/{$s->handle}/", NULL, 'content'));
					$td2 = Widget::TableData(Widget::Anchor((string)$entry_count, ADMIN_URL . "/publish/{$s->handle}/"));
					$td3 = Widget::TableData($s->{'navigation-group'});
					
					$td3->appendChild(Widget::Input("items[{$s->handle}]", 'on', 'checkbox'));
					
					// Add a row to the body array, assigning each cell to the row
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

		private function __save(array $essentials, array $fieldsets=NULL, Section $section=NULL){
			var_dump($section);exit;
			
			
			if(is_null($section)){
				$section = new Section;
				$section->path = SECTIONS;
			}

			$this->section = $section;

			$this->section->name = $essentials['name'];
			$this->section->{'navigation-group'} = $essentials['navigation-group'];
			$this->section->{'hidden-from-publish-menu'} = (isset($essentials['hidden-from-publish-menu']) && $essentials['hidden-from-publish-menu'] == 'yes' ? 'yes' : 'no');

			/*
			Array
			(
			    [0] => Array
			        (
			            [type] => checkbox
			            [label] => Test Checkbox
			            [location] => sidebar
			            [description] => Please Check Me!!
			            [default_state] => on
			            [show_column] => yes
			        )

			    [1] => Array
			        (
			            [type] => input
			            [label] => I am the name
			            [location] => main
			            [validator] => /^[^\s:\/?#]+:(?:\/{2,3})?[^\s.\/?#]+(?:\.[^\s.\/?#]+)*(?:\/[^\s?#]*\??[^\s?#]*(#[^\s#]*)?)?$/
			            [required] => yes
			            [show_column] => yes
			        )

			)
			*/


			try{

				$this->errors = new MessageStack;

				$this->section->removeAllFields();
/*
[0] => Array
    (
        [label] => Essentials
        [rows] => Array
            (
                [0] => Array
                    (
                        [fields] => Array
                            (
                                [1] => Array
                                    (
                                        [label] => Date
                                        [width] => 1
                                        [pre-populate] => yes
                                        [show_column] => no
                                    )

                                [0] => Array
                                    (
                                        [label] => Title
                                        [width] => 2
                                        [validator] =>
                                        [required] => no
                                        [show_column] => no
                                    )

                            )

                    )

                [1] => Array
                    (
                        [fields] => Array
                            (
                                [0] => Array
                                    (
                                        [label] => Body
                                        [width] => 1
                                        [formatter] => markdown_with_purifier
                                        [size] => 15
                                        [show_column] => no
                                        [required] => no
                                    )

                            )

                    )

            )

    )

*/
				if(!is_null($fieldsets) && !empty($fieldsets)) {

					$doc = new DOMDocument('1.0', 'utf-8');
					$doc->formatOutput = true;

					$layout = $doc->createElement('layout');
					$doc->appendChild($layout);
					
					if (is_array($fieldsets)) foreach($fieldsets as $f){
						
						$fieldset = $doc->createElement('fieldset');
						$fieldset->appendChild($doc->createElement('label', General::sanitize($f['label'])));
						
						if (is_array($f['rows'])) foreach($f['rows'] as $r){
							
							if(!isset($r['fields']) || empty($r['fields'])) continue;

							$row = $doc->createElement('row');

							$fields = $doc->createElement('fields');

							ksort($r['fields']);

							foreach($r['fields'] as $index => $f){
								$obj = $this->section->appendField($f['type'], $f);
								$fields->appendChild($doc->createElement('item', General::sanitize($obj->get('element_name'))));
							}

							$row->appendChild($fields);
							$fieldset->appendChild($row);
						}

						$layout->appendChild($fieldset);
					}
				}

				Section::save($this->section, $this->errors, array($doc));
				return true;
			}
			catch(SectionException $e){
				switch($e->getCode()){
					case Section::ERROR_MISSING_OR_INVALID_FIELDS:
						$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
						break;

					case Section::ERROR_FAILED_TO_WRITE:
						$this->pageAlert($e->getMessage(), Alert::ERROR);
						break;
				}
			}
			catch(Exception $e){
				// Errors!!
				// Not sure what happened!!
				$this->pageAlert(__('An unknown error has occurred. %s', array($e->getMessage())), Alert::ERROR);
			}

			return false;
		}

		public function __actionNew(){
			if(isset($_POST['action']['save'])){
				if($this->__save($_POST['essentials'], (isset($_POST['fieldset']) ? $_POST['fieldset'] : NULL)) == true){
					redirect(ADMIN_URL . "/blueprints/sections/edit/{$this->section->handle}/:created/");
				}
			}
		}

		public function __actionEdit(){
			if(isset($_POST['action']['save'])){
				if($this->__save($_POST['essentials'], (isset($_POST['fieldset']) ? $_POST['fieldset'] : NULL), Section::load(SECTIONS . '/' . $this->_context[1] . '.xml')) == true){
					redirect(ADMIN_URL . "/blueprints/sections/edit/{$this->section->handle}/:saved/");
				}
			}
		}

		private static function __loadExistingSection($handle){
			try{
				return Section::load(SECTIONS . "/{$handle}.xml");
			}
			catch(SectionException $e){

				switch($e->getCode()){
					case Section::ERROR_SECTION_NOT_FOUND:
						throw new SymphonyErrorPage(
							__('The section you requested to edit does not exist.'),
							__('Section not found'), NULL,
							array('HTTP/1.0 404 Not Found')
						);
						break;

					default:
					case Section::ERROR_FAILED_TO_LOAD:
						throw new SymphonyErrorPage(
							__('The section you requested could not be loaded. Please check it is readable.'),
							__('Failed to load section')
						);
						break;
				}
			}
			catch(Exception $e){
				throw new SymphonyErrorPage(
					sprintf(__("An unknown error has occurred. %s"), $e->getMessage()),
					__('Unknown Error'), NULL,
					array('HTTP/1.0 500 Internal Server Error')
				);
			}
		}

		public function __viewNew(){
			if(!($this->section instanceof Section)){
				$this->section = new Section;
			}
			$this->__form();
		}

		public function __viewEdit(){
			$existing = self::__loadExistingSection($this->_context[1]);
			if(!($this->section instanceof Section)){
				$this->section = $existing;
			}
			$this->__form($existing);
		}

		private function __form(Section $existing=NULL){

			// Status message:
			$callback = Administration::instance()->getPageCallback();
			if(isset($callback['flag']) && !is_null($callback['flag'])){

				switch($callback['flag']){

					case 'saved':

						$this->pageAlert(
							__(
								'Section updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Sections</a>',
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
								'Section created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Sections</a>',
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
			$fieldset->appendChild(new XMLElement('h3', __('Essentials')));

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);

			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('essentials[name]', $this->section->name));

			$namediv->appendChild((
				isset($this->errors->name)
					? Widget::wrapFormElementWithError($label, $this->errors->name)
					: $label
			));

			$label = Widget::Label();
			$input = Widget::Input('essentials[hidden-from-publish-menu]', 'yes', 'checkbox', ($this->section->{'hidden-from-publish-menu'} == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Hide this section from the Publish menu', array($input->generate(false))));
			$namediv->appendChild($label);
			$div->appendChild($namediv);

			$navgroupdiv = new XMLElement('div', NULL);

			$label = Widget::Label('Navigation Group <i>Created if does not exist</i>');
			$label->appendChild(Widget::Input('essentials[navigation-group]', $this->section->{"navigation-group"}));

			$navgroupdiv->appendChild((
				isset($this->errors->{'navigation-group'})
					? Widget::wrapFormElementWithError($label, $this->errors->{'navigation-group'})
					: $label
			));

			$navigation_groups = Section::fetchUsedNavigationGroups();
			if(is_array($navigation_groups) && !empty($navigation_groups)){
				$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
				foreach($navigation_groups as $g){
					$ul->appendChild(new XMLElement('li', $g));
				}
				$navgroupdiv->appendChild($ul);
			}

			$div->appendChild($navgroupdiv);

			$fieldset->appendChild($div);
			$this->Form->appendChild($fieldset);

			// Fields


			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('h3', __('Fields')));

			$layout = new XMLElement('div');
			$layout->setAttribute('class', 'layout');

			$templates = new XMLElement('ol');
			$templates->setAttribute('class', 'templates');

			/*
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Fields'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);

			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'section-' . $section_id);
			$ol->setAttribute('class', 'section-duplicator');

			$fields = $this->section->fields;

			if(is_array($fields) && !empty($fields)){
				foreach($fields as $position => $field){

					$wrapper = new XMLElement('li');

					$field->set('sortorder', $position);
					$field->displaySettingsPanel($wrapper, (isset($this->errors->{"field::{$position}"}) ? $this->errors->{"field::{$position}"} : NULL));
					$ol->appendChild($wrapper);

				}
			}
			*/



			$types = array();
			foreach (FieldManager::instance()->fetchTypes() as $type){
				if ($field = FieldManager::instance()->create($type)){
					$types[$type] = $field;
				}
			}

			// To Do: Sort this list based on how many times a field has been used across the system
			uasort($types, create_function('$a, $b', 'return strnatcasecmp($a->name(), $b->name());'));


			foreach ($types as $type => $field){
				$defaults = array();

				$field->findDefaults($defaults);
				$field->setArray($defaults);

				$wrapper = new XMLElement('li');
				$field->displaySettingsPanel($wrapper);
				$wrapper->appendChild(Widget::Input('type', General::sanitize($type), 'hidden'));

				$templates->appendChild($wrapper);
			}


			$layout->appendChild($templates);


			// Existing Fields
			// 			var_dump($existing->layout->fieldsets); die();

			// Organise the fields into an array indexed by the element name
			$fields = array();

			if(is_array($this->section->fields) && count($this->section->fields) > 0){
				foreach($this->section->fields as $position => $field){

					$fields[$field->get('element_name')] = array('position' => $position, 'field' => $field);

					//$wrapper = new XMLElement('li');

				//	$field->set('sortorder', $position);
				//	$field->displaySettingsPanel($wrapper, (isset($this->errors->{"field::{$position}"}) ? $this->errors->{"field::{$position}"} : NULL));
				//	$ol->appendChild($wrapper);

				}
			}


			$content = new XMLElement('div');
			$content->setAttribute('class', 'content');

			if(!isset($this->section->layout) || !isset($this->section->layout->fieldsets) || empty($this->section->layout->fieldsets)){

				$row = new XMLElement('div');
				$h3 = new XMLElement('h3');
				$h3->appendChild(Widget::Input('label', 'Default Fieldset'));
				$row->appendChild($h3);

				if(is_array($fields) && !empty($fields)){
					foreach($fields as $element_name => $data){

						$ol = new XMLElement('ol');

						$field = $data['field'];
						$position = $data['position'];

						$li = new XMLElement('li');

						$settings_div = new XMLElement('div');
						$settings_div->setAttribute('class', 'settings');

						$field->set('sortorder', $position);
						$field->displaySettingsPanel($settings_div, (isset($this->errors->{"field::{$position}"}) ? $this->errors->{"field::{$position}"} : NULL));
						$settings_div->appendChild(Widget::Input('type', General::sanitize($field->get('type')), 'hidden'));
						$li->appendChild($settings_div);
						$ol->appendChild($li);

						$row->appendChild($ol);
					}
				}

				else{
					$row->appendChild(new XMLElement('ol'));

				}

				$content->appendChild($row);

			}

			else{

				foreach($this->section->layout->fieldsets as $f){

					$group = new XMLElement('div');
					$h3 = new XMLElement('h3');
					$h3->appendChild(Widget::Input('label', $f->label));
					$group->appendChild($h3);

					foreach($f->rows as $r){
						if(isset($r) && !empty($r)){

							$ol = new XMLElement('ol');

							foreach($r as $element_name){

								if(!isset($fields[$element_name]['field']) || !($fields[$element_name]['field'] instanceof Field)) continue;

								$field = $fields[$element_name]['field'];
								$position = $fields[$element_name]['position'];

								$li = new XMLElement('li');

								$settings_div = new XMLElement('div');
								$settings_div->setAttribute('class', 'settings');

								$field->set('sortorder', $position);
								$field->displaySettingsPanel($settings_div, (isset($this->errors->{"field::{$position}"}) ? $this->errors->{"field::{$position}"} : NULL));
								$settings_div->appendChild(Widget::Input('type', General::sanitize($field->get('type')), 'hidden'));
								$li->appendChild($settings_div);
								$ol->appendChild($li);

							}

							$group->appendChild($ol);
						}
					}


					$content->appendChild($group);


				}
			}

			$layout->appendChild($content);

			$fieldset->appendChild($layout);

			$this->Form->appendChild($fieldset);

			/*
				<h3>Fields</h3>

				<div class="layout">
					<ol class="templates">
						<li>
							<h3>Checkbox</h3>

							<label class="field-label">
								Label
								<input name="label" value="" />
							</label>
							<label class="field-flex">
								Width
								<select name="width">
									<option value="1">Small</option>
									<option value="2" selected="selected">Medium</option>
									<option value="3">Large</option>
								</select>
							</label>
							<label>
								Alternate label
								<input name="alternate-label" value="" />
							</label>

							<ul class="options-list">
								<li>
									<label>
										<input type="checkbox" checked="checked" />
										Show column
									</label>
								</li>
							</ul>
						</li>
						<li>
							<h3>Taglist</h3>

							<label class="field-label">
								Label
								<input name="label" value="" />
							</label>
							<label class="field-flex">
								Width
								<select name="width">
									<option value="1">Small</option>
									<option value="2" selected="selected">Medium</option>
									<option value="3">Large</option>
								</select>
							</label>
							<label>
								Static Options
								<input name="static-options" value="" />
							</label>
							<label>
								Dynamic Options
								<select>
									<option>None</option>
								</select>
							</label>

							<ul class="options-list">
								<li>
									<label>
										<input type="checkbox" checked="checked" />
										Show column
									</label>
								</li>
							</ul>
						</li>
						<li>
							<h3>Textbox</h3>

							<label class="field-label">
								Label
								<input name="label" value="" />
							</label>

							<div class="group">
								<label class="field-flex">
									Width
									<select name="width">
										<option value="1">Small</option>
										<option value="2" selected="selected">Medium</option>
										<option value="3">Large</option>
									</select>
								</label>
								<label>
									Height
									<select name="height">
										<option>Single Line</option>
										<option>Small</option>
										<option selected="selected">Medium</option>
										<option>Large</option>
									</select>
								</label>
							</div>

							<label>
								Text Formatter
								<select name="text-formatter">
									<option>None</option>
									<option>HTML Normal</option>
									<option>HTML Pretty</option>
								</select>
							</label>

							<div class="group">
								<label>
									Limit (characters)
									<input name="limit" value="0" />
								</label>
								<label>
									Preview length
									<input name="preview-length" value="75" />
								</label>
							</div>

							<ul class="options-list">
								<li>
									<label>
										<input type="checkbox" />
										Output with handles
									</label>
								</li>
								<li>
									<label>
										<input type="checkbox" />
										Output as CDATA
									</label>
								</li>
								<li>
									<label>
										<input type="checkbox" />
										Make this a required field
									</label>
								</li>
								<li>
									<label>
										<input type="checkbox" checked="checked" />
										Show column
									</label>
								</li>
							</ul>
						</li>
						<li>
							<h3>Upload</h3>

							<label class="field-label">
								Label
								<input name="label" value="" />
							</label>
							<label class="field-flex">
								Width
								<select name="width">
									<option value="1">Small</option>
									<option value="2" selected="selected">Medium</option>
									<option value="3">Large</option>
								</select>
							</label>

							<ul class="options-list">
								<li>
									<label>
										<input type="checkbox" checked="checked" />
										Show column
									</label>
								</li>
							</ul>
						</li>
					</ol>

					<div class="content">
						<div>
							<h3><input value="One" /></h3>
							<ol></ol>
						</div>

					</div>
				</div>
*/


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

			$formHasErrors = (is_array($this->errors) && !empty($this->errors));

			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			@ksort($fields);

			$showEmptyTemplate = (is_array($fields) && !empty($fields) ? false : true);

			$meta['entry_order'] = (isset($meta['entry_order']) ? $meta['entry_order'] : 'date');
			$meta['subsection'] = (isset($meta['subsection']) ? 1 : 0);
			$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');
			$meta['navigation_group'] = (isset($meta['navigation_group']) ? $meta['navigation_group'] : 'Content');

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('h3', __('Essentials')));

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);

			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('meta[name]', $meta['name']));

			if(isset($this->errors['name'])) $namediv->appendChild(Widget::wrapFormElementWithError($label, $this->errors['name']));
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

			if(isset($this->errors['navigation_group'])) $navgroupdiv->appendChild(Widget::wrapFormElementWithError($label, $this->errors['navigation_group']));
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
			$fieldset->appendChild(new XMLElement('h3', __('Fields')));

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
						$input->displaySettingsPanel($wrapper, (isset($this->errors[$position]) ? $this->errors[$position] : NULL));
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

			$formHasErrors = (is_array($this->errors) && !empty($this->errors));
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
			$fieldset->appendChild(new XMLElement('h3', __('Essentials')));

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);

			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('meta[name]', $meta['name']));

			if(isset($this->errors['name'])) $namediv->appendChild(Widget::wrapFormElementWithError($label, $this->errors['name']));
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

			if(isset($this->errors['navigation_group'])) $navgroupdiv->appendChild(Widget::wrapFormElementWithError($label, $this->errors['navigation_group']));
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
			$fieldset->appendChild(new XMLElement('h3', __('Fields')));

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
					$field->displaySettingsPanel($wrapper, (isset($this->errors[$position]) ? $this->errors[$position] : NULL));
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

				$this->errors = array();

				## Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || strlen(trim($meta['name'])) == 0){
					$required = array('Name');
					$this->errors['name'] = __('This is a required field.');
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif(Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `name` = '" . $meta['name'] . "' LIMIT 1")){
					$this->errors['name'] = __('A Section with the name <code>%s</code> name already exists', array($meta['name']));
					$canProceed = false;
				}

				## Check to ensure all the required section fields are filled
				if(!isset($meta['navigation_group']) || strlen(trim($meta['navigation_group'])) == 0){
					$required = array('Navigation Group');
					$this->errors['navigation_group'] = __('This is a required field.');
					$canProceed = false;
				}

				## Basic custom field checking
				if(is_array($fields) && !empty($fields)){

					$name_list = array();

					foreach($fields as $position => $data){
						if(trim($data['element_name']) == '')
							$data['element_name'] = $fields[$position]['element_name'] = Lang::createHandle($data['label'], '-', false, true, array('@^[\d-]+@i' => ''));

						if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
							$this->errors[$position] = array('element_name' => __('Two custom fields have the same element name. All element names must be unique.'));
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
							$this->errors[$position] = array('label' => __('There is already a field of type <code>%s</code>. There can only be one per section.', array($field->name())));
						}

						$errors = array();

						if(Field::__OK__ != $field->checkFields($errors, false, false) && !empty($errors)){
							$this->errors[$position] = $errors;
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


				$this->errors = array();

				## Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || trim($meta['name']) == ''){
					$required = array('Name');
					$this->errors['name'] = __('This is a required field.');
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif($meta['name'] != $existing_section->get('name') && Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `name` = '" . $meta['name'] . "' AND `id` != {$section_id} LIMIT 1")){
					$this->errors['name'] = __('A Section with the name <code>%s</code> name already exists', array($meta['name']));
					$canProceed = false;
				}

				## Check to ensure all the required section fields are filled
				if(!isset($meta['navigation_group']) || strlen(trim($meta['navigation_group'])) == 0){
					$required = array('Navigation Group');
					$this->errors['navigation_group'] = __('This is a required field.');
					$canProceed = false;
				}

				## Basic custom field checking
				elseif(is_array($fields) && !empty($fields)){

					## Check for duplicate CF names
					if($canProceed){
						$name_list = array();

						foreach($fields as $position => $data){
							if(trim($data['element_name']) == '')
								$data['element_name'] = $fields[$position]['element_name'] = Lang::createHandle($data['label'], '-', false, true, array('@^[\d-]+@i' => ''));

							if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
								$this->errors[$position] = array('label' => __('Two custom fields have the same element name. All element names must be unique.'));
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
								$this->errors[$position] = array('label' => __('There is already a field of type <code>%s</code>. There can only be one per section.', array($field->name())));
							}

							$errors = array();

							if(Field::__OK__ != $field->checkFields($errors, false, false) && !empty($errors)){
								$this->errors[$position] = $errors;
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
