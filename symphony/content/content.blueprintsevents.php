<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	//require_once(TOOLKIT . '/class.sectionmanager.php');

	Class contentBlueprintsEvents extends AdministrationPage{

		public function __viewIndex() {
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Events'))));

			$this->appendSubheading(__('Events') . $heading, Widget::Anchor(
				__('Create New'), Administration::instance()->getCurrentPageURL() . 'new/',
				__('Create a new event'), 'create button'
			));

			$eTableHead = array(
				array(__('Name'), 'col'),
				array(__('Source'), 'col'),
				array(__('Author'), 'col')
			);

			$eTableBody = array();

			$events = eventManager::instance()->listAll();

			if(!is_array($events) or empty($events)) {
				$eTableBody[] = Widget::TableRow(array(
					Widget::TableData(__('None found.'), 'inactive', null, count($eTableHead))
				));
			}

			else foreach($events as $event) {
				$instance = eventManager::instance()->create($event['handle']);
				//$section = SectionManager::instance()->fetch($instance->getSource());

				$view_mode = ($event['can_parse'] ? 'edit' : 'info');

				$col_name = Widget::TableData(Widget::Anchor(
					$event['name'], URL . '/symphony/blueprints/events/' . $view_mode . '/' . $event['handle'] . '/', 'event.' . $event['handle'] . '.php'
				));
				$col_name->appendChild(Widget::Input("items[{$event['handle']}]", null, 'checkbox'));

				// Source
				if(is_null($instance->getSource())){
					$col_source = Widget::TableData(__('None'), 'inactive');
				}
				else{
					$section = Section::loadFromHandle($instance->getSource());

					return Widget::TableData(Widget::Anchor(
						$section->name,
						URL . '/symphony/blueprints/sections/edit/' . $section->handle . '/',
						$section->handle
					));
				}

				if (isset($event['author']['website'])) {
					$col_author = Widget::TableData(Widget::Anchor(
						$event['author']['name'],
						General::validateURL($event['author']['website'])
					));
				}

				else if (isset($event['author']['email'])) {
					$col_author = Widget::TableData(Widget::Anchor(
						$event['author']['name'],
						'mailto:' . $event['author']['email']
					));
				}

				else {
					$col_author = Widget::TableData($event['author']['name']);
				}

				$eTableBody[] = Widget::TableRow(
					array($col_name, $col_source, $col_author)
				);
			}

			$table = Widget::Table(
				Widget::TableHead($eTableHead), null,
				Widget::TableBody($eTableBody), null
			);
			$table->setAttribute('id', 'events-list');

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(null, false, __('With Selected...')),
				array('delete', false, __('Delete'))
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		function __viewNew(){
			$this->__form();
		}

		function __viewEdit(){
			$this->__form();
		}

		function __viewInfo(){
			$this->__form(true);
		}

		function __form($readonly=false){

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			if(isset($this->_context[2])){
				switch($this->_context[2]){

					case 'saved':
						$this->pageAlert(
							__(
								'Event updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Events</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									URL . '/symphony/blueprints/events/new/',
									URL . '/symphony/blueprints/events/'
								)
							),
							Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__(
								'Event created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Events</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									URL . '/symphony/blueprints/events/new/',
									URL . '/symphony/blueprints/events/'
								)
							),
							Alert::SUCCESS);
						break;

				}
			}

			$isEditing = ($readonly ? true : false);
			$fields = array();


			if($this->_context[0] == 'edit' || $this->_context[0] == 'info'){
				$isEditing = true;

				$handle = $this->_context[1];

				$existing =& EventManager::instance()->create($handle);

				$about = $existing->about();

				$fields['name'] = $about['name'];
				$fields['source'] = $existing->getSource();
				$fields['filters'] = $existing->eParamFILTERS;
				$fields['output_id_on_save'] = ($existing->eParamOUTPUT_ID_ON_SAVE === true ? 'yes' : 'no');

				if(isset($existing->eParamOVERRIDES) && !empty($existing->eParamOVERRIDES)){
					$fields['overrides'] = array(
						'field' => array(),
						'replacement' => array()
					);

					foreach($existing->eParamOVERRIDES as $field_name => $replacement_value){
						$fields['overrides']['field'][] = $field_name;
						$fields['overrides']['replacement'][] = $replacement_value;
					}

				}

				if(isset($existing->eParamDEFAULTS) && !empty($existing->eParamDEFAULTS)){
					$fields['defaults'] = array(
						'field' => array(),
						'replacement' => array()
					);

					foreach($existing->eParamDEFAULTS as $field_name => $replacement_value){
						$fields['defaults']['field'][] = $field_name;
						$fields['defaults']['replacement'][] = $replacement_value;
					}

				}

			}

			if(isset($_POST['fields'])) $fields = $_POST['fields'];

			$this->setPageType('form');
			$this->setTitle(__(($isEditing ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'), array(__('Symphony'), __('Events'), $about['name'])));
			$this->appendSubheading(($isEditing ? $about['name'] : __('Untitled')));

			if(!$readonly):

				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings');
				$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

				$div = new XMLElement('div');
				$div->setAttribute('class', 'group');

				$label = Widget::Label(__('Name'));
				$label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));

				if(isset($this->_errors['name'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
				else $div->appendChild($label);

				$label = Widget::Label(__('Source'));

			    $options = array();

				foreach (new SectionIterator as $section) {
					$options[] = array($section->handle, ($fields['source'] == $section->handle), $section->name);
				}

				$label->appendChild(Widget::Select('fields[source]', $options, array('id' => 'event-context-selector')));
				$div->appendChild($label);

				$fieldset->appendChild($div);

				$this->Form->appendChild($fieldset);

				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings');
				$fieldset->appendChild(new XMLElement('legend', __('Processing Options')));

				$label = Widget::Label(__('Filter Rules'));

				if(!is_array($fields['filters'])) $fields['filters'] = array($fields['filters']);

				$options = array(
					array('admin-only', in_array('admin-only', $fields['filters']), __('Admin Only')),
					array('send-email', in_array('send-email', $fields['filters']), __('Send Email')),
					array('expect-multiple', in_array('expect-multiple', $fields['filters']), __('Allow Multiple')),
				);

				###
				# Delegate: AppendEventFilter
				# Description: Allows adding of new filter rules to the Event filter rule select box. A reference to the $options array is provided, and selected filters
				ExtensionManager::instance()->notifyMembers('AppendEventFilter', '/blueprints/events/' . $this->_context[0] . '/', array('selected' => $fields['filters'], 'options' => &$options));

				$label->appendChild(Widget::Select('fields[filters][]', $options, array('multiple' => 'multiple')));
				$fieldset->appendChild($label);

				$label = Widget::Label();
				$input = Widget::Input('fields[output_id_on_save]', 'yes', 'checkbox');
				if(isset($fields['output_id_on_save']) && $fields['output_id_on_save'] == 'yes'){
					$input->setAttribute('checked', 'checked');
				}

				$label->setValue(__('%s Add entry ID to the parameter pool in the format of <code>$event-name-id</code> when saving is successful.', array($input->generate())));
				$fieldset->appendChild($label);

				$this->Form->appendChild($fieldset);
			endif;


			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Overrides &amp; Defaults')));
			$fieldset->appendChild(new XMLElement('p', __('{$param}'), array('class' => 'help')));

			$div = new XMLElement('div');

			if(is_array($sections) && !empty($sections)){
				foreach($sections as $s){
					$div->appendChild(
						$this->__buildDefaultsAndOverridesDuplicator(
							$s,
							($fields['source'] == $s->get('id')
								? array('overrides' => $fields['overrides'], 'defaults' => $fields['defaults'])
								: NULL
							)
						)
					);
				}
			}

			$fieldset->appendChild($div);
			$this->Form->appendChild($fieldset);

			if($isEditing):
				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings');

				$doc = $existing->documentation();
				$fieldset->setValue('<legend>' . __('Description') . '</legend>' . self::CRLF . General::tabsToSpaces((is_object($doc) ? $doc->generate(true) : $doc), 2));

				$this->Form->appendChild($fieldset);
			endif;


			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($isEditing ? __('Save Changes') : __('Create Event')), 'submit', array('accesskey' => 's')));

			if($isEditing){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this event'), 'type' => 'submit'));
				$div->appendChild($button);
			}

			if(!$readonly) $this->Form->appendChild($div);

		}

		private function __buildDefaultsAndOverridesDuplicator(Section $section, array $items=NULL){

			$fields = Symphony::Database()->fetch("SELECT `element_name`, `label` FROM `tbl_fields` WHERE `parent_section` = " . $section->get('id'));

			$duplicator = new XMLElement('div', NULL, array('id' => 'event-context-' . $section->get('id')));
			$h3 = new XMLElement('h3', __('Fields'));
			$h3->setAttribute('class', 'label');
			$duplicator->appendChild($h3);

			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'events-duplicator');

			$options = array(
				array('', false, __('None')),
			);


			if(is_array($items['overrides'])){

				$field_names = $items['overrides']['field'];
				$replacement_values = $items['overrides']['replacement'];

				for($ii = 0; $ii < count($field_names); $ii++){

					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('h4', __('Override')));

					$group = new XMLElement('div');
					$group->setAttribute('class', 'group');

					$label = Widget::Label(__('Element Name'));
					$options = array(array('system:id', false, 'System ID'));

					foreach($fields as $f){
						$options[] = array(General::sanitize($f['element_name']), $f['element_name'] == $field_names[$ii], General::sanitize($f['label']));
					}

					$label->appendChild(Widget::Select('fields[overrides][field][]', $options));
					$group->appendChild($label);

					$label = Widget::Label(__('Replacement'));
					$label->appendChild(Widget::Input('fields[overrides][replacement][]', General::sanitize($replacement_values[$ii])));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);

				}
			}

			if(is_array($items['defaults'])){

				$field_names = $items['defaults']['field'];
				$replacement_values = $items['defaults']['replacement'];

				for($ii = 0; $ii < count($field_names); $ii++){

					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('h4', __('Default Value')));

					$group = new XMLElement('div');
					$group->setAttribute('class', 'group');

					$label = Widget::Label(__('Element Name'));
					$options = array(array('system:id', false, 'System ID'));

					foreach($fields as $f){
						$options[] = array(General::sanitize($f['element_name']), $f['element_name'] == $field_names[$ii], General::sanitize($f['label']));
					}

					$label->appendChild(Widget::Select('fields[defaults][field][]', $options));
					$group->appendChild($label);


					$label = Widget::Label(__('Replacement'));
					$label->appendChild(Widget::Input('fields[defaults][replacement][]', General::sanitize($replacement_values[$ii])));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);

				}
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->appendChild(new XMLElement('h4', __('Override')));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$label = Widget::Label(__('Element Name'));
			$options = array(array('system:id', false, 'System ID'));

			foreach($fields as $f){
				$options[] = array(General::sanitize($f['element_name']), false, General::sanitize($f['label']));
			}

			$label->appendChild(Widget::Select('fields[overrides][field][]', $options));
			$group->appendChild($label);

			$label = Widget::Label(__('Replacement'));
			$label->appendChild(Widget::Input('fields[overrides][replacement][]'));
			$group->appendChild($label);

			$li->appendChild($group);
			$ol->appendChild($li);


			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->appendChild(new XMLElement('h4', __('Default Value')));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$label = Widget::Label(__('Element Name'));
			$options = array(array('system:id', false, 'System ID'));

			foreach($fields as $f){
				$options[] = array(General::sanitize($f['element_name']), false, General::sanitize($f['label']));
			}

			$label->appendChild(Widget::Select('fields[defaults][field][]', $options));
			$group->appendChild($label);

			$label = Widget::Label(__('Replacement'));
			$label->appendChild(Widget::Input('fields[defaults][replacement][]'));
			$group->appendChild($label);

			$li->appendChild($group);
			$ol->appendChild($li);

			$duplicator->appendChild($ol);

			return $duplicator;
		}

		function __actionNew(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
		}

		function __actionEdit(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
			elseif(array_key_exists('delete', $_POST['action'])){

				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting the event file. Target file path is provided.
				#ExtensionManager::instance()->notifyMembers('Delete', getCurrentPage(), array("file" => EVENTS . "/event." . $_REQUEST['file'] . ".php"));

		    	if(!General::deleteFile(EVENTS . '/event.' . $this->_context[1] . '.php'))
					$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($this->_context[1])), Alert::ERROR);

		    	else redirect(URL . '/symphony/blueprints/components/');

			}
		}

		function __formAction(){
			$fields = $_POST['fields'];

			$this->_errors = array();

			if(trim($fields['name']) == '') $this->_errors['name'] = __('This is a required field');

			$classname = Lang::createHandle($fields['name'], '_', false, true, array('@^[^a-z]+@i' => '', '/[^\w-\.]/i' => ''));
			$rootelement = str_replace('_', '-', $classname);

			$file = EVENTS . '/event.' . $classname . '.php';

			$isDuplicate = false;
			$queueForDeletion = NULL;

			if($this->_context[0] == 'new' && @is_file($file)) $isDuplicate = true;
			elseif($this->_context[0] == 'edit'){
				$existing_handle = $this->_context[1];
				if($classname != $existing_handle && @is_file($file)) $isDuplicate = true;
				elseif($classname != $existing_handle) $queueForDeletion = EVENTS . '/event.' . $existing_handle . '.php';
			}

			##Duplicate
			if($isDuplicate) $this->_errors['name'] = __('An Event with the name <code>%s</code> name already exists', array($classname));

			if(empty($this->_errors)){

				$multiple = @in_array('expect-multiple', $fields['filters']);

				$eventShell = file_get_contents(TEMPLATES . '/event.tpl');

				$about = array(
					'name' => $fields['name'],
					'version' => '1.0',
					'release date' => DateTimeObj::getGMT('c'),
					'author name' => Administration::instance()->User->getFullName(),
					'author website' => URL,
					'author email' => Administration::instance()->User->email,
					'trigger condition' => $rootelement
				);

				$source = $fields['source'];

				$filter = NULL;
				$elements = NULL;

				$eventShell = self::__injectAboutInformation($eventShell, $about);

				if(isset($fields['filters']) && is_array($fields['filters']) && !empty($fields['filters'])){
					$eventShell = self::__injectArrayValues($eventShell, 'FILTERS', $fields['filters']);
				}

				$eventShell = self::__injectOverridesAndDefaults(
					$eventShell,
					(isset($fields['overrides']) && is_array($fields['overrides']) && !empty($fields['overrides']) ? $fields['overrides'] : NULL),
					(isset($fields['defaults']) && is_array($fields['defaults']) && !empty($fields['defaults']) ? $fields['defaults'] : NULL)
				);

				$documentation = NULL;
				$documentation_parts = array();

				$documentation_parts[] = new XMLElement('h3', __('Success and Failure XML Examples'));
				$documentation_parts[] = new XMLElement('p', __('When saved successfully, the following XML will be returned:'));

				if($multiple){
					$code = new XMLElement($rootelement);
					$entry = new XMLElement('entry', NULL, array('index' => '0', 'result' => 'success' , 'type' => 'create | edit'));
					$entry->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));

					$code->appendChild($entry);
				}

				else{
					$code = new XMLElement($rootelement, NULL, array('result' => 'success' , 'type' => 'create | edit'));
					$code->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));
				}


				$documentation_parts[] = self::processDocumentationCode($code);

				###


				$documentation_parts[] = new XMLElement('p', __('When an error occurs during saving, due to either missing or invalid fields, the following XML will be returned') . ($multiple ? __(' (<b>Notice that it is possible to get mixtures of success and failure messages when using the "Allow Multiple" option</b>)') : NULL) . ':');

				if($multiple){
					$code = new XMLElement($rootelement);

					$entry = new XMLElement('entry', NULL, array('index' => '0', 'result' => 'error'));
					$entry->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
					$entry->appendChild(new XMLElement('field-name', NULL, array('type' => 'invalid | missing')));
					$code->appendChild($entry);

					$entry = new XMLElement('entry', NULL, array('index' => '1', 'result' => 'success' , 'type' => 'create | edit'));
					$entry->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));
					$code->appendChild($entry);
				}

				else{
					$code = new XMLElement($rootelement, NULL, array('result' => 'error'));
					$code->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
					$code->appendChild(new XMLElement('field-name', NULL, array('type' => 'invalid | missing')));
				}


				$code->setValue('...', false);
				$documentation_parts[] = self::processDocumentationCode($code);

				###


				if(is_array($fields['filters']) && !empty($fields['filters'])){
					$documentation_parts[] = new XMLElement('p', __('The following is an example of what is returned if any filters fail:'));

					$code = new XMLElement($rootelement, NULL, array('result' => 'error'));
					$code->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
					$code->appendChild(new XMLElement('filter', NULL, array('name' => 'admin-only', 'status' => 'failed')));
					$code->appendChild(new XMLElement('filter', __('Recipient username was invalid'), array('name' => 'send-email', 'status' => 'failed')));
					$code->setValue('...', false);
					$documentation_parts[] = self::processDocumentationCode($code);
				}

				###

				$documentation_parts[] = new XMLElement('h3', __('Example Front-end Form Markup'));

				$documentation_parts[] = new XMLElement('p', __('This is an example of the form markup you can use on your frontend:'));
				$container = new XMLElement('form', NULL, array('method' => 'post', 'action' => '', 'enctype' => 'multipart/form-data'));
				$container->appendChild(Widget::Input('MAX_FILE_SIZE', Symphony::Configuration()->get('max_upload_size', 'admin'), 'hidden'));

				$section = SectionManager::instance()->fetch($fields['source']);
				$markup = NULL;
				foreach($section->fetchFields() as $f){
					if ($f->getExampleFormMarkup() instanceof XMLElement)
						$container->appendChild($f->getExampleFormMarkup());
				}
				$container->appendChild(Widget::Input('action['.$rootelement.']', __('Submit'), 'submit'));

				$code = $container->generate(true);

				$documentation_parts[] = self::processDocumentationCode(($multiple ? str_replace('fields[', 'fields[0][', $code) : $code));


				$documentation_parts[] = new XMLElement('p', __('To edit an existing entry, include the entry ID value of the entry in the form. This is best as a hidden field like so:'));
				$documentation_parts[] = self::processDocumentationCode(Widget::Input('id' . ($multiple ? '[0]' : NULL), 23, 'hidden'));


				$documentation_parts[] = new XMLElement('p', __('To redirect to a different location upon a successful save, include the redirect location in the form. This is best as a hidden field like so, where the value is the URL to redirect to:'));
				$documentation_parts[] = self::processDocumentationCode(Widget::Input('redirect', URL.'/success/', 'hidden'));

				if(@in_array('send-email', $fields['filters'])){

					$documentation_parts[] = new XMLElement('h3', __('Send Email Filter'));

					$documentation_parts[] = new XMLElement('p', __('The send email filter, upon the event successfully saving the entry, takes input from the form and send an email to the desired recipient. <b>This filter currently does not work with the "Allow Multiple" option.</b> The following are the recognised fields:'));

					$documentation_parts[] = self::processDocumentationCode(
						'send-email[sender-email] // '.__('Optional').self::CRLF.
						'send-email[sender-name] // '.__('Optional').self::CRLF.
						'send-email[subject] // '.__('Optional').self::CRLF.
						'send-email[body]'.self::CRLF.
						'send-email[recipient] // '.__('list of comma separated usernames.'));

					$documentation_parts[] = new XMLElement('p', __('All of these fields can be set dynamically using the exact field name of another field in the form as shown below in the example form:'));

			        $documentation_parts[] = self::processDocumentationCode('<form action="" method="post">
	<fieldset>
		<label>'.__('Name').' <input type="text" name="fields[author]" value="" /></label>
		<label>'.__('Email').' <input type="text" name="fields[email]" value="" /></label>
		<label>'.__('Message').' <textarea name="fields[message]" rows="5" cols="21"></textarea></label>
		<input name="send-email[sender-email]" value="fields[email]" type="hidden" />
		<input name="send-email[sender-name]" value="fields[author]" type="hidden" />
		<input name="send-email[subject]" value="You are being contacted" type="hidden" />
		<input name="send-email[body]" value="fields[message]" type="hidden" />
		<input name="send-email[recipient]" value="fred" type="hidden" />
		<input id="submit" type="submit" name="action[save-contact-form]" value="Send" />
	</fieldset>
</form>');

				}

				###
				# Delegate: AppendEventFilterDocumentation
				# Description: Allows adding documentation for new filters. A reference to the $documentation array is provided, along with selected filters
				ExtensionManager::instance()->notifyMembers(
					'AppendEventFilterDocumentation',
					'/blueprints/events/' . $this->_context[0] . '/',
					array('selected' => $fields['filters'], 'documentation' => &$documentation_parts)
				);

				$documentation = join(self::CRLF, array_map(create_function('$x', 'return rtrim($x->generate(true, 4));'), $documentation_parts));
				$documentation = str_replace('\'', '\\\'', $documentation);

				$pattern = array(
					'<!-- CLASS NAME -->',
					'<!-- SOURCE -->',
					'<!-- DOCUMENTATION -->',
					'<!-- ROOT ELEMENT -->',
					'<!-- OUTPUT ID ON SAVE -->'
				);

				$replacements = array(
					$classname,
					$source,
					General::tabsToSpaces($documentation, 2),
					$rootelement,
					(isset($fields['output_id_on_save']) && $fields['output_id_on_save'] == 'yes' ? 'true' : 'false')
				);

				$eventShell = str_replace($pattern, $replacements, $eventShell);


				## Remove left over placeholders
				$eventShell = preg_replace(array('/<!--[\w ]++-->/'), '', $eventShell);
				header('Content-Type: text/plain');

				##Write the file
				if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $eventShell, Symphony::Configuration()->get('write_mode', 'file')))
					$this->pageAlert(__('Failed to write Event to <code>%s</code>. Please check permissions.', array(EVENTS)), Alert::ERROR);

				##Write Successful, add record to the database
				else{

					// TODO: Remove Events from Views

					/*
					if($queueForDeletion){
						General::deleteFile($queueForDeletion);

						$sql = "SELECT * FROM `tbl_pages` WHERE `events` REGEXP '[[:<:]]".$existing_handle."[[:>:]]' ";
						$resul = Symphony::Database()->query($sql);

						if(is_array($pages) && !empty($pages)){
							foreach($pages as $page){

								$page['events'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['events']);

								Symphony::Database()->update('tbl_pages', $page, array($page['id']), "`id` = '".$page['id']."'");
							}
						}

					}
					*/

					### TODO: Fix me
					###
					# Delegate: Create
					# Description: After saving the event, the file path is provided and an array
					#              of variables set by the editor
					#ExtensionManager::instance()->notifyMembers('Create', getCurrentPage(), array('file' => $file, 'defines' => $defines, 'var' => $var));

	                redirect(URL . '/symphony/blueprints/events/edit/'.$classname.'/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');

				}
			}
		}

		public static function processDocumentationCode($code){
			return new XMLElement('pre', '<code>' . str_replace('<', '&lt;', str_replace('&', '&amp;', trim((is_object($code) ? $code->generate(true) : $code)))) . '</code>', array('class' => 'XML'));
		}


		private static function __injectArrayValues($shell, $variable, array $elements){
			return str_replace('<!-- '.strtoupper($variable).' -->',  "'" . implode("'," . self::CRLF . "\t\t\t'", $elements) . "'", $shell);
		}

		private static function __injectOverridesAndDefaults($shell, array $overrides=NULL, array $defaults=NULL){

			/*
			Array
			(
			    [field] => Array
			        (
			            [0] => id
			        )

			    [replacement] => Array
			        (
			            [0] => 43
			        )

			)
			Array
			(
			    [field] => Array
			        (
			            [0] => title
			            [1] => published
			        )

			    [replacement] => Array
			        (
			            [0] => I am {$title}
			            [1] => no
			        )

			)
			*/

			if(!is_null($overrides)){
				$values = array();
				foreach($overrides['field'] as $index => $handle){
					if(strlen(trim($handle)) == 0) continue;
					$values[] = sprintf("%s' => '%s", addslashes($handle), addslashes($overrides['replacement'][$index]));
				}

				$shell = self::__injectArrayValues($shell, 'OVERRIDES', $values);
			}

			if(!is_null($defaults)){
				$values = array();
				foreach($defaults['field'] as $index => $handle){
					if(strlen(trim($handle)) == 0) continue;
					$values[] = sprintf("%s' => '%s", addslashes($handle), addslashes($defaults['replacement'][$index]));
				}

				$shell = self::__injectArrayValues($shell, 'DEFAULTS', $values);
			}

			return $shell;
		}

		private static function __injectAboutInformation($shell, array $details){
			foreach($details as $key => $val){
				$shell = str_replace('<!-- ' . strtoupper($key) . ' -->', addslashes($val), $shell);
			}

			return $shell;
		}

		protected function __actionDelete($events, $redirect) {
			$success = true;

			if(!is_array($events)) $events = array($events);

			foreach ($events as $event) {
				if(!General::deleteFile(EVENTS . '/event.' . $event . '.php'))
					$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($this->_context[1])), Alert::ERROR);
			}

			if($success) redirect($redirect);
		}

		public function __actionIndex() {
			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						$this->__actionDelete($checked, URL . '/symphony/blueprints/events/');
						break;
				}
			}
		}


	}

