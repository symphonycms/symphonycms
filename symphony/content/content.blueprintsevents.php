<?php
	/**
	 * @package content
	 */

	/**
	 * The Event Editor allows a developer to create events that typically
	 * allow Frontend forms to populate Sections or edit Entries.
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');

	Class contentBlueprintsEvents extends AdministrationPage{

		public function __viewNew(){
			$this->__form();
		}

		public function __viewEdit(){
			$this->__form();
		}

		public function __viewInfo(){
			$this->__form(true);
		}

		public function __form($readonly=false){

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			if(isset($this->_context[2])){
				switch($this->_context[2]){

					case 'saved':
						$this->pageAlert(
							__(
								'Event updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Events</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . '/blueprints/events/new/',
									SYMPHONY_URL . '/blueprints/components/'
								)
							),
							Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__(
								'Event created at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Events</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . '/blueprints/events/new/',
									SYMPHONY_URL . '/blueprints/components/'
								)
							),
							Alert::SUCCESS);
						break;

				}
			}

			$isEditing = ($readonly ? true : false);
			$fields = array();

			$sectionManager = new SectionManager($this->_Parent);

			if($this->_context[0] == 'edit' || $this->_context[0] == 'info'){
				$isEditing = true;

				$handle = $this->_context[1];

				$EventManager = new EventManager($this->_Parent);
				$existing =& $EventManager->create($handle);

				$about = $existing->about();

				if ($this->_context[0] == 'edit' && !$existing->allowEditorToParse()) redirect(SYMPHONY_URL . '/blueprints/events/info/' . $handle . '/');

				$fields['name'] = $about['name'];
				$fields['source'] = $existing->getSource();
				$fields['filters'] = $existing->eParamFILTERS;
			}

			if(isset($_POST['fields'])) $fields = $_POST['fields'];

			$this->setPageType('form');
			$this->setTitle(__(($isEditing ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'), array(__('Symphony'), __('Events'), $about['name'])));
			$this->appendSubheading(($isEditing ? $about['name'] : __('Untitled')));

			if(!$readonly):
				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings');
				$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

				$group = new XMLElement('div');
				$group->setAttribute('class', 'group');

				$label = Widget::Label(__('Name'));
				$label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));

				$div = new XMLElement('div');
				if(isset($this->_errors['name'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
				else $div->appendChild($label);
				$group->appendChild($div);

				$label = Widget::Label(__('Source'));

				$sections = $sectionManager->fetch(NULL, 'ASC', 'name');

				$options = array();

				if(is_array($sections) && !empty($sections)){
					foreach($sections as $s) $options[] = array($s->get('id'), ($fields['source'] == $s->get('id')), General::sanitize($s->get('name')));
				}

				$label->appendChild(Widget::Select('fields[source]', $options, array('id' => 'context')));
				$div = new XMLElement('div');
				if(isset($this->_errors['source'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['source']));
				else $div->appendChild($label);
				$group->appendChild($div);

				$fieldset->appendChild($group);

				$label = Widget::Label(__('Filter Options'));

				$filters = is_array($fields['filters']) ? $fields['filters'] : array();
				$options = array(
					array('admin-only', in_array('admin-only', $filters), __('Admin Only')),
					array('send-email', in_array('send-email', $filters), __('Send Notification Email')),
					array('expect-multiple', in_array('expect-multiple', $filters), __('Allow Multiple')),
				);

				/**
				 * Allows adding of new filter rules to the Event filter rule select box
				 *
				 * @delegate AppendEventFilter
				 * @param string $context
				 * '/blueprints/events/(edit|new|info)/'
				 * @param array $selected
				 *  An array of all the selected filters for this Event
				 * @param array $options
				 *  An array of all the filters that are available, passed by reference
				 */
				Symphony::ExtensionManager()->notifyMembers('AppendEventFilter', '/blueprints/events/' . $this->_context[0] . '/', array('selected' => $filters, 'options' => &$options));

				$label->appendChild(Widget::Select('fields[filters][]', $options, array('multiple' => 'multiple')));

				$fieldset->appendChild($label);

				$this->Form->appendChild($fieldset);
			endif;

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
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this event'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this event?')));
				$div->appendChild($button);
			}

			if(!$readonly) $this->Form->appendChild($div);

		}

		public function __actionNew(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
		}

		public function __actionEdit(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
			elseif(array_key_exists('delete', $_POST['action'])){

				/**
				 * Prior to deleting the Event file. Target file path is provided.
				 *
				 * @delegate EventPreDelete
				 * @since Symphony 2.2
				 * @param string $context
				 * '/blueprints/events/'
				 * @param string $file
				 *  The path to the Event file
				 */
				Symphony::ExtensionManager()->notifyMembers('EventPreDelete', '/blueprints/events/', array('file' => EVENTS . "/event." . $this->_context[1] . ".php"));

				if(!General::deleteFile(EVENTS . '/event.' . $this->_context[1] . '.php')){
					$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($this->_context[1])), Alert::ERROR);
				}

				else{

					$pages = Symphony::Database()->fetch("SELECT * FROM `tbl_pages` WHERE `events` REGEXP '[[:<:]]".$this->_context[1]."[[:>:]]' ");

					if(is_array($pages) && !empty($pages)){
						foreach($pages as $page){

							$events = preg_split('/\s*,\s*/', $page['events'], -1, PREG_SPLIT_NO_EMPTY);
							$events = array_flip($events);
							unset($events[$this->_context[1]]);

							$page['events'] = implode(',', array_flip($events));

							Symphony::Database()->update($page, 'tbl_pages', "`id` = '".$page['id']."'");
						}
					}

					redirect(SYMPHONY_URL . '/blueprints/components/');
				}

			}
		}

		public function __formAction(){
			$fields = $_POST['fields'];

			$this->_errors = array();

			if(trim($fields['name']) == '') $this->_errors['name'] = __('This is a required field');
			if(trim($fields['source']) == '') $this->_errors['source'] = __('This is a required field');
            $filters = (is_array($fields['filters'])) ? $fields['filters'] : array();

			$classname = Lang::createHandle($fields['name'], NULL, '_', false, true, array('@^[^a-z]+@i' => '', '/[^\w-\.]/i' => ''));
			$rootelement = str_replace('_', '-', $classname);

			$file = EVENTS . '/event.' . $classname . '.php';

			$isDuplicate = false;
			$queueForDeletion = NULL;

			if($this->_context[0] == 'new' && is_file($file)) $isDuplicate = true;
			elseif($this->_context[0] == 'edit'){
				$existing_handle = $this->_context[1];
				if($classname != $existing_handle && is_file($file)) $isDuplicate = true;
				elseif($classname != $existing_handle) $queueForDeletion = EVENTS . '/event.' . $existing_handle . '.php';
			}

			##Duplicate
			if($isDuplicate) $this->_errors['name'] = __('An Event with the name <code>%s</code> name already exists', array($classname));

			if(empty($this->_errors)){

				$multiple = in_array('expect-multiple', $filters);

				$eventShell = file_get_contents(TEMPLATE . '/event.tpl');

				$about = array(
					'name' => $fields['name'],
					'version' => '1.0',
					'release date' => DateTimeObj::getGMT('c'),
					'author name' => Administration::instance()->Author->getFullName(),
					'author website' => URL,
					'author email' => Administration::instance()->Author->get('email'),
					'trigger condition' => $rootelement
				);

				$source = $fields['source'];

				$filter = NULL;
				$elements = NULL;
				$this->__injectAboutInformation($eventShell, $about);
				$this->__injectFilters($eventShell, $filters);

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

				if(is_array($filters) && !empty($filters)){
					$documentation_parts[] = new XMLElement('p', __('The following is an example of what is returned if any options return an error:'));

					$code = new XMLElement($rootelement, NULL, array('result' => 'error'));
					$code->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
					$code->appendChild(new XMLElement('filter', NULL, array('name' => 'admin-only', 'status' => 'failed')));
					$code->appendChild(new XMLElement('filter', __('Recipient not found'), array('name' => 'send-email', 'status' => 'failed')));
					$code->setValue('...', false);
					$documentation_parts[] = self::processDocumentationCode($code);
				}

				###

				$documentation_parts[] = new XMLElement('h3', __('Example Front-end Form Markup'));

				$documentation_parts[] = new XMLElement('p', __('This is an example of the form markup you can use on your frontend:'));
				$container = new XMLElement('form', NULL, array('method' => 'post', 'action' => '', 'enctype' => 'multipart/form-data'));
				$container->appendChild(Widget::Input('MAX_FILE_SIZE', Symphony::Configuration()->get('max_upload_size', 'admin'), 'hidden'));

				$sectionManager = new SectionManager($this->_Parent);
				$section = $sectionManager->fetch($fields['source']);

				$section_fields = $section->fetchFields();
				if(is_array($section_fields) && !empty($section_fields)) {
					foreach($section_fields as $f) {
						if ($f->getExampleFormMarkup() instanceof XMLElement) {
							$container->appendChild($f->getExampleFormMarkup());
						}
					}
				}

				$container->appendChild(Widget::Input('action['.$rootelement.']', __('Submit'), 'submit'));

				$code = $container->generate(true);

				$documentation_parts[] = self::processDocumentationCode(($multiple ? str_replace('fields[', 'fields[0][', $code) : $code));

				$documentation_parts[] = new XMLElement('p', __('To edit an existing entry, include the entry ID value of the entry in the form. This is best as a hidden field like so:'));
				$documentation_parts[] = self::processDocumentationCode(Widget::Input('id' . ($multiple ? '[0]' : NULL), 23, 'hidden'));

				$documentation_parts[] = new XMLElement('p', __('To redirect to a different location upon a successful save, include the redirect location in the form. This is best as a hidden field like so, where the value is the URL to redirect to:'));
				$documentation_parts[] = self::processDocumentationCode(Widget::Input('redirect', URL.'/success/', 'hidden'));

				if(in_array('send-email', $filters)){
					$documentation_parts[] = new XMLElement('h3', __('Send Notification Email'));

					$documentation_parts[] = new XMLElement('p', __('Upon the event successfully saving the entry, this option takes input from the form and send an email to the desired recipient. <b>It currently does not work with "Allow Multiple".</b> The following are the recognised fields:'));

					$documentation_parts[] = self::processDocumentationCode(
						'send-email[sender-email] // '.__('Optional').self::CRLF.
						'send-email[sender-name] // '.__('Optional').self::CRLF.
						'send-email[reply-to-email] // '.__('Optional').self::CRLF.
						'send-email[reply-to-name] // '.__('Optional').self::CRLF.
						'send-email[subject]'.self::CRLF.
						'send-email[body]'.self::CRLF.
						'send-email[recipient] // '.__('list of comma-separated author usernames.'));

					$documentation_parts[] = new XMLElement('p', __('All of these fields can be set dynamically using the exact field name of another field in the form as shown below in the example form:'));

					$documentation_parts[] = self::processDocumentationCode('<form action="" method="post">
	<fieldset>
		<label>'.__('Name').' <input type="text" name="fields[author]" value="" /></label>
		<label>'.__('Email').' <input type="text" name="fields[email]" value="" /></label>
		<label>'.__('Message').' <textarea name="fields[message]" rows="5" cols="21"></textarea></label>
		<input name="send-email[sender-email]" value="fields[email]" type="hidden" />
		<input name="send-email[sender-name]" value="fields[author]" type="hidden" />
		<input name="send-email[reply-to-email]" value="fields[email]" type="hidden" />
		<input name="send-email[reply-to-name]" value="fields[author]" type="hidden" />
		<input name="send-email[subject]" value="You are being contacted" type="hidden" />
		<input name="send-email[body]" value="fields[message]" type="hidden" />
		<input name="send-email[recipient]" value="fred" type="hidden" />
		<input id="submit" type="submit" name="action[save-contact-form]" value="Send" />
	</fieldset>
</form>');

				}

				/**
				 * Allows adding documentation for new filters. A reference to the $documentation
 				 * array is provided, along with selected filters
				 * @delegate AppendEventFilterDocumentation
				 * @param string $context
				 * '/blueprints/events/(edit|new|info)/'
				 * @param array $selected
				 *  An array of all the selected filters for this Event
				 * @param array $documentation
				 *  An array of all the documentation XMLElements, passed by reference
				 */
				Symphony::ExtensionManager()->notifyMembers('AppendEventFilterDocumentation', '/blueprints/events/' . $this->_context[0] . '/', array('selected' => $filters, 'documentation' => &$documentation_parts));

				$documentation = join(self::CRLF, array_map(create_function('$x', 'return rtrim($x->generate(true, 4));'), $documentation_parts));
				$documentation = str_replace('\'', '\\\'', $documentation);

				$eventShell = str_replace('<!-- CLASS NAME -->', $classname, $eventShell);
				$eventShell = str_replace('<!-- SOURCE -->', $source, $eventShell);
				$eventShell = str_replace('<!-- DOCUMENTATION -->', General::tabsToSpaces($documentation, 2), $eventShell);
				$eventShell = str_replace('<!-- ROOT ELEMENT -->', $rootelement, $eventShell);

				## Remove left over placeholders
				$eventShell = preg_replace(array('/<!--[\w ]++-->/'), '', $eventShell);

				if($this->_context[0] == 'new') {
					/**
					 * Prior to creating an Event, the file path where it will be written to
					 * is provided and well as the contents of that file.
					 *
					 * @delegate EventsPreCreate
					 * @since Symphony 2.2
					 * @param string $context
					 * '/blueprints/events/'
					 * @param string $file
					 *  The path to the Event file
					 * @param string $contents
					 *  The contents for this Event as a string passed by reference
					 * @param array $filters
					 *  An array of the filters attached to this event
					 */
					Symphony::ExtensionManager()->notifyMembers('EventPreCreate', '/blueprints/events/', array(
						'file' => $file,
						'contents' => &$eventShell,
						'filters' => $filters
					));
				}
				else {
					/**
					 * Prior to editing an Event, the file path where it will be written to
					 * is provided and well as the contents of that file.
					 *
					 * @delegate EventPreEdit
					 * @since Symphony 2.2
					 * @param string $context
					 * '/blueprints/events/'
					 * @param string $file
					 *  The path to the Event file
					 * @param string $contents
					 *  The contents for this Event as a string passed by reference
					 * @param array $filters
					 *  An array of the filters attached to this event
					 */
					Symphony::ExtensionManager()->notifyMembers('EventPreEdit', '/blueprints/events/', array(
						'file' => $file,
						'contents' => &$eventShell,
						'filters' => $filters
					));
				}

				// Write the file
				if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $eventShell, Symphony::Configuration()->get('write_mode', 'file')))
					$this->pageAlert(__('Failed to write Event to <code>%s</code>. Please check permissions.', array(EVENTS)), Alert::ERROR);

				// Write Successful, add record to the database
				else{

					if($queueForDeletion){
						General::deleteFile($queueForDeletion);

						$sql = "SELECT * FROM `tbl_pages` WHERE `events` REGEXP '[[:<:]]".$existing_handle."[[:>:]]' ";
						$pages = Symphony::Database()->fetch($sql);

						if(is_array($pages) && !empty($pages)){
							foreach($pages as $page){

								$page['events'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['events']);

								Symphony::Database()->update($page, 'tbl_pages', "`id` = '".$page['id']."'");
							}
						}

					}

					if($this->_context[0] == 'new') {
						/**
						 * After creating the Event, the path to the Event file is provided
						 *
						 * @delegate EventPostCreate
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/events/'
						 * @param string $file
						 *  The path to the Event file
						 */
						Symphony::ExtensionManager()->notifyMembers('EventPostCreate', '/blueprints/events/', array('file' => $file));
					}
					else {
						/**
						 * After editing the Event, the path to the Event file is provided
						 *
						 * @delegate EventPostEdit
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/events/'
						 * @param string $file
						 *  The path to the Event file
						 */
						Symphony::ExtensionManager()->notifyMembers('EventPostEdit', '/blueprints/events/', array('file' => $file));
					}

					redirect(SYMPHONY_URL . '/blueprints/events/edit/'.$classname.'/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');

				}
			}
		}

		public static function processDocumentationCode($code){
			return new XMLElement('pre', '<code>' . str_replace('<', '&lt;', str_replace('&', '&amp;', trim((is_object($code) ? $code->generate(true) : $code)))) . '</code>', array('class' => 'XML'));
		}

		public function __injectFilters(&$shell, $elements){
			if(!is_array($elements) || empty($elements)) return;

			$shell = str_replace('<!-- FILTERS -->',  "'" . implode("'," . self::CRLF . "\t\t\t\t'", $elements) . "'", $shell);
		}

		public function __injectAboutInformation(&$shell, $details){
			if(!is_array($details) || empty($details)) return;

			foreach($details as $key => $val) $shell = str_replace('<!-- ' . strtoupper($key) . ' -->', addslashes($val), $shell);
		}
	}
