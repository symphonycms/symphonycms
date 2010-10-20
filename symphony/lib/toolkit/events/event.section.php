<?php
	
	if(!function_exists('buildFilterElement')){
		function buildFilterElement($name, $status, $message=NULL, array $attr=NULL){
			$ret = new XMLElement('filter', (!$message || is_object($message) ? NULL : $message), array('name' => $name, 'status' => $status));
			if(is_object($message)) $ret->appendChild($message);
			
			if($attr) $ret->setAttributeArray($attr);
			
			return $ret;
		}
	}
	
	if (!function_exists('__doit')) {
		function __doit($source, $fields, &$result, &$obj, &$event, $filters, $position=NULL, $entry_id=NULL){

			$post_values = new XMLElement('post-values');
			$filter_results = array();	
			
			## Create the post data cookie element
			if (is_array($fields) && !empty($fields)) {
				General::array_to_xml($post_values, $fields, true);
			}
			
			###
			# Delegate: EventPreSaveFilter
			# Description: Prior to saving entry from the front-end. This delegate will 
			#			   force the Event to terminate if it populates the error
			#              array reference. Provided with references to this object, the 
			#			   POST data and also the error array
			$obj->ExtensionManager->notifyMembers(
				'EventPreSaveFilter', 
				'/frontend/', 
				array(
					'fields' => $fields, 
					'event' => &$event, 
					'messages' => &$filter_results, 
					'post_values' => &$post_values
				)
			);
			
			if (is_array($filter_results) && !empty($filter_results)) {
				$can_proceed = true;

				foreach ($filter_results as $fr) {
					list($type, $status, $message) = $fr;
					
					$result->appendChild(buildFilterElement($type, ($status ? 'passed' : 'failed'), $message));
					
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

			$sectionManager = new SectionManager($obj);

			if(!$section = $sectionManager->fetch($source)){
				$result->setAttribute('result', 'error');			
				$result->appendChild(new XMLElement('message', __('Section is invalid')));
				return false;
			}

			$entryManager = new EntryManager($obj);

			if(isset($entry_id) && $entry_id != NULL){
				$entry =& $entryManager->fetch($entry_id);	
				$entry = $entry[0];

				if(!is_object($entry)){
					$result->setAttribute('result', 'error');			
					$result->appendChild(new XMLElement('message', __('Invalid Entry ID specified. Could not create Entry object.')));
					return false;
				}

			}

			else{
				$entry =& $entryManager->create();
				$entry->set('section_id', $source);
			}

			$filter_errors = array();

			if(__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $errors, ($entry->get('id') ? true : false))):
				$result->setAttribute('result', 'error');
				$result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));

				foreach($errors as $field_id => $message){
					$field = $entryManager->fieldManager->fetch($field_id);
					$result->appendChild(new XMLElement($field->get('element_name'), NULL, array('type' => ($fields[$field->get('element_name')] == '' ? 'missing' : 'invalid'), 'message' => General::sanitize($message))));
				}

				if(isset($post_values) && is_object($post_values)) $result->appendChild($post_values);		

				return false;

			elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $errors, false, ($entry->get('id') ? true : false))):
				$result->setAttribute('result', 'error');
				$result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
				
				if(isset($errors['field_id'])){
					$errors = array($errors);
				}

				foreach($errors as $err){
					$field = $entryManager->fieldManager->fetch($err['field_id']);
					$result->appendChild(new XMLElement($field->get('element_name'), NULL, array('type' => 'invalid')));
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

			## PASSIVE FILTERS ONLY AT THIS STAGE. ENTRY HAS ALREADY BEEN CREATED. 

			if(@in_array('send-email', $filters) && !@in_array('expect-multiple', $filters)){

				if(!function_exists('__sendEmailFindFormValue')){
					function __sendEmailFindFormValue($needle, $haystack, $discard_field_name=true, $default=NULL, $collapse=true){

						if(preg_match('/^(fields\[[^\]]+\],?)+$/i', $needle)){
							$parts = preg_split('/\,/i', $needle, -1, PREG_SPLIT_NO_EMPTY);
							$parts = array_map('trim', $parts);

							$stack = array();
							foreach($parts as $p){ 
								$field = str_replace(array('fields[', ']'), '', $p);
								($discard_field_name ? $stack[] = $haystack[$field] : $stack[$field] = $haystack[$field]);
							}

							if(is_array($stack) && !empty($stack)) return ($collapse ? implode(' ', $stack) : $stack);
							else $needle = NULL;
						}

						$needle = trim($needle);
						if(empty($needle)) return $default;

						return $needle;

					}
				}

				$fields = $_POST['send-email'];
				
				$fields['recipient'] = __sendEmailFindFormValue($fields['recipient'], $_POST['fields'], true);
				$fields['recipient'] = preg_split('/\,/i', $fields['recipient'], -1, PREG_SPLIT_NO_EMPTY);
				$fields['recipient'] = array_map('trim', $fields['recipient']);
				$fields['recipient'] = array_map(array('MySQL','cleanValue'), $fields['recipient']);

				$fields['recipient'] = $obj->Database->fetch("SELECT `email`, `first_name` FROM `tbl_authors` WHERE `username` IN ('".@implode("', '", $fields['recipient'])."') ");

				$fields['subject'] = __sendEmailFindFormValue($fields['subject'], $_POST['fields'], true, __('[Symphony] A new entry was created on %s', array($obj->Configuration->get('sitename', 'general'))));
				$fields['body'] = __sendEmailFindFormValue($fields['body'], $_POST['fields'], false, NULL, false);
				$fields['sender-email'] = __sendEmailFindFormValue($fields['sender-email'], $_POST['fields'], true, 'noreply@' . parse_url(URL, PHP_URL_HOST));
				$fields['sender-name'] = __sendEmailFindFormValue($fields['sender-name'], $_POST['fields'], true, 'Symphony');

				$edit_link = URL.'/symphony/publish/'.$section->get('handle').'/edit/'.$entry->get('id').'/';

				$body = __('Dear <!-- RECIPIENT NAME -->,') . General::CRLF . __('This is a courtesy email to notify you that an entry was created on the %1$s section. You can edit the entry by going to: %2$s', array($section->get('name'), $edit_link)). General::CRLF . General::CRLF;

				if(is_array($fields['body'])){
					foreach($fields['body'] as $field_handle => $value){
						$body .= "// $field_handle" . General::CRLF . $value . General::CRLF . General::CRLF;
					}
				}

				else $body .= $fields['body'];

				$errors = array();

				if(!is_array($fields['recipient']) || empty($fields['recipient'])){
					$result->appendChild(buildFilterElement('send-email', 'failed', __('No valid recipients found. Check send-email[recipient] field.')));
				}

				else{
					foreach($fields['recipient'] as $r){

						list($email, $name) = array_values($r);

						if(!General::sendEmail($email, 
										   $fields['sender-email'], 
										   $fields['sender-name'], 
										   $fields['subject'], 
										   str_replace('<!-- RECIPIENT NAME -->', $name, $body)))
										       $errors[] = $email;

					}

					if(!empty($errors)){

						$xml = buildFilterElement('send-email', 'failed');
						foreach($errors as $address) $xml->appendChild(new XMLElement('recipient', $address));

						$result->appendChild($xml);

					}

					else $result->appendChild(buildFilterElement('send-email', 'passed'));
				}
			}

			$filter_results = array();

			###
			# Delegate: EventPostSaveFilter
			# Description: After saving entry from the front-end. This delegate will not force the Events to terminate if it populates the error
			#              array reference. Provided with references to this object, the POST data and also the error array
			$obj->ExtensionManager->notifyMembers('EventPostSaveFilter', '/frontend/', array('entry_id' => $entry->get('id'), 
																							 'fields' => $fields, 
																							 'entry' => $entry, 
																							 'event' => &$event, 
																							 'messages' => &$filter_results));
																							

			if(is_array($filter_results) && !empty($filter_results)){
				foreach($filter_results as $fr){
					list($type, $status, $message) = $fr;

					$result->appendChild(buildFilterElement($type, ($status ? 'passed' : 'failed'), $message));

				}
			}
			
			###
			# Delegate: EventFinalSaveFilter
			$obj->ExtensionManager->notifyMembers(
				'EventFinalSaveFilter', '/frontend/', array(
					'fields'	=> $fields,
					'event'		=> &$event,
					'errors'	=> &$filter_errors,
					'entry'		=> $entry
				)
			);
			
			$result->setAttributeArray(array('result' => 'success', 'type' => (isset($entry_id) ? 'edited' : 'created')));
			$result->appendChild(new XMLElement('message', (isset($entry_id) ? __('Entry edited successfully.') : __('Entry created successfully.'))));
			if(isset($post_values) && is_object($post_values)) $result->appendChild($post_values);
			
			return true;
			
			## End Function
		}
	}
	
	if(!isset($this->eParamFILTERS) || !is_array($this->eParamFILTERS)){
		$this->eParamFILTERS = array();
	}
	
	$result = new XMLElement(self::ROOTELEMENT);
	
	if(@in_array('admin-only', $this->eParamFILTERS) && !$this->_Parent->isLoggedIn()){
		$result->setAttribute('result', 'error');			
		$result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
		$result->appendChild(buildFilterElement('admin-only', 'failed'));
		return $result;
	}

	$entry_id = $position = $fields = NULL;	
	$post = General::getPostData();
	$success = true;
	
	if (in_array('expect-multiple', $this->eParamFILTERS)) {
		if (is_array($post['fields']) && isset($post['fields'][0])) {
			foreach ($post['fields'] as $position => $fields) {
				if (isset($post['id'][$position]) && is_numeric($post['id'][$position])) {
					$entry_id = $post['id'][$position];
				}
				
				$entry = new XMLElement('entry', NULL, array('position' => $position));
				
				$ret = __doit(
					self::getSource(), $fields, $entry, $this->_Parent,
					$this, $this->eParamFILTERS, $position, $entry_id
				);
				
				if (!$ret) $success = false;
				
				$result->appendChild($entry);
			}
		}
	}
	
	else {
		$fields = $post['fields'];
		$entry_id = NULL;
		
		if (isset($post['id']) && is_numeric($post['id'])) $entry_id = $post['id'];
		
		$success = __doit(self::getSource(), $fields, $result, $this->_Parent, $this, $this->eParamFILTERS, NULL, $entry_id);
	}
	
	if($success && isset($_REQUEST['redirect'])) redirect($_REQUEST['redirect']);
	
	## return $result;
