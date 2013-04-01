<?php
	/**
	 * @package content
	 */

	/**
	 * The Event Editor allows a developer to create events that typically
	 * allow Frontend forms to populate Sections or edit Entries.
	 */
	require_once(TOOLKIT . '/class.resourcespage.php');
	require_once FACE . '/interface.provider.php';

	Class contentBlueprintsEvents extends ResourcesPage {

		public $_errors = array();

		public function __viewIndex($resource_type) {
			parent::__viewIndex(RESOURCE_TYPE_EVENT);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Events'), __('Symphony'))));
			$this->appendSubheading(__('Events'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a new event'), 'create button', NULL, array('accesskey' => 'c')));
		}

		public function __viewNew() {
			$this->__form();
		}

		public function __viewEdit() {
			$this->__form();
		}

		public function __viewInfo() {
			$this->__form(true);
		}

		public function __form($readonly=false) {
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			if($formHasErrors) {
				$this->pageAlert(
					__('An error occurred while processing this form. See below for details.')
					, Alert::ERROR
				);
			}
			// These alerts are only valid if the form doesn't have errors
			else if(isset($this->_context[2])) {
				switch($this->_context[2]) {
					case 'saved':
						$this->pageAlert(
							__('Event updated at %s.', array(DateTimeObj::getTimeAgo()))
							. ' <a href="' . SYMPHONY_URL . '/blueprints/events/new/" accesskey="c">'
							. __('Create another?')
							. '</a> <a href="' . SYMPHONY_URL . '/blueprints/events/" accesskey="a">'
							. __('View all Events')
							. '</a>'
							, Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__('Event created at %s.', array(DateTimeObj::getTimeAgo()))
							. ' <a href="' . SYMPHONY_URL . '/blueprints/events/new/" accesskey="c">'
							. __('Create another?')
							. '</a> <a href="' . SYMPHONY_URL . '/blueprints/events/" accesskey="a">'
							. __('View all Events')
							. '</a>'
							, Alert::SUCCESS);
						break;
				}
			}

			$isEditing = ($readonly ? true : false);
			$fields = array("name"=>null, "filters"=>null);
			$about = array("name"=>null);
			$providers = Symphony::ExtensionManager()->getProvidersOf(iProvider::EVENT);

			if(isset($_POST['fields'])) {
				$fields = $_POST['fields'];

				if($this->_context[0] == 'edit') {
					$isEditing = true;
				}
			}

			else if($this->_context[0] == 'edit' || $this->_context[0] == 'info') {
				$isEditing = true;
				$handle = $this->_context[1];
				$existing = EventManager::create($handle);
				$about = $existing->about();

				if ($this->_context[0] == 'edit' && !$existing->allowEditorToParse()) {
					redirect(SYMPHONY_URL . '/blueprints/events/info/' . $handle . '/');
				}

				$fields['name'] = $about['name'];
				$fields['source'] = $existing->getSource();
				$provided = false;

				if(!empty($providers)) {
					foreach($providers as $providerClass => $provider) {
						if($fields['source'] == call_user_func(array($providerClass, 'getClass'))) {
							$fields = array_merge($fields, $existing->settings());
							$provided = true;
							break;
						}
					}
				}

				if(!$provided) {
					if(isset($existing->eParamFILTERS)) {
						$fields['filters'] = $existing->eParamFILTERS;
					}
				}
			}

			// Handle name on edited changes, or from reading an edited datasource
			if(isset($about['name'])) {
				$name = $about['name'];
			}
			else if(isset($fields['name'])) {
				$name = $fields['name'];
			}

			$this->setPageType('form');
			$this->setTitle(__(($isEditing ? '%1$s &ndash; %2$s &ndash; %3$s' : '%2$s &ndash; %3$s'), array($about['name'], __('Events'), __('Symphony'))));
			$this->appendSubheading(($isEditing ? $about['name'] : __('Untitled')));
			$this->insertBreadcrumbs(array(
				Widget::Anchor(__('Events'), SYMPHONY_URL . '/blueprints/events/'),
			));

			if(!$readonly) {
				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings picker');
				$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

				$group = new XMLElement('div');
				$group->setAttribute('class', 'two columns');

			// Name
				$label = Widget::Label(__('Name'));
				$label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));

				$div = new XMLElement('div');
				$div->setAttribute('class', 'column');
				if(isset($this->_errors['name'])) {
					$div->appendChild(Widget::Error($label, $this->_errors['name']));
				}
				else {
					$div->appendChild($label);
				}
				$group->appendChild($div);

			// Source
				$label = Widget::Label(__('Source'));
				$sections = SectionManager::fetch(NULL, 'ASC', 'name');
				$options = array();
				$section_options = array();
				$source = isset($fields['source']) ? $fields['source'] : null;

				if(is_array($sections) && !empty($sections)) {
					$section_options = array('label' => __('Sections'), 'options' => array());
					foreach($sections as $s) {
						$section_options['options'][] = array($s->get('id'), $source == $s->get('id'), General::sanitize($s->get('name')));
					}
				}

				$options[] = $section_options;

				// Loop over the event providers
				if(!empty($providers)) {
					$p = array('label' => __('From extensions'), 'options' => array());

					foreach($providers as $providerClass => $provider) {
						$p['options'][] = array(
							$providerClass, ($fields['source'] == $providerClass), $provider
						);
					}

					$options[] = $p;
				}

				$label->appendChild(
					Widget::Select('fields[source]', $options, array(
						'id' => 'event-context',
						'class' => 'picker'
					))
				);

				$div = new XMLElement('div');
				$div->setAttribute('class', 'column');
				if(isset($this->_errors['source'])) {
					$div->appendChild(Widget::Error($label, $this->_errors['source']));
				}
				else {
					$div->appendChild($label);
				}

				$group->appendChild($div);
				$fieldset->appendChild($group);
				$this->Form->appendChild($fieldset);

				// Filters
				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('id', 'sections');
				$fieldset->setAttribute('class', 'settings pickable');
				$fieldset->setAttribute('data-relation', 'event-context');
				$fieldset->appendChild(new XMLElement('legend', __('Filters')));
				$p = new XMLElement('p',
					__('Event Filters add additional conditions or actions to an event.')
				);
				$p->setAttribute('class', 'help');
				$fieldset->appendChild($p);

				$filters = isset($fields['filters']) ? $fields['filters'] : array();
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
				Symphony::ExtensionManager()->notifyMembers('AppendEventFilter', '/blueprints/events/' . $this->_context[0] . '/', array(
					'selected' => $filters,
					'options' => &$options
				));

				$fieldset->appendChild(Widget::Select('fields[filters][]', $options, array('multiple' => 'multiple')));

				$this->Form->appendChild($fieldset);

			// Providers
				if(!empty($providers)) {
					foreach($providers as $providerClass => $provider) {
						if($isEditing && $fields['source'] !== call_user_func(array($providerClass, 'getSource'))) continue;

						call_user_func_array(array($providerClass, 'buildEditor'), array($this->Form, &$this->_errors, $fields, $handle));
					}
				}
			}

			else {
				// Author
				if(isset($about['author']['website'])) {
					$link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));
				}
				else if(isset($about['author']['email'])) {
					$link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);
				}
				else {
					$link = $about['author']['name'];
				}

				if($link) {
					$fieldset = new XMLElement('fieldset');
					$fieldset->setAttribute('class', 'settings');
					$fieldset->appendChild(new XMLElement('legend', __('Author')));
					$fieldset->appendChild(new XMLElement('p', $link->generate(false)));
					$this->Form->appendChild($fieldset);
				}

				// Version
				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings');
				$fieldset->appendChild(new XMLElement('legend', __('Version')));
				$version = array_key_exists('version', $about) ? $about['version'] : null;
				$release_date = array_key_exists('release-date', $about) ? $about['release-date'] : filemtime(EventManager::__getDriverPath($handle));

				if(preg_match('/^\d+(\.\d+)*$/', $version)) {
					$fieldset->appendChild(
						new XMLElement('p', __('%1$s released on %2$s', array($version, DateTimeObj::format($release_date, __SYM_DATE_FORMAT__))))
					);
				}
				else if(!is_null($version)) {
					$fieldset->appendChild(
						new XMLElement('p', __('Created by %1$s at %2$s', array($version, DateTimeObj::format($release_date, __SYM_DATE_FORMAT__))))
					);
				}
				else {
					$fieldset->appendChild(
						new XMLElement('p', __('Last modified on %s', array(DateTimeObj::format($release_date, __SYM_DATE_FORMAT__))))
					);
				}
				$this->Form->appendChild($fieldset);
			}

			// If we are editing an event, it assumed that the event has documentation
			if($isEditing && method_exists($existing, 'documentation')) {
				// Description
				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings');

				$doc = $existing->documentation();
				if($doc) {
					$fieldset->setValue(
						'<legend>' . __('Description') . '</legend>' . PHP_EOL .
						General::tabsToSpaces(is_object($doc) ? $doc->generate(true) : $doc, 2)
					);
					$this->Form->appendChild($fieldset);
				}
			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($isEditing ? __('Save Changes') : __('Create Event')), 'submit', array('accesskey' => 's')));

			if($isEditing) {
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this event'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this event?')));
				$div->appendChild($button);
			}

			if(!$readonly) {
				$this->Form->appendChild($div);
			}
		}

		public function __actionNew() {
			if(array_key_exists('save', $_POST['action'])) {
				return $this->__formAction();
			}
		}

		public function __actionEdit() {
			if(array_key_exists('save', $_POST['action'])) {
				return $this->__formAction();
			}
			else if(array_key_exists('delete', $_POST['action'])) {

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

				if(!General::deleteFile(EVENTS . '/event.' . $this->_context[1] . '.php')) {
					$this->pageAlert(
						__('Failed to delete %s.', array('<code>' . $this->_context[1] . '</code>'))
						. ' ' . __('Please check permissions on %s.', array('<code>/workspace/events</code>'))
						, Alert::ERROR
					);
				}

				else {
					$pages = ResourceManager::getAttachedPages(RESOURCE_TYPE_EVENT, $this->_context[1]);
					foreach($pages as $page) {
						ResourceManager::detach(RESOURCE_TYPE_EVENT, $this->_context[1], $page['id']);
					}

					redirect(SYMPHONY_URL . '/blueprints/events/');
				}
			}
		}

		public function __actionIndex($resource_type) {
			return parent::__actionIndex(RESOURCE_TYPE_EVENT);
		}

		public function __formAction() {
			$fields = $_POST['fields'];
			$this->_errors = array();
			$providers = Symphony::ExtensionManager()->getProvidersOf(iProvider::EVENT);
			$providerClass = null;

			if(trim($fields['name']) == '') $this->_errors['name'] = __('This is a required field');
			if(trim($fields['source']) == '') $this->_errors['source'] = __('This is a required field');
			$filters = isset($fields['filters']) ? $fields['filters'] : array();

			// See if a Provided Datasource is saved
			if (!empty($providers)) {
				foreach($providers as $providerClass => $provider) {
					if($fields['source'] == call_user_func(array($providerClass, 'getSource'))) {
						call_user_func_array(array($providerClass, 'validate'), array(&$fields, &$this->_errors));
						break;
					}

					unset($providerClass);
				}
			}

			$classname = Lang::createHandle($fields['name'], 255, '_', false, true, array('@^[^a-z\d]+@i' => '', '/[^\w-\.]/i' => ''));
			$rootelement = str_replace('_', '-', $classname);
			$extends = 'SectionEvent';

			// Check to make sure the classname is not empty after handlisation.
			if(empty($classname) && !isset($this->_errors['name'])) {
				$this->_errors['name'] = __('Please ensure name contains at least one Latin-based character.', array($classname));
			}

			$file = EVENTS . '/event.' . $classname . '.php';
			$isDuplicate = false;
			$queueForDeletion = NULL;

			if($this->_context[0] == 'new' && is_file($file)) {
				$isDuplicate = true;
			}
			else if($this->_context[0] == 'edit') {
				$existing_handle = $this->_context[1];
				if($classname != $existing_handle && is_file($file)) {
					$isDuplicate = true;
				}
				else if($classname != $existing_handle) {
					$queueForDeletion = EVENTS . '/event.' . $existing_handle . '.php';
				}
			}

			// Duplicate
			if($isDuplicate) $this->_errors['name'] = __('An Event with the name %s already exists', array('<code>' . $classname . '</code>'));

			if(empty($this->_errors)) {
				$multiple = in_array('expect-multiple', $filters);
				$elements = NULL;
				$placeholder = '<!-- GRAB -->';
				$source = $fields['source'];
				$params = array(
					'rootelement' => $rootelement,
				);
				$about = array(
					'name' => $fields['name'],
					'version' => 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'),
					'release date' => DateTimeObj::getGMT('c'),
					'author name' => Administration::instance()->Author->getFullName(),
					'author website' => URL,
					'author email' => Administration::instance()->Author->get('email')
				);

				// If there is a provider, get their template
				if($providerClass) {
					$eventShell = file_get_contents(call_user_func(array($providerClass, 'getTemplate')));
				}
				else {
					$eventShell = file_get_contents($this->getTemplate('blueprints.event'));
					$about['trigger condition'] = $rootelement;
				}

				$this->__injectAboutInformation($eventShell, $about);

				// Replace the name
				$eventShell = str_replace('<!-- CLASS NAME -->', $classname, $eventShell);

				// Build the templates
				if($providerClass) {
					$eventShell = call_user_func(array($providerClass, 'prepare'), $fields, $params, $eventShell);
				}
				else {
					$this->__injectFilters($eventShell, $filters);

				// Add Documentation
					$documentation = NULL;
					$documentation_parts = array();
					$documentation_parts[] = new XMLElement('h3', __('Success and Failure XML Examples'));
					$documentation_parts[] = new XMLElement('p', __('When saved successfully, the following XML will be returned:'));

					if($multiple) {
						$code = new XMLElement($rootelement);
						$entry = new XMLElement('entry', NULL, array('index' => '0', 'result' => 'success' , 'type' => 'create | edit'));
						$entry->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));

						$code->appendChild($entry);
					}

					else {
						$code = new XMLElement($rootelement, NULL, array('result' => 'success' , 'type' => 'create | edit'));
						$code->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));
					}

					$documentation_parts[] = self::processDocumentationCode($code);
					$documentation_parts[] = new XMLElement('p', __('When an error occurs during saving, due to either missing or invalid fields, the following XML will be returned') . ($multiple ? ' (<strong> ' . __('Notice that it is possible to get mixtures of success and failure messages when using the ‘Allow Multiple’ option') . '</strong>)' : NULL) . ':');

					if($multiple) {
						$code = new XMLElement($rootelement);

						$entry = new XMLElement('entry', NULL, array('index' => '0', 'result' => 'error'));
						$entry->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
						$entry->appendChild(new XMLElement('field-name', NULL, array('type' => 'invalid | missing')));
						$code->appendChild($entry);

						$entry = new XMLElement('entry', NULL, array('index' => '1', 'result' => 'success' , 'type' => 'create | edit'));
						$entry->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));
						$code->appendChild($entry);
					}

					else {
						$code = new XMLElement($rootelement, NULL, array('result' => 'error'));
						$code->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
						$code->appendChild(new XMLElement('field-name', NULL, array('type' => 'invalid | missing')));
					}

					$code->setValue('...', false);
					$documentation_parts[] = self::processDocumentationCode($code);

					if(is_array($filters) && !empty($filters)) {
						$documentation_parts[] = new XMLElement('p', __('The following is an example of what is returned if any options return an error:'));

						$code = new XMLElement($rootelement, NULL, array('result' => 'error'));
						$code->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
						$code->appendChild(new XMLElement('filter', NULL, array('name' => 'admin-only', 'status' => 'failed')));
						$code->appendChild(new XMLElement('filter', __('Recipient not found'), array('name' => 'send-email', 'status' => 'failed')));
						$code->setValue('...', false);
						$documentation_parts[] = self::processDocumentationCode($code);
					}

					$documentation_parts[] = new XMLElement('h3', __('Example Front-end Form Markup'));
					$documentation_parts[] = new XMLElement('p', __('This is an example of the form markup you can use on your frontend:'));
					$container = new XMLElement('form', NULL, array('method' => 'post', 'action' => '', 'enctype' => 'multipart/form-data'));
					$container->appendChild(Widget::Input('MAX_FILE_SIZE', (string)min(ini_size_to_bytes(ini_get('upload_max_filesize')), Symphony::Configuration()->get('max_upload_size', 'admin')), 'hidden'));

					if(is_numeric($fields['source'])) {
						$section = SectionManager::fetch($fields['source']);
						if($section instanceof Section) {
							$section_fields = $section->fetchFields();
							if(is_array($section_fields) && !empty($section_fields)) {
								foreach($section_fields as $f) {
									if ($f->getExampleFormMarkup() instanceof XMLElement) {
										$container->appendChild($f->getExampleFormMarkup());
									}
								}
							}
						}
					}

					$container->appendChild(Widget::Input('action['.$rootelement.']', __('Submit'), 'submit'));
					$code = $container->generate(true);

					$documentation_parts[] = self::processDocumentationCode(($multiple ? str_replace('fields[', 'fields[0][', $code) : $code));

					$documentation_parts[] = new XMLElement('p', __('To edit an existing entry, include the entry ID value of the entry in the form. This is best as a hidden field like so:'));
					$documentation_parts[] = self::processDocumentationCode(Widget::Input('id' . ($multiple ? '[0]' : NULL), '23', 'hidden'));

					$documentation_parts[] = new XMLElement('p', __('To redirect to a different location upon a successful save, include the redirect location in the form. This is best as a hidden field like so, where the value is the URL to redirect to:'));
					$documentation_parts[] = self::processDocumentationCode(Widget::Input('redirect', URL.'/success/', 'hidden'));

					if(in_array('send-email', $filters)) {
						$documentation_parts[] = new XMLElement('h3', __('Send Notification Email'));

						$documentation_parts[] = new XMLElement('p',
							__('Upon the event successfully saving the entry, this option takes input from the form and send an email to the desired recipient.')
							. ' <strong>'
							. __('It currently does not work with ‘Allow Multiple’')
							. '</strong>. '
							. __('The following are the recognised fields:')
						);

						$documentation_parts[] = self::processDocumentationCode(
							'send-email[sender-email] // '.__('Optional').PHP_EOL.
							'send-email[sender-name] // '.__('Optional').PHP_EOL.
							'send-email[reply-to-email] // '.__('Optional').PHP_EOL.
							'send-email[reply-to-name] // '.__('Optional').PHP_EOL.
							'send-email[subject]'.PHP_EOL.
							'send-email[body]'.PHP_EOL.
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
					Symphony::ExtensionManager()->notifyMembers('AppendEventFilterDocumentation', '/blueprints/events/' . $this->_context[0] . '/', array(
						'selected' => $filters,
						'documentation' => &$documentation_parts
					));

					$documentation = join(PHP_EOL, array_map(create_function('$x', 'return rtrim($x->generate(true, 4));'), $documentation_parts));
					$documentation = str_replace('\'', '\\\'', $documentation);

					$eventShell = str_replace('<!-- CLASS EXTENDS -->', $extends, $eventShell);
					$eventShell = str_replace('<!-- DOCUMENTATION -->', General::tabsToSpaces($documentation, 2), $eventShell);
				}

				$eventShell = str_replace('<!-- ROOT ELEMENT -->', $rootelement, $eventShell);
				$eventShell = str_replace('<!-- CLASS NAME -->', $classname, $eventShell);
				$eventShell = str_replace('<!-- SOURCE -->', $source, $eventShell);

				// Remove left over placeholders
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
				if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $eventShell, Symphony::Configuration()->get('write_mode', 'file'))) {
					$this->pageAlert(
						__('Failed to write Event to disk.')
						. ' ' . __('Please check permissions on %s.', array('<code>/workspace/events</code>'))
						, Alert::ERROR
					);
				}
				// Write Successful, add record to the database
				else {

					if($queueForDeletion) {
						General::deleteFile($queueForDeletion);

						$pages = PageManager::fetch(false, array('events', 'id'), array("
							`events` REGEXP '[[:<:]]" . $existing_handle . "[[:>:]]'
						"));

						if(is_array($pages) && !empty($pages)){
							foreach($pages as $page){

								$page['events'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['events']);

								PageManager::edit($page['id'], $page);
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
						Symphony::ExtensionManager()->notifyMembers('EventPostCreate', '/blueprints/events/', array(
							'file' => $file
						));
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
						 * @param string $previous_file
						 *  The path of the previous Event file in the case where an Event may
						 *  have been renamed. To get the handle from this value, see
						 *  `EventManager::__getHandleFromFilename`
						 */
						Symphony::ExtensionManager()->notifyMembers('EventPostEdit', '/blueprints/events/', array(
							'file' => $file,
							'previous_file' => ($queueForDeletion) ? $queueForDeletion : null
						));
					}

					redirect(SYMPHONY_URL . '/blueprints/events/edit/'.$classname.'/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');

				}
			}
		}

		public static function processDocumentationCode($code) {
			return new XMLElement('pre', '<code>' . str_replace('<', '&lt;', str_replace('&', '&amp;', trim((is_object($code) ? $code->generate(true) : $code)))) . '</code>', array('class' => 'XML'));
		}

		public function __injectFilters(&$shell, $elements) {
			if(!is_array($elements) || empty($elements)) return;

			$shell = str_replace('<!-- FILTERS -->',  "'" . implode("'," . PHP_EOL . "\t\t\t\t'", $elements) . "'", $shell);
		}

		public function __injectAboutInformation(&$shell, $details) {
			if(!is_array($details) || empty($details)) return;

			foreach($details as $key => $val) $shell = str_replace('<!-- ' . strtoupper($key) . ' -->', addslashes($val), $shell);
		}
	}
