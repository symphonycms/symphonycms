<?php

	if(!function_exists('buildFilterElement')){
		function buildFilterElement($name, $status, $message=null, array $attr=null){
			$ret = new XMLElement('filter', (!$message || is_object($message) ? null : $message), array('name' => $name, 'status' => $status));
			if(is_object($message)) $ret->appendChild($message);

			if(is_array($attr)) $ret->setAttributeArray($attr);

			return $ret;
		}
	}

	if(!function_exists('__reduceType')) {
		function __reduceType($a, $b) {
			return (empty($b)) ? 'missing' : 'invalid';
		}
	}

	if (!function_exists('__doit')) {
		function __doit($source, $fields, &$result, &$event, $filters = array(), $position=null, $entry_id=null){

			$post_values = new XMLElement('post-values');
			$filter_results = array();
			if(!is_array($filters)) $filters = array();

			// Create the post data cookie element
			if (is_array($fields) && !empty($fields)) {
				General::array_to_xml($post_values, $fields, true);
			}

			/**
			 * Prior to saving entry from the front-end. This delegate will
			 * force the Event to terminate if it populates the `$filter_results`
			 * array. All parameters are passed by reference.
			 *
			 * @delegate EventPreSaveFilter
			 * @param string $context
			 * '/frontend/'
			 * @param array $fields
			 * @param Event $event
			 * @param array $messages
			 *  An associative array of array's which contain 4 values,
			 *  the name of the filter (string), the status (boolean),
			 *  the message (string) an optionally an associative array
			 *  of additional attributes to add to the filter element.
			 * @param XMLElement $post_values
			 * @param integer $entry_id
			 *  If editing an entry, this parameter will be an integer,
			 *  otherwise null.
			 */
			Symphony::ExtensionManager()->notifyMembers(
				'EventPreSaveFilter',
				'/frontend/',
				array(
					'fields' => &$fields,
					'event' => &$event,
					'messages' => &$filter_results,
					'post_values' => &$post_values,
					'entry_id' => &$entry_id
				)
			);

			if (is_array($filter_results) && !empty($filter_results)) {
				$can_proceed = true;

				foreach ($filter_results as $fr) {
					list($name, $status, $message, $attributes) = $fr;

					$result->appendChild(buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes));

					if($status === false) $can_proceed = false;
				}

				if ($can_proceed !== true) {
					$result->appendChild($post_values);
					$result->setAttribute('result', 'error');
					$result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
					return false;
				}
			}

			include_once(TOOLKIT . '/class.sectionmanager.php');
			include_once(TOOLKIT . '/class.entrymanager.php');

			if(!$section = SectionManager::fetch($source)){
				$result->setAttribute('result', 'error');
				$result->appendChild(new XMLElement('message', __('The Section, %s, could not be found.', array($this->getSource()))));
				return false;
			}

			if(isset($entry_id)) {
				$entry = EntryManager::fetch($entry_id);
				$entry = $entry[0];

				if(!is_object($entry)){
					$result->setAttribute('result', 'error');
					$result->appendChild(new XMLElement('message', __('The Entry, %s, could not be found.', array($entry_id))));
					return false;
				}
			}

			else{
				$entry = EntryManager::create();
				$entry->set('section_id', $source);
			}

			if(__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $errors, ($entry->get('id') ? true : false))):
				$result->setAttribute('result', 'error');
				$result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));

				foreach($errors as $field_id => $message){
					$field = FieldManager::fetch($field_id);

					if(is_array($fields[$field->get('element_name')])) {
						$type = array_reduce($fields[$field->get('element_name')], '__reduceType');
					}
					else {
						$type = ($fields[$field->get('element_name')] == '') ? 'missing' : 'invalid';
					}

					$result->appendChild(new XMLElement($field->get('element_name'), null, array(
						'label' => General::sanitize($field->get('label')),
						'type' => $type,
						'message' => General::sanitize($message)
					)));
				}

				if(isset($post_values) && is_object($post_values)) $result->appendChild($post_values);

				return false;

			elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $errors, false, ($entry->get('id') ? true : false))):
				$result->setAttribute('result', 'error');
				$result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));

				foreach($errors as $field_id => $message){
					$field = FieldManager::fetch($field_id);
					$result->appendChild(new XMLElement($field->get('element_name'), null, array(
						'label' => General::sanitize($field->get('label')),
						'type' => 'invalid',
						'message' => General::sanitize($message)
					)));
				}

				if(isset($post_values) && is_object($post_values)) $result->appendChild($post_values);

				return false;

			else:

				if(!$entry->commit()){
					$result->setAttribute('result', 'error');
					$result->appendChild(new XMLElement('message', __('Unknown errors where encountered when saving.')));
					if(isset($post_values) && is_object($post_values)) $result->appendChild($post_values);
					return false;
				}

				$result->setAttribute('id', $entry->get('id'));

			endif;

			// PASSIVE FILTERS ONLY AT THIS STAGE. ENTRY HAS ALREADY BEEN CREATED.

			if(in_array('send-email', $filters) && !in_array('expect-multiple', $filters)){

				if(!function_exists('__sendEmailFindFormValue')){
					function __sendEmailFindFormValue($needle, $haystack, $discard_field_name=true, $default=null, $collapse=true){

						if(preg_match('/^(fields\[[^\]]+\],?)+$/i', $needle)){
							$parts = preg_split('/\,/i', $needle, -1, PREG_SPLIT_NO_EMPTY);
							$parts = array_map('trim', $parts);

							$stack = array();
							foreach($parts as $p){
								$field = str_replace(array('fields[', ']'), '', $p);
								($discard_field_name ? $stack[] = $haystack[$field] : $stack[$field] = $haystack[$field]);
							}

							if(is_array($stack) && !empty($stack)) return ($collapse ? implode(' ', $stack) : $stack);
							else $needle = null;
						}

						$needle = trim($needle);
						if(empty($needle)) return $default;

						return $needle;

					}
				}

				$fields = $_POST['send-email'];
				$db = Symphony::Database();

				$fields['recipient']		= __sendEmailFindFormValue($fields['recipient'], $_POST['fields'], true);
				$fields['recipient']		= preg_split('/\,/i', $fields['recipient'], -1, PREG_SPLIT_NO_EMPTY);
				$fields['recipient']		= array_map('trim', $fields['recipient']);

				$fields['subject']			= __sendEmailFindFormValue($fields['subject'], $_POST['fields'], true, __('[Symphony] A new entry was created on %s', array(Symphony::Configuration()->get('sitename', 'general'))));
				$fields['body']				= __sendEmailFindFormValue($fields['body'], $_POST['fields'], false, null, false);
				$fields['sender-email']		= __sendEmailFindFormValue($fields['sender-email'], $_POST['fields'], true, null);
				$fields['sender-name']		= __sendEmailFindFormValue($fields['sender-name'], $_POST['fields'], true, null);

				$fields['reply-to-name']	= __sendEmailFindFormValue($fields['reply-to-name'], $_POST['fields'], true, null);
				$fields['reply-to-email']	= __sendEmailFindFormValue($fields['reply-to-email'], $_POST['fields'], true, null);

				$edit_link = SYMPHONY_URL.'/publish/'.$section->get('handle').'/edit/'.$entry->get('id').'/';

				$language = Symphony::Configuration()->get('lang', 'symphony');

				$template_path = Event::getNotificationTemplate($language);

				$body = sprintf(file_get_contents($template_path), $section->get('name'), $edit_link);

				if(is_array($fields['body'])){
					foreach($fields['body'] as $field_handle => $value){
						$body .= "// $field_handle" . PHP_EOL . $value . PHP_EOL . PHP_EOL;
					}
				}
				else {
					$body .= $fields['body'];
				}

				// Loop over all the recipients and attempt to send them an email
				// Errors will be appended to the Event XML
				$errors = array();
				foreach($fields['recipient'] as $recipient){
					$author = AuthorManager::fetchByUsername($recipient);

					if(empty($author)) {
						$errors['recipient'][$recipient] = __('Recipient not found');
						continue;
					}

					$email = Email::create();

					// Huib: Exceptions are also thrown in the settings functions, not only in the send function.
					// Those Exceptions should be caught too.
					try{
						$email->recipients = array(
							$author->get('first_name') => $author->get('email')
						);

						if($fields['sender-name'] != null){
							$email->sender_name = $fields['sender-name'];
						}
						if($fields['sender-email'] != null){
							$email->sender_email_address = $fields['sender-email'];
						}
						if($fields['reply-to-name'] != null){
							$email->reply_to_name = $fields['reply-to-name'];
						}
						if($fields['reply-to-email'] != null){
							$email->reply_to_email_address = $fields['reply-to-email'];
						}

						$email->text_plain = str_replace('<!-- RECIPIENT NAME -->', $author->get('first_name'), $body);
						$email->subject = $fields['subject'];

						$email->send();
					}

					catch(EmailValidationException $e){
						$errors['address'][$author->get('email')] = $e->getMessage();
					}

					catch(EmailGatewayException $e){
						// The current error array does not permit custom tags.
						// Therefore, it is impossible to set a "proper" error message.
						// Will return the failed email address instead.
						$errors['gateway'][$author->get('email')] = $e->getMessage();
					}

					catch(EmailException $e){
						// Because we don't want symphony to break because it can not send emails,
						// all exceptions are logged silently.
						// Any custom event can change this behaviour.
						$errors['email'][$author->get('email')] = $e->getMessage();
					}
				}

				// If there were errors, output them to the event
				if(!empty($errors)){
					$xml = buildFilterElement('send-email', 'failed');
					foreach($errors as $type => $messages) {
						$xType = new XMLElement('error');
						$xType->setAttribute('error-type', $type);

						foreach($messages as $recipient => $message) {
							$xType->appendChild(
								new XMLElement('message', $message, array(
									'recipient' => $recipient
								))
							);
						}

						$xml->appendChild($xType);
					}

					$result->appendChild($xml);
				}

				else $result->appendChild(buildFilterElement('send-email', 'passed'));
			}

			$filter_results = array();

			/**
			 * After saving entry from the front-end. This delegate will not force
			 * the Events to terminate if it populates the `$filter_results` array.
			 * Provided with references to this object, the `$_POST` data and also
			 * the error array
			 *
			 * @delegate EventPostSaveFilter
			 * @param string $context
			 * '/frontend/'
			 * @param integer $entry_id
			 * @param array $fields
			 * @param Entry $entry
			 * @param Event $event
			 * @param array $messages
			 *  An associative array of array's which contain 4 values,
			 *  the name of the filter (string), the status (boolean),
			 *  the message (string) an optionally an associative array
			 *  of additional attributes to add to the filter element.
			 */
			Symphony::ExtensionManager()->notifyMembers('EventPostSaveFilter', '/frontend/', array(
				'entry_id' => $entry->get('id'),
				'fields' => $fields,
				'entry' => $entry,
				'event' => &$event,
				'messages' => &$filter_results
			));

			if(is_array($filter_results) && !empty($filter_results)){
				foreach($filter_results as $fr){
					list($name, $status, $message, $attributes) = $fr;

					$result->appendChild(buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes));
				}
			}

			$filter_errors = array();
			/**
			 * This delegate that lets extensions know the final status of the
			 * current Event. It is triggered when everything has processed correctly.
			 * The `$messages` array contains the results of the previous filters that
			 * have executed, and the `$errors` array contains any errors that have
			 * occurred as a result of this delegate. These errors cannot stop the
			 * processing of the Event, as that has already been done.
			 *
			 *
			 * @delegate EventFinalSaveFilter
			 * @param string $context
			 * '/frontend/'
			 * @param array $fields
			 * @param Event $event
			 * @param array $messages
			 *  An associative array of array's which contain 4 values,
			 *  the name of the filter (string), the status (boolean),
			 *  the message (string) an optionally an associative array
			 *  of additional attributes to add to the filter element.
			 * @param array $errors
			 *  An associative array of array's which contain 4 values,
			 *  the name of the filter (string), the status (boolean),
			 *  the message (string) an optionally an associative array
			 *  of additional attributes to add to the filter element.
			 * @param Entry $entry
			 */
			Symphony::ExtensionManager()->notifyMembers(
				'EventFinalSaveFilter', '/frontend/', array(
					'fields'	=> $fields,
					'event'		=> $event,
					'messages'	=> $filter_results,
					'errors'	=> &$filter_errors,
					'entry'		=> $entry
				)
			);

			if(is_array($filter_errors) && !empty($filter_errors)){
				foreach($filter_errors as $fr){
					list($name, $status, $message, $attributes) = $fr;

					$result->appendChild(buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes));
				}
			}

			$result->setAttributeArray(array('result' => 'success', 'type' => (isset($entry_id) ? 'edited' : 'created')));
			$result->appendChild(new XMLElement('message', (isset($entry_id) ? __('Entry edited successfully.') : __('Entry created successfully.'))));
			if(isset($post_values) && is_object($post_values)) $result->appendChild($post_values);

			return true;
		}
	}

	if(!isset($this->eParamFILTERS) || !is_array($this->eParamFILTERS)){
		$this->eParamFILTERS = array();
	}

	$result = new XMLElement(self::ROOTELEMENT);

	if(in_array('admin-only', $this->eParamFILTERS) && !Symphony::Engine()->isLoggedIn()){
		$result->setAttribute('result', 'error');
		$result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
		$result->appendChild(buildFilterElement('admin-only', 'failed'));
		return $result;
	}

	$entry_id = $position = $fields = null;
	$post = General::getPostData();
	$success = true;

	if (in_array('expect-multiple', $this->eParamFILTERS)) {
		if (is_array($post['fields'])) {
			foreach ($post['fields'] as $position => $fields) {
				if (isset($post['id'][$position]) && is_numeric($post['id'][$position])) {
					$entry_id = $post['id'][$position];
				}
				else {
					$entry_id = null;
				}

				$entry = new XMLElement('entry', null, array('position' => $position));

				$ret = __doit(
					self::getSource(), $fields, $entry, $this, $this->eParamFILTERS, $position, $entry_id
				);

				if (!$ret) $success = false;

				$result->appendChild($entry);
			}
		}
	}

	else {
		$fields = $post['fields'];
		$entry_id = null;

		if (isset($post['id']) && is_numeric($post['id'])) $entry_id = $post['id'];

		$success = __doit(self::getSource(), $fields, $result, $this, $this->eParamFILTERS, null, $entry_id);
	}

	if($success && isset($_REQUEST['redirect'])) redirect($_REQUEST['redirect']);
