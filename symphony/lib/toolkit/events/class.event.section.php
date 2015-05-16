<?php

/**
 * @package events
 */
/**
 * The `SectionEvent` class provides methods required to save
 * data entered on the frontend to a corresponding Symphony section.
 *
 * @since Symphony 2.3.1
 * @link http://getsymphony.com/learn/concepts/view/events/
 */

abstract class SectionEvent extends Event
{
    /**
     * An associative array of results from the filters that have run
     * on this event.
     * @var array
     */
    public $filter_results = array();

    /**
     * An associative array of errors from the filters that have run
     * on this event.
     * @var array
     */
    public $filter_errors = array();

    /**
     * This method will construct XML that represents the result of
     * an Event filter.
     *
     * @param string $name
     *  The name of the filter
     * @param string $status
     *  The status of the filter, either passed or failed.
     * @param XMLElement|string $message
     *  Optionally, an XMLElement or string to be appended to this
     *  `<filter>` element. XMLElement allows for more complex return
     *  types.
     * @param array $attributes
     *  An associative array of additional attributes to add to this
     *  `<filter>` element
     * @return XMLElement
     */
    public static function buildFilterElement($name, $status, $message = null, array $attributes = null)
    {
        $filter = new XMLElement('filter', (!$message || is_object($message) ? null : $message), array('name' => $name, 'status' => $status));

        if ($message instanceof XMLElement) {
            $filter->appendChild($message);
        }

        if (is_array($attributes)) {
            $filter->setAttributeArray($attributes);
        }

        return $filter;
    }

