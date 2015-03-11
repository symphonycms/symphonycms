<?php
/**
 * @package content
 */

/**
 * The Event Editor allows a developer to create events that typically
 * allow Frontend forms to populate Sections or edit Entries.
 */

class contentBlueprintsEvents extends ResourcesPage
{
    public $_errors = array();

    public function __viewIndex($resource_type)
    {
        parent::__viewIndex(ResourceManager::RESOURCE_TYPE_EVENT);

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Events'), __('Symphony'))));
        $this->appendSubheading(__('Events'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a new event'), 'create button', null, array('accesskey' => 'c')));
    }

    public function __viewNew()
    {
        $this->__form();
    }

    public function __viewEdit()
    {
        $this->__form();
    }

    public function __viewInfo()
    {
        $this->__form(true);
    }

    public function __form($readonly = false)
    {
        $formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

        if ($formHasErrors) {
            $this->pageAlert(
                __('An error occurred while processing this form. See below for details.'),
                Alert::ERROR
            );

            // These alerts are only valid if the form doesn't have errors
        } elseif (isset($this->_context[2])) {
            $time = Widget::Time();

            switch ($this->_context[2]) {
                case 'saved':
                    $message = __('Event updated at %s.', array($time->generate()));
                    break;
                case 'created':
                    $message = __('Event created at %s.', array($time->generate()));
            }

            $this->pageAlert(
                $message
                . ' <a href="' . SYMPHONY_URL . '/blueprints/events/new/" accesskey="c">'
                . __('Create another?')
                . '</a> <a href="' . SYMPHONY_URL . '/blueprints/events/" accesskey="a">'
                . __('View all Events')
                . '</a>',
                Alert::SUCCESS
            );
        }

        $isEditing = ($readonly ? true : false);
        $fields = array("name"=>null, "filters"=>null);
        $about = array("name"=>null);
        $providers = Symphony::ExtensionManager()->getProvidersOf(iProvider::EVENT);

        if (isset($_POST['fields'])) {
            $fields = $_POST['fields'];

            if ($this->_context[0] == 'edit') {
                $isEditing = true;
            }
        } elseif ($this->_context[0] == 'edit' || $this->_context[0] == 'info') {
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

            if (!empty($providers)) {
                foreach ($providers as $providerClass => $provider) {
                    if ($fields['source'] == call_user_func(array($providerClass, 'getClass'))) {
                        $fields = array_merge($fields, $existing->settings());
                        $provided = true;
                        break;
                    }
                }
            }

            if (!$provided) {
                if (isset($existing->eParamFILTERS)) {
                    $fields['filters'] = $existing->eParamFILTERS;
                }
            }
        }

        // Handle name on edited changes, or from reading an edited datasource
        if (isset($about['name'])) {
            $name = $about['name'];
        } elseif (isset($fields['name'])) {
            $name = $fields['name'];
        }

        $this->setPageType('form');
        $this->setTitle(__(($isEditing ? '%1$s &ndash; %2$s &ndash; %3$s' : '%2$s &ndash; %3$s'), array($name, __('Events'), __('Symphony'))));
        $this->appendSubheading(($isEditing ? $about['name'] : __('Untitled')));
        $this->insertBreadcrumbs(array(
            Widget::Anchor(__('Events'), SYMPHONY_URL . '/blueprints/events/'),
        ));

        if (!$readonly) {
            $fieldset = new XMLElement('fieldset');
            $fieldset->setAttribute('class', 'settings');
            $fieldset->appendChild(new XMLElement('legend', __('Essentials')));

            // Target
            $sources = new XMLElement('div', null, array('class' => 'apply actions'));
            $div = new XMLElement('div');
            $label = Widget::Label(__('Target'), null, 'apply-label-left');
            $sources->appendChild($label);
            $sources->appendChild($div);

            $sections = SectionManager::fetch(null, 'ASC', 'name');
            $options = array();
            $section_options = array();
            $source = isset($fields['source']) ? $fields['source'] : null;

            if (is_array($sections) && !empty($sections)) {
                $section_options = array('label' => __('Sections'), 'options' => array());

                foreach ($sections as $s) {
                    $section_options['options'][] = array($s->get('id'), $source == $s->get('id'), General::sanitize($s->get('name')));
                }
            }

            $options[] = $section_options;

            // Loop over the event providers
            if (!empty($providers)) {
                $p = array('label' => __('From extensions'), 'options' => array());

                foreach ($providers as $providerClass => $provider) {
                    $p['options'][] = array(
                        $providerClass, ($fields['source'] == $providerClass), $provider
                    );
                }

                $options[] = $p;
            }

            $div->appendChild(
                Widget::Select('source', $options, array('id' => 'event-context'))
            );

            if (isset($this->_errors['source'])) {
                $this->Context->prependChild(Widget::Error($sources, $this->_errors['source']));
            } else {
                $this->Context->prependChild($sources);
            }

            $this->Form->appendChild(
                Widget::Input('fields[source]', $options[0]['options'][0][0], 'hidden', array('id' => 'event-source'))
            );

            // Name
            $group = new XMLElement('div');
            $label = Widget::Label(__('Name'));
            $label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));

            $div = new XMLElement('div');
            $div->setAttribute('class', 'column');

            if (isset($this->_errors['name'])) {
                $div->appendChild(Widget::Error($label, $this->_errors['name']));
            } else {
                $div->appendChild($label);
            }
            $group->appendChild($div);
            $fieldset->appendChild($group);
            $this->Form->appendChild($fieldset);

            // Filters
            $fieldset = new XMLElement('fieldset');
            $fieldset->setAttribute('class', 'settings pickable');
            $fieldset->appendChild(new XMLElement('legend', __('Filters')));
            $p = new XMLElement('p', __('Event Filters add additional conditions or actions to an event.'));
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

            $fieldset->appendChild(Widget::Select('fields[filters][]', $options, array('multiple' => 'multiple', 'id' => 'event-filters')));
            $this->Form->appendChild($fieldset);

            // Connections
            $fieldset = new XMLElement('fieldset');
            $fieldset->setAttribute('class', 'settings');
            $fieldset->appendChild(new XMLElement('legend', __('Attach to Pages')));
            $p = new XMLElement('p', __('The event will only be available on the selected pages.'));
            $p->setAttribute('class', 'help');
            $fieldset->appendChild($p);

            $div = new XMLElement('div');
            $label = Widget::Label(__('Pages'));

            $pages = PageManager::fetch();
            $event_handle = str_replace('-', '_', Lang::createHandle($fields['name']));
            $connections = ResourceManager::getAttachedPages(ResourceManager::RESOURCE_TYPE_EVENT, $event_handle);
            $selected = array();

            foreach ($connections as $connection) {
                $selected[] = $connection['id'];
            }

            $options = array();

            foreach ($pages as $page) {
                $options[] = array($page['id'], in_array($page['id'], $selected), PageManager::resolvePageTitle($page['id']));
            }

            $label->appendChild(Widget::Select('fields[connections][]', $options, array('multiple' => 'multiple')));
            $div->appendChild($label);

            $fieldset->appendChild($div);
            $this->Form->appendChild($fieldset);

            // Providers
            if (!empty($providers)) {
                foreach ($providers as $providerClass => $provider) {
                    if ($isEditing && $fields['source'] !== call_user_func(array($providerClass, 'getSource'))) {
                        continue;
                    }

                    call_user_func_array(array($providerClass, 'buildEditor'), array($this->Form, &$this->_errors, $fields, $handle));
                }
            }
        } else {
            // Author
            if (isset($about['author']['website'])) {
                $link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));
            } elseif (isset($about['author']['email'])) {
                $link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);
            } else {
                $link = $about['author']['name'];
            }

            if ($link) {
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

            if (preg_match('/^\d+(\.\d+)*$/', $version)) {
                $fieldset->appendChild(
                    new XMLElement('p', __('%1$s released on %2$s', array($version, DateTimeObj::format($release_date, __SYM_DATE_FORMAT__))))
                );
            } elseif (!is_null($version)) {
                $fieldset->appendChild(
                    new XMLElement('p', __('Created by %1$s at %2$s', array($version, DateTimeObj::format($release_date, __SYM_DATE_FORMAT__))))
                );
            } else {
                $fieldset->appendChild(
                    new XMLElement('p', __('Last modified on %s', array(DateTimeObj::format($release_date, __SYM_DATE_FORMAT__))))
                );
            }
            $this->Form->appendChild($fieldset);
        }

        // If we are editing an event, it assumed that the event has documentation
        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('id', 'event-documentation');
        $fieldset->setAttribute('class', 'settings');

        if ($isEditing && method_exists($existing, 'documentation')) {
            $doc = $existing->documentation();

            if ($doc) {
                $fieldset->setValue(
                    '<legend>' . __('Documentation') . '</legend>' . PHP_EOL .
                    General::tabsToSpaces(is_object($doc) ? $doc->generate(true, 4) : $doc)
                );
            }
        }

        $this->Form->appendChild($fieldset);

        $div = new XMLElement('div');
        $div->setAttribute('class', 'actions');
        $div->appendChild(Widget::Input('action[save]', ($isEditing ? __('Save Changes') : __('Create Event')), 'submit', array('accesskey' => 's')));

        if ($isEditing) {
            $button = new XMLElement('button', __('Delete'));
            $button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this event'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this event?')));
            $div->appendChild($button);
        }

        if (!$readonly) {
            $this->Form->appendChild($div);
        }
    }

    public function __actionNew()
    {
        if (array_key_exists('save', $_POST['action'])) {
            return $this->__formAction();
        }
    }

    public function __actionEdit()
    {
        if (array_key_exists('save', $_POST['action'])) {
            return $this->__formAction();
        } elseif (array_key_exists('delete', $_POST['action'])) {
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

            if (!General::deleteFile(EVENTS . '/event.' . $this->_context[1] . '.php')) {
                $this->pageAlert(
                    __('Failed to delete %s.', array('<code>' . $this->_context[1] . '</code>'))
                    . ' ' . __('Please check permissions on %s.', array('<code>/workspace/events</code>')),
                    Alert::ERROR
                );
            } else {
                $pages = ResourceManager::getAttachedPages(ResourceManager::RESOURCE_TYPE_EVENT, $this->_context[1]);

                foreach ($pages as $page) {
                    ResourceManager::detach(ResourceManager::RESOURCE_TYPE_EVENT, $this->_context[1], $page['id']);
                }

                redirect(SYMPHONY_URL . '/blueprints/events/');
            }
        }
    }

    public function __actionIndex($resource_type)
    {
        return parent::__actionIndex(ResourceManager::RESOURCE_TYPE_EVENT);
    }

    public function __formAction()
    {
        $fields = $_POST['fields'];
        $this->_errors = array();
        $providers = Symphony::ExtensionManager()->getProvidersOf(iProvider::EVENT);
        $providerClass = null;

        if (trim($fields['name']) == '') {
            $this->_errors['name'] = __('This is a required field');
        }

        if (trim($fields['source']) == '') {
            $this->_errors['source'] = __('This is a required field');
        }

        $filters = isset($fields['filters']) ? $fields['filters'] : array();

        // See if a Provided Datasource is saved
        if (!empty($providers)) {
            foreach ($providers as $providerClass => $provider) {
                if ($fields['source'] == call_user_func(array($providerClass, 'getSource'))) {
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
        if (empty($classname) && !isset($this->_errors['name'])) {
            $this->_errors['name'] = __('Please ensure name contains at least one Latin-based character.', array($classname));
        }

        $file = EVENTS . '/event.' . $classname . '.php';
        $isDuplicate = false;
        $queueForDeletion = null;

        if ($this->_context[0] == 'new' && is_file($file)) {
            $isDuplicate = true;
        } elseif ($this->_context[0] == 'edit') {
            $existing_handle = $this->_context[1];

            if ($classname != $existing_handle && is_file($file)) {
                $isDuplicate = true;
            } elseif ($classname != $existing_handle) {
                $queueForDeletion = EVENTS . '/event.' . $existing_handle . '.php';
            }
        }

        // Duplicate
        if ($isDuplicate) {
            $this->_errors['name'] = __('An Event with the name %s already exists', array('<code>' . $classname . '</code>'));
        }

        if (empty($this->_errors)) {
            $source = $fields['source'];
            $params = array(
                'rootelement' => $rootelement,
            );

            $about = array(
                'name' => $fields['name'],
                'version' => 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'),
                'release date' => DateTimeObj::getGMT('c'),
                'author name' => Symphony::Author()->getFullName(),
                'author website' => URL,
                'author email' => Symphony::Author()->get('email')
            );

            // If there is a provider, get their template
            if ($providerClass) {
                $eventShell = file_get_contents(call_user_func(array($providerClass, 'getTemplate')));
            } else {
                $eventShell = file_get_contents($this->getTemplate('blueprints.event'));
                $about['trigger condition'] = $rootelement;
            }

            $this->__injectAboutInformation($eventShell, $about);

            // Replace the name
            $eventShell = str_replace('<!-- CLASS NAME -->', $classname, $eventShell);

            // Build the templates
            if ($providerClass) {
                $eventShell = call_user_func(array($providerClass, 'prepare'), $fields, $params, $eventShell);
            } else {
                $this->__injectFilters($eventShell, $filters);

                // Add Documentation
                $ajaxEventDoc = new contentAjaxEventDocumentation();
                $doc_parts = array();

                // Add Documentation (Success/Failure)
                $ajaxEventDoc->addEntrySuccessDoc($doc_parts, $rootelement, $filters);
                $ajaxEventDoc->addEntryFailureDoc($doc_parts, $rootelement, $filters);

                // Filters
                $ajaxEventDoc->addDefaultFiltersDoc($doc_parts, $rootelement, $filters);

                // Frontend Markup
                $ajaxEventDoc->addFrontendMarkupDoc($doc_parts, $rootelement, $fields['source'], $filters);
                $ajaxEventDoc->addSendMailFilterDoc($doc_parts, $filters);

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
                 * @param string $rootelment
                 *  The name of this event, as a handle.
                 */
                Symphony::ExtensionManager()->notifyMembers('AppendEventFilterDocumentation', '/blueprints/events/', array(
                    'selected' => $filters,
                    'documentation' => &$doc_parts,
                    'rootelement' => $rootelement
                ));

                $documentation = join(PHP_EOL, array_map(create_function('$x', 'return rtrim($x->generate(true, 4));'), $doc_parts));
                $documentation = str_replace('\'', '\\\'', $documentation);

                $eventShell = str_replace('<!-- CLASS EXTENDS -->', $extends, $eventShell);
                $eventShell = str_replace('<!-- DOCUMENTATION -->', General::tabsToSpaces($documentation, 4), $eventShell);
            }

            $eventShell = str_replace('<!-- ROOT ELEMENT -->', $rootelement, $eventShell);
            $eventShell = str_replace('<!-- CLASS NAME -->', $classname, $eventShell);
            $eventShell = str_replace('<!-- SOURCE -->', $source, $eventShell);

            // Remove left over placeholders
            $eventShell = preg_replace(array('/<!--[\w ]++-->/'), '', $eventShell);

            if ($this->_context[0] == 'new') {
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
            } else {
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
            if (!is_writable(dirname($file)) || !General::writeFile($file, $eventShell, Symphony::Configuration()->get('write_mode', 'file'))) {
                $this->pageAlert(
                    __('Failed to write Event to disk.')
                    . ' ' . __('Please check permissions on %s.', array('<code>/workspace/events</code>')),
                    Alert::ERROR
                );

                // Write successful
            } else {
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($file, true);
                }

                // Attach this event to pages
                $connections = $fields['connections'];
                ResourceManager::setPages(ResourceManager::RESOURCE_TYPE_EVENT, is_null($existing_handle) ? $classname : $existing_handle, $connections);

                if ($queueForDeletion) {
                    General::deleteFile($queueForDeletion);

                    $pages = PageManager::fetch(false, array('events', 'id'), array("
                        `events` REGEXP '[[:<:]]" . $existing_handle . "[[:>:]]'
                    "));

                    if (is_array($pages) && !empty($pages)) {
                        foreach ($pages as $page) {
                            $page['events'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['events']);

                            PageManager::edit($page['id'], $page);
                        }
                    }
                }

                if ($this->_context[0] == 'new') {
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
                } else {
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

    public function __injectFilters(&$shell, $elements)
    {
        if (!is_array($elements) || empty($elements)) {
            return;
        }

        $shell = str_replace('<!-- FILTERS -->', "'" . implode("'," . PHP_EOL . "\t\t\t\t'", $elements) . "'", $shell);
    }

    public function __injectAboutInformation(&$shell, $details)
    {
        if (!is_array($details) || empty($details)) {
            return;
        }

        foreach ($details as $key => $val) {
            $shell = str_replace('<!-- ' . strtoupper($key) . ' -->', addslashes($val), $shell);
        }
    }
}