    /**
     * Appends errors generated from fields during the execution of an Event
     *
     * @param XMLElement $result
     * @param array $fields
     * @param array $errors
     * @param object $post_values
     * @throws Exception
     * @return XMLElement
     */
    public static function appendErrors(XMLElement $result, array $fields, $errors, $post_values)
    {
        $result->setAttribute('result', 'error');
        $result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.'), array(
            'message-id' => EventMessages::ENTRY_ERRORS
        )));

        foreach ($errors as $field_id => $message) {
            $field = FieldManager::fetch($field_id);

            // Do a little bit of a check for files so that we can correctly show
            // whether they are 'missing' or 'invalid'. If it's missing, then we
            // want to remove the data so `__reduceType` will correctly resolve to
            // missing instead of invalid.
            // @see https://github.com/symphonists/s3upload_field/issues/17
            if (isset($_FILES['fields']['error'][$field->get('element_name')])) {
                $upload = $_FILES['fields']['error'][$field->get('element_name')];

                if ($upload === UPLOAD_ERR_NO_FILE) {
                    unset($fields[$field->get('element_name')]);
                }
            }

            if (is_array($fields[$field->get('element_name')])) {
                $type = array_reduce($fields[$field->get('element_name')], array('SectionEvent', '__reduceType'));
            } else {
                $type = ($fields[$field->get('element_name')] == '') ? 'missing' : 'invalid';
            }

            $error = self::createError($field, $type, $message);
            $result->appendChild($error);
        }

        if (isset($post_values) && is_object($post_values)) {
            $result->appendChild($post_values);
        }

        return $result;
    }

    /**
     * Given a Field instance, the type of error, and the message, this function
     * creates an XMLElement node so that it can be added to the `?debug` for the
     * Event
     *
     * @since Symphony 2.5.0
     * @param Field $field
     * @param string $type
     *  At the moment 'missing' or 'invalid' accepted
     * @param string $message
     * @return XMLElement
     */
    public static function createError(Field $field, $type, $message = null)
    {
        $error = new XMLElement($field->get('element_name'), null, array(
            'label' => General::sanitize($field->get('label')),
            'type' => $type,
            'message-id' => ($type === 'missing') ? EventMessages::FIELD_MISSING : EventMessages::FIELD_INVALID,
            'message' => General::sanitize($message)
        ));

        return $error;
    }

    /**
     * This function searches the `$haystack` for the given `$needle`,
     * where the needle is a string representation of where the desired
     * value exists in the `$haystack` array. For example `fields[name]`
     * would look in the `$haystack` for the key of `fields` that has the
     * key `name` and return the value.
     *
     * @param string $needle
     *  The needle, ie. `fields[name]`.
     * @param array $haystack
     *  Associative array to find the needle, ie.
     *      `array('fields' => array(
     *          'name' => 'Bob',
     *          'age' => '10'
     *      ))`
     * @param string $default
     *  If the `$needle` is not found, return this value. Defaults to null.
     * @param boolean $discard_field_name
     *  When matches are found in the `$haystack`, they are added to results
     *  array. This parameter defines if this should be an associative array
     *  or just an array of the matches. Used in conjunction with `$collapse`
     * @param boolean $collapse
     *  If multiple values are found, this will cause them to be reduced
     *  to single string with ' ' as the separator. Defaults to true.
     * @return string|array
     */
    public static function replaceFieldToken($needle, $haystack, $default = null, $discard_field_name = true, $collapse = true)
    {
        if (preg_match('/^(fields\[[^\]]+\],?)+$/i', $needle)) {
            $parts = preg_split('/\,/i', $needle, -1, PREG_SPLIT_NO_EMPTY);
            $parts = array_map('trim', $parts);

            $stack = array();

            foreach ($parts as $p) {
                $field = str_replace(array('fields[', ']'), '', $p);
                ($discard_field_name ? $stack[] = $haystack[$field] : $stack[$field] = $haystack[$field]);
            }

            if (is_array($stack) && !empty($stack)) {
                return $collapse ? implode(' ', $stack) : $stack;
            } else {
                $needle = null;
            }
        }

        $needle = trim($needle);

        if (empty($needle)) {
            return $default;
        } else {
            return $needle;
        }
    }

    /**
     * Helper method to determine if a field is missing, or if the data
     * provided was invalid. Used in conjunction with `array_reduce`.
     *
     * @param array $a,
     * @param array $b
     * @return string
     *  'missing' or 'invalid'
     */
    public function __reduceType($a, $b)
    {
        if (is_array($b)) {
            return array_reduce($b, array('SectionEvent', '__reduceType'));
        }

        return (strlen(trim($b)) === 0) ? 'missing' : 'invalid';
    }

    /**
     * This function will process the core Filters, Admin Only and Expect
     * Multiple, before invoking the `__doit` function, which actually
     * processes the Event. Once the Event has executed, this function will
     * determine if the user should be redirected to a URL, or to just return
     * the XML.
     *
     * @throws Exception
     * @return XMLElement|void
     *  If `$_REQUEST{'redirect']` is set, and the Event executed successfully,
     *  the user will be redirected to the given location. If `$_REQUEST['redirect']`
     *  is not set, or the Event encountered errors, an XMLElement of the Event
     *  result will be returned.
     */
    public function execute()
    {
        if (!isset($this->eParamFILTERS) || !is_array($this->eParamFILTERS)) {
            $this->eParamFILTERS = array();
        }

        $result = new XMLElement($this->ROOTELEMENT);

        if (in_array('admin-only', $this->eParamFILTERS) && !Symphony::Engine()->isLoggedIn()) {
            $result->setAttribute('result', 'error');
            $result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.'), array(
                'message-id' => EventMessages::ENTRY_ERRORS
            )));
            $result->appendChild(self::buildFilterElement('admin-only', 'failed'));
            return $result;
        }

        $entry_id = $position = $fields = null;
        $post = General::getPostData();
        $success = true;
        if (!is_array($post['fields'])) {
            $post['fields'] = array();
        }

        if (in_array('expect-multiple', $this->eParamFILTERS)) {
            foreach ($post['fields'] as $position => $fields) {
                if (isset($post['id'][$position]) && is_numeric($post['id'][$position])) {
                    $entry_id = $post['id'][$position];
                } else {
                    $entry_id = null;
                }

                $entry = new XMLElement('entry', null, array('position' => $position));

                // Reset errors for each entry execution
                $this->filter_results = $this->filter_errors = array();

                // Ensure that we are always dealing with an array.
                if (!is_array($fields)) {
                    $fields = array();
                }

                // Execute the event for this entry
                if (!$this->__doit($fields, $entry, $position, $entry_id)) {
                    $success = false;
                }

                $result->appendChild($entry);
            }
        } else {
            $fields = $post['fields'];

            if (isset($post['id']) && is_numeric($post['id'])) {
                $entry_id = $post['id'];
            }

            $success = $this->__doit($fields, $result, null, $entry_id);
        }

        if ($success && isset($_REQUEST['redirect'])) {
            redirect($_REQUEST['redirect']);
        }

        return $result;
    }

    /**
     * This function does the bulk of processing the Event, from running the delegates
     * to validating the data and eventually saving the data into Symphony. The result
     * of the Event is returned via the `$result` parameter.
     *
     * @param array $fields
     *  An array of $_POST data, to process and add/edit an entry.
     * @param XMLElement $result
     *  The XMLElement contains the result of the Event, it is passed by
     *  reference.
     * @param integer $position
     *  When the Expect Multiple filter is added, this event should expect
     *  to deal with adding (or editing) multiple entries at once.
     * @param integer $entry_id
     *  If this Event is editing an existing entry, that Entry ID will
     *  be passed to this function.
     * @throws Exception
     * @return XMLElement
     *  The result of the Event
     */
    public function __doit(array $fields = array(), XMLElement &$result, $position = null, $entry_id = null)
    {
        $post_values = new XMLElement('post-values');

        if (!is_array($this->eParamFILTERS)) {
            $this->eParamFILTERS = array();
        }

        // Check to see if the Section of this Event is valid.
        if (!$section = SectionManager::fetch($this->getSource())) {
            $result->setAttribute('result', 'error');
            $result->appendChild(new XMLElement('message', __('The Section, %s, could not be found.', array($this->getSource())), array(
                'message-id' => EventMessages::SECTION_MISSING
            )));
            return false;
        }

        // Create the post data element
        if (!empty($fields)) {
            General::array_to_xml($post_values, $fields, true);
        }

        // If the EventPreSaveFilter fails, return early
        if ($this->processPreSaveFilters($result, $fields, $post_values, $entry_id) === false) {
            return false;
        }

        // If the `$entry_id` is provided, check to see if it exists.
        // @todo If this was moved above PreSaveFilters, we can pass the
        // Entry object to the delegate meaning extensions don't have to
        // do that step.
        if (isset($entry_id)) {
            $entry = EntryManager::fetch($entry_id);
            $entry = $entry[0];

            if (!is_object($entry)) {
                $result->setAttribute('result', 'error');
                $result->appendChild(new XMLElement('message', __('The Entry, %s, could not be found.', array($entry_id)), array(
                    'message-id' => EventMessages::ENTRY_MISSING
                )));

                return false;
            }

            // `$entry_id` wasn't provided, create a new Entry object.
        } else {
            $entry = EntryManager::create();
            $entry->set('section_id', $this->getSource());
        }

        // Validate the data. `$entry->checkPostData` loops over all fields calling
        // their `checkPostFieldData` function. If the return of the function is
        // `Entry::__ENTRY_FIELD_ERROR__` then abort the event and add the error
        // messages to the `$result`.
        if (Entry::__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $errors, ($entry->get('id') ? true : false))) {
            $result = self::appendErrors($result, $fields, $errors, $post_values);
            return false;

            // If the data is good, process the data, almost ready to save it to the
            // Database. If processing fails, abort the event and display the errors
        } elseif (Entry::__ENTRY_OK__ != $entry->setDataFromPost($fields, $errors, false, ($entry->get('id') ? true : false))) {
            $result = self::appendErrors($result, $fields, $errors, $post_values);
            return false;

            // Data is checked, data has been processed, by trying to save the
            // Entry caused an error to occur, so abort and return.
        } elseif ($entry->commit() === false) {
            $result->setAttribute('result', 'error');
            $result->appendChild(new XMLElement('message', __('Unknown errors where encountered when saving.'), array(
                'message-id' => EventMessages::ENTRY_UNKNOWN
            )));

            if (isset($post_values) && is_object($post_values)) {
                $result->appendChild($post_values);
            }

            return false;

            // Entry was created, add the good news to the return `$result`
        } else {
            $result->setAttributeArray(array(
                'result' => 'success',
                'type' => (isset($entry_id) ? 'edited' : 'created'),
                'id' => $entry->get('id')
            ));

            if (isset($entry_id)) {
                $result->appendChild(new XMLElement('message', __('Entry edited successfully.'), array(
                    'message-id' => EventMessages::ENTRY_EDITED_SUCCESS
                )));
            } else {
                $result->appendChild(new XMLElement('message', __('Entry created successfully.'), array(
                    'message-id' => EventMessages::ENTRY_CREATED_SUCCESS
                )));
            }
        }

        // PASSIVE FILTERS ONLY AT THIS STAGE. ENTRY HAS ALREADY BEEN CREATED.
        if (in_array('send-email', $this->eParamFILTERS) && !in_array('expect-multiple', $this->eParamFILTERS)) {
            $result = $this->processSendMailFilter($result, $_POST['send-email'], $fields, $section, $entry);
        }

        $result = $this->processPostSaveFilters($result, $fields, $entry);
        $result = $this->processFinalSaveFilters($result, $fields, $entry);

        if (isset($post_values) && is_object($post_values)) {
            $result->appendChild($post_values);
        }

        return true;
    }

    /**
     * Processes all extensions attached to the `EventPreSaveFilter` delegate
     *
     * @uses EventPreSaveFilter
     *
     * @param XMLElement $result
     * @param array $fields
     * @param XMLElement $post_values
     * @param integer $entry_id
     * @return boolean
     */
    protected function processPreSaveFilters(XMLElement $result, array &$fields, XMLElement &$post_values, $entry_id = null)
    {
        $can_proceed = true;

        /**
         * Prior to saving entry from the front-end. This delegate will
         * force the Event to terminate if it populates the `$filter_results`
         * array. All parameters are passed by reference.
         *
         * @delegate EventPreSaveFilter
         * @param string $context
         * '/frontend/'
         * @param array $fields
         * @param Event $this
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
                'event' => &$this,
                'messages' => &$this->filter_results,
                'post_values' => &$post_values,
                'entry_id' => $entry_id
            )
        );

        // Logic taken from `event.section.php` to fail should any `$this->filter_results`
        // be returned. This delegate can cause the event to exit early.
        if (is_array($this->filter_results) && !empty($this->filter_results)) {
            $can_proceed = true;

            foreach ($this->filter_results as $fr) {
                list($name, $status, $message, $attributes) = $fr;

                $result->appendChild(
                    self::buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes)
                );

                if ($status === false) {
                    $can_proceed = false;
                }
            }

            if ($can_proceed !== true) {
                $result->appendChild($post_values);
                $result->setAttribute('result', 'error');
                $result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.'), array(
                    'message-id' => EventMessages::FILTER_FAILED
                )));
            }
        }

        // Reset the filter results to prevent duplicates. RE: #2179
        $this->filter_results = array();
        return $can_proceed;
    }

    /**
     * Processes all extensions attached to the `EventPostSaveFilter` delegate
     *
     * @uses EventPostSaveFilter
     *
     * @param XMLElement $result
     * @param array $fields
     * @param Entry $entry
     * @return XMLElement
     */
    protected function processPostSaveFilters(XMLElement $result, array $fields, Entry $entry = null)
    {
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
         * @param Event $this
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
            'event' => &$this,
            'messages' => &$this->filter_results
        ));

        if (is_array($this->filter_results) && !empty($this->filter_results)) {
            foreach ($this->filter_results as $fr) {
                list($name, $status, $message, $attributes) = $fr;

                $result->appendChild(
                    self::buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes)
                );
            }
        }

        // Reset the filter results to prevent duplicates. RE: #2179
        $this->filter_results = array();
        return $result;
    }

    /**
     * Processes all extensions attached to the `EventFinalSaveFilter` delegate
     *
     * @uses EventFinalSaveFilter
     *
     * @param XMLElement $result
     * @param array $fields
     * @param Entry $entry
     * @return XMLElement
     */
    protected function processFinalSaveFilters(XMLElement $result, array $fields, Entry $entry = null)
    {
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
         * @param Event $this
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
        Symphony::ExtensionManager()->notifyMembers('EventFinalSaveFilter', '/frontend/', array(
            'fields'    => $fields,
            'event'     => $this,
            'messages'  => $this->filter_results,
            'errors'    => &$this->filter_errors,
            'entry'     => $entry
        ));

        if (is_array($this->filter_errors) && !empty($this->filter_errors)) {
            foreach ($this->filter_errors as $fr) {
                list($name, $status, $message, $attributes) = $fr;

                $result->appendChild(
                    self::buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes)
                );
            }
        }

        // Reset the filter results to prevent duplicates. RE: #2179
        $this->filter_results = array();
        return $result;
    }

    /**
     * This function handles the Send Mail filter which will send an email
     * to each specified recipient informing them that an Entry has been
     * created.
     *
     * @param XMLElement $result
     *  The XMLElement of the XML that is going to be returned as part
     *  of this event to the page.
     * @param array $send_email
     *  Associative array of `send-mail` parameters.*  Associative array of `send-mail` parameters.
     * @param array $fields
     *  Array of post data to extract the values from
     * @param Section $section
     *  This current Entry that has just been updated or created
     * @param Entry $entry
     * @throws Exception
     * @return XMLElement
     *  The modified `$result` with the results of the filter.
     */
    public function processSendMailFilter(XMLElement $result, array $send_email, array &$fields, Section $section, Entry $entry)
    {
        $fields['recipient']        = self::replaceFieldToken($send_email['recipient'], $fields);
        $fields['recipient']        = preg_split('/\,/i', $fields['recipient'], -1, PREG_SPLIT_NO_EMPTY);
        $fields['recipient']        = array_map('trim', $fields['recipient']);

        $fields['subject']          = self::replaceFieldToken($send_email['subject'], $fields, __('[Symphony] A new entry was created on %s', array(Symphony::Configuration()->get('sitename', 'general'))));
        $fields['body']             = self::replaceFieldToken($send_email['body'], $fields, null, false, false);
        $fields['sender-email']     = self::replaceFieldToken($send_email['sender-email'], $fields);
        $fields['sender-name']      = self::replaceFieldToken($send_email['sender-name'], $fields);

        $fields['reply-to-name']    = self::replaceFieldToken($send_email['reply-to-name'], $fields);
        $fields['reply-to-email']   = self::replaceFieldToken($send_email['reply-to-email'], $fields);

        $edit_link = SYMPHONY_URL . '/publish/' . $section->get('handle') . '/edit/' . $entry->get('id').'/';
        $language = Symphony::Configuration()->get('lang', 'symphony');
        $template_path = Event::getNotificationTemplate($language);
        $body = sprintf(file_get_contents($template_path), $section->get('name'), $edit_link);

        if (is_array($fields['body'])) {
            foreach ($fields['body'] as $field_handle => $value) {
                $body .= "// $field_handle" . PHP_EOL . $value . PHP_EOL . PHP_EOL;
            }
        } else {
            $body .= $fields['body'];
        }

        // Loop over all the recipients and attempt to send them an email
        // Errors will be appended to the Event XML
        $errors = array();

        foreach ($fields['recipient'] as $recipient) {
            $author = AuthorManager::fetchByUsername($recipient);

            if (empty($author)) {
                $errors['recipient'][$recipient] = __('Recipient not found');
                continue;
            }

            $email = Email::create();

            // Huib: Exceptions are also thrown in the settings functions, not only in the send function.
            // Those Exceptions should be caught too.
            try {
                $email->recipients = array(
                    $author->get('first_name') => $author->get('email')
                );

                if ($fields['sender-name'] != null) {
                    $email->sender_name = $fields['sender-name'];
                }

                if ($fields['sender-email'] != null) {
                    $email->sender_email_address = $fields['sender-email'];
                }

                if ($fields['reply-to-name'] != null) {
                    $email->reply_to_name = $fields['reply-to-name'];
                }

                if ($fields['reply-to-email'] != null) {
                    $email->reply_to_email_address = $fields['reply-to-email'];
                }

                $email->text_plain = str_replace('<!-- RECIPIENT NAME -->', $author->get('first_name'), $body);
                $email->subject = $fields['subject'];
                $email->send();
            } catch (EmailValidationException $e) {
                $errors['address'][$author->get('email')] = $e->getMessage();

                // The current error array does not permit custom tags.
                // Therefore, it is impossible to set a "proper" error message.
                // Will return the failed email address instead.
            } catch (EmailGatewayException $e) {
                $errors['gateway'][$author->get('email')] = $e->getMessage();

                // Because we don't want symphony to break because it can not send emails,
                // all exceptions are logged silently.
                // Any custom event can change this behaviour.
            } catch (EmailException $e) {
                $errors['email'][$author->get('email')] = $e->getMessage();
            }
        }

        // If there were errors, output them to the event
        if (!empty($errors)) {
            $xml = self::buildFilterElement('send-email', 'failed');

            foreach ($errors as $type => $messages) {
                $xType = new XMLElement('error');
                $xType->setAttribute('error-type', $type);

                foreach ($messages as $recipient => $message) {
                    $xType->appendChild(
                        new XMLElement('message', $message, array(
                            'recipient' => $recipient
                        ))
                    );
                }

                $xml->appendChild($xType);
            }

            $result->appendChild($xml);
        } else {
            $result->appendChild(
                self::buildFilterElement('send-email', 'passed')
            );
        }

        return $result;
    }
}

/**
 * Basic lookup class for Event messages, allows for frontend developers
 * to localise and change event messages without relying on string
 * comparision.
 *
 * @since Symphony 2.4
 */
class EventMessages
{
    const UNKNOWN_ERROR = 0;

    const ENTRY_CREATED_SUCCESS = 100;
    const ENTRY_EDITED_SUCCESS = 101;
    const ENTRY_ERRORS = 102;
    const ENTRY_MISSING = 103;
    const ENTRY_NOT_UNIQUE = 104;

    const SECTION_MISSING = 200;

    const FIELD_MISSING = 301;
    const FIELD_INVALID = 302;
    const FIELD_NOT_UNIQUE = 303;

    const FILTER_FAILED = 400;

    const SECURITY_XSRF = 500;
}
