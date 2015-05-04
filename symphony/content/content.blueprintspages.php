<?php

/**
 * @package content
 */

/**
 * Developers can create new Frontend pages from this class. It provides
 * an index view of all the pages in this Symphony install as well as the
 * forms for the creation/editing of a Page
 */

class contentBlueprintsPages extends AdministrationPage
{
    public $_errors = array();
    protected $_hilights = array();

    public function insertBreadcrumbsUsingPageIdentifier($page_id, $preserve_last = true)
    {
        if ($page_id == 0) {
            return parent::insertBreadcrumbs(
                array(Widget::Anchor(__('Pages'), SYMPHONY_URL . '/blueprints/pages/'))
            );
        }

        $pages = PageManager::resolvePage($page_id, 'handle');

        foreach ($pages as &$page) {
            // If we are viewing the Page Editor, the Breadcrumbs should link
            // to the parent's Page Editor.
            if ($this->_context[0] == 'edit') {
                $page = Widget::Anchor(
                    PageManager::fetchTitleFromHandle($page),
                    SYMPHONY_URL . '/blueprints/pages/edit/' . PageManager::fetchIDFromHandle($page) . '/'
                );

                // If the pages index is nested, the Breadcrumb should link to the
                // Pages Index filtered by parent
            } elseif (Symphony::Configuration()->get('pages_table_nest_children', 'symphony') == 'yes') {
                $page = Widget::Anchor(
                    PageManager::fetchTitleFromHandle($page),
                    SYMPHONY_URL . '/blueprints/pages/?parent=' . PageManager::fetchIDFromHandle($page)
                );

                // If there is no nesting on the Pages Index, the breadcrumb is
                // not a link, just plain text
            } else {
                $page = new XMLElement('span', PageManager::fetchTitleFromHandle($page));
            }
        }

        if (!$preserve_last) {
            array_pop($pages);
        }

        parent::insertBreadcrumbs(array_merge(
            array(Widget::Anchor(__('Pages'), SYMPHONY_URL . '/blueprints/pages/')),
            $pages
        ));
    }

    public function __viewIndex()
    {
        $this->setPageType('table');
        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Pages'), __('Symphony'))));

        $nesting = Symphony::Configuration()->get('pages_table_nest_children', 'symphony') == 'yes';

        if ($nesting && isset($_GET['parent']) && is_numeric($_GET['parent'])) {
            $parent = PageManager::fetchPageByID((int)$_GET['parent'], array('title', 'id'));
        }

        $this->appendSubheading(isset($parent) ? $parent['title'] : __('Pages'), Widget::Anchor(
            __('Create New'), Administration::instance()->getCurrentPageURL() . 'new/' . ($nesting && isset($parent) ? "?parent={$parent['id']}" : null),
            __('Create a new page'), 'create button', null, array('accesskey' => 'c')
        ));

        if (isset($parent)) {
            $this->insertBreadcrumbsUsingPageIdentifier($parent['id'], false);
        }

        $aTableHead = array(
            array(__('Name'), 'col'),
            array(__('Template'), 'col'),
            array('<abbr title="' . __('Universal Resource Locator') . '">' . __('URL') . '</abbr>', 'col'),
            array(__('Parameters'), 'col'),
            array(__('Type'), 'col')
        );
        $aTableBody = array();

        if ($nesting) {
            $aTableHead[] = array(__('Children'), 'col');
            $where = array(
                'parent ' . (isset($parent) ? " = {$parent['id']} " : ' IS NULL ')
            );
        } else {
            $where = array();
        }

        $pages = PageManager::fetch(true, array('*'), $where);

        if (!is_array($pages) || empty($pages)) {
            $aTableBody = array(Widget::TableRow(array(
                Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead))
            ), 'odd'));

        } else {
            foreach ($pages as $page) {
                $class = array();

                $page_title = ($nesting ? $page['title'] : PageManager::resolvePageTitle($page['id']));
                $page_url = URL . '/' . PageManager::resolvePagePath($page['id']) . '/';
                $page_edit_url = Administration::instance()->getCurrentPageURL() . 'edit/' . $page['id'] . '/';
                $page_template = PageManager::createFilePath($page['path'], $page['handle']);

                $col_title = Widget::TableData(Widget::Anchor($page_title, $page_edit_url, $page['handle']));
                $col_title->appendChild(Widget::Label(__('Select Page %s', array($page_title)), null, 'accessible', null, array(
                    'for' => 'page-' . $page['id']
                )));
                $col_title->appendChild(Widget::Input('items['.$page['id'].']', 'on', 'checkbox', array(
                    'id' => 'page-' . $page['id']
                )));

                $col_template = Widget::TableData($page_template . '.xsl');

                $col_url = Widget::TableData(Widget::Anchor($page_url, $page_url));

                if ($page['params']) {
                    $col_params = Widget::TableData(trim($page['params'], '/'));

                } else {
                    $col_params = Widget::TableData(__('None'), 'inactive');
                }

                if (!empty($page['type'])) {
                    $col_types = Widget::TableData(implode(', ', $page['type']));

                } else {
                    $col_types = Widget::TableData(__('None'), 'inactive');
                }

                if (in_array($page['id'], $this->_hilights)) {
                    $class[] = 'failed';
                }

                $columns = array($col_title, $col_template, $col_url, $col_params, $col_types);

                if ($nesting) {
                    if (PageManager::hasChildPages($page['id'])) {
                        $col_children = Widget::TableData(
                            Widget::Anchor(PageManager::getChildPagesCount($page['id']) . ' &rarr;',
                            SYMPHONY_URL . '/blueprints/pages/?parent=' . $page['id'])
                        );
                    } else {
                        $col_children = Widget::TableData(__('None'), 'inactive');
                    }

                    $columns[] = $col_children;
                }

                $aTableBody[] = Widget::TableRow(
                    $columns,
                    implode(' ', $class)
                );
            }
        }

        $table = Widget::Table(
            Widget::TableHead($aTableHead), null,
            Widget::TableBody($aTableBody), 'orderable selectable',
            null, array('role' => 'directory', 'aria-labelledby' => 'symphony-subheading', 'data-interactive' => 'data-interactive')
        );

        $this->Form->appendChild($table);

        $version = new XMLElement('p', 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'), array(
            'id' => 'version'
        ));
        $this->Form->appendChild($version);

        $tableActions = new XMLElement('div');
        $tableActions->setAttribute('class', 'actions');

        $options = array(
            array(null, false, __('With Selected...')),
            array('delete', false, __('Delete'), 'confirm', null, array(
                'data-message' => __('Are you sure you want to delete the selected pages?')
            ))
        );

        /**
         * Allows an extension to modify the existing options for this page's
         * With Selected menu. If the `$options` parameter is an empty array,
         * the 'With Selected' menu will not be rendered.
         *
         * @delegate AddCustomActions
         * @since Symphony 2.3.2
         * @param string $context
         * '/blueprints/pages/'
         * @param array $options
         *  An array of arrays, where each child array represents an option
         *  in the With Selected menu. Options should follow the same format
         *  expected by `Widget::__SelectBuildOption`. Passed by reference.
         */
        Symphony::ExtensionManager()->notifyMembers('AddCustomActions', '/blueprints/pages/', array(
            'options' => &$options
        ));

        if (!empty($options)) {
            $tableActions->appendChild(Widget::Apply($options));
            $this->Form->appendChild($tableActions);
        }
    }

    public function __viewNew()
    {
        $this->__viewEdit();
    }

    public function __viewEdit()
    {
        $this->setPageType('form');
        $fields = array("title"=>null, "handle"=>null, "parent"=>null, "params"=>null, "type"=>null, "data_sources"=>null);
        $existing = $fields;

        $nesting = (Symphony::Configuration()->get('pages_table_nest_children', 'symphony') == 'yes');

        // Verify page exists:
        if ($this->_context[0] === 'edit') {
            if (!$page_id = (int)$this->_context[1]) {
                redirect(SYMPHONY_URL . '/blueprints/pages/');
            }

            $existing = PageManager::fetchPageByID($page_id);

            if (!$existing) {
                Administration::instance()->errorPageNotFound();
            } else {
                $existing['type'] = PageManager::fetchPageTypes($page_id);
            }
        }

        // Status message:
        if (isset($this->_context[2])) {
            $flag = $this->_context[2];
            $parent_link_suffix = $message = '';
            $time = Widget::Time();

            if (isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])) {
                $parent_link_suffix = "?parent=" . $_REQUEST['parent'];
            } elseif ($nesting && isset($existing) && !is_null($existing['parent'])) {
                $parent_link_suffix = '?parent=' . $existing['parent'];
            }

            switch ($flag) {
                case 'saved':
                    $message = __('Page updated at %s.', array($time->generate()));
                    break;
                case 'created':
                    $message = __('Page created at %s.', array($time->generate()));
            }

            $this->pageAlert(
                $message
                . ' <a href="' . SYMPHONY_URL . '/blueprints/pages/new/' . $parent_link_suffix . '" accesskey="c">'
                . __('Create another?')
                . '</a> <a href="' . SYMPHONY_URL . '/blueprints/pages/" accesskey="a">'
                . __('View all Pages')
                . '</a>',
                Alert::SUCCESS
            );
        }

        // Find values:
        if (isset($_POST['fields'])) {
            $fields = $_POST['fields'];
        } elseif ($this->_context[0] == 'edit') {
            $fields = $existing;

            if (!is_null($fields['type'])) {
                $fields['type'] = implode(', ', $fields['type']);
            }

            $fields['data_sources'] = preg_split('/,/i', $fields['data_sources'], -1, PREG_SPLIT_NO_EMPTY);
            $fields['events'] = preg_split('/,/i', $fields['events'], -1, PREG_SPLIT_NO_EMPTY);
        } elseif (isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])) {
            $fields['parent'] = $_REQUEST['parent'];
        }

        $title = $fields['title'];

        if (trim($title) == '') {
            $title = $existing['title'];
        }

        $this->setTitle(__(
            ($title ? '%1$s &ndash; %2$s &ndash; %3$s' : '%2$s &ndash; %3$s'),
            array(
                $title,
                __('Pages'),
                __('Symphony')
            )
        ));

        $page_id = isset($page_id) ? $page_id : null;

        if (!empty($title)) {
            $page_url = URL . '/' . PageManager::resolvePagePath($page_id) . '/';

            $this->appendSubheading($title, array(
                Widget::Anchor(__('View Page'), $page_url, __('View Page on Frontend'), 'button', null, array('target' => '_blank', 'accesskey' => 'v'))
            ));
        } else {
            $this->appendSubheading(!empty($title) ? $title : __('Untitled'));
        }

        if (isset($page_id)) {
            $this->insertBreadcrumbsUsingPageIdentifier($page_id, false);
        } else {
            $_GET['parent'] = isset($_GET['parent']) ? $_GET['parent'] : null;
            $this->insertBreadcrumbsUsingPageIdentifier((int)$_GET['parent'], true);
        }

        // Title --------------------------------------------------------------

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Page Settings')));

        $label = Widget::Label(__('Name'));
        $label->appendChild(Widget::Input(
            'fields[title]', General::sanitize($fields['title'])
        ));

        if (isset($this->_errors['title'])) {
            $label = Widget::Error($label, $this->_errors['title']);
        }

        $fieldset->appendChild($label);

        // Handle -------------------------------------------------------------

        $group = new XMLElement('div');
        $group->setAttribute('class', 'two columns');
        $column = new XMLElement('div');
        $column->setAttribute('class', 'column');

        $label = Widget::Label(__('Handle'));
        $label->appendChild(Widget::Input(
            'fields[handle]', $fields['handle']
        ));

        if (isset($this->_errors['handle'])) {
            $label = Widget::Error($label, $this->_errors['handle']);
        }

        $column->appendChild($label);

        // Parent ---------------------------------------------------------

        $label = Widget::Label(__('Parent Page'));

        $where = array(
            sprintf('id != %d', $page_id)
        );
        $pages = PageManager::fetch(false, array('id'), $where, 'title ASC');

        $options = array(
            array('', false, '/')
        );

        if (!empty($pages)) {
            foreach ($pages as $page) {
                $options[] = array(
                    $page['id'], $fields['parent'] == $page['id'],
                    '/' . PageManager::resolvePagePath($page['id'])
                );
            }

            usort($options, array($this, '__compare_pages'));
        }

        $label->appendChild(Widget::Select(
            'fields[parent]', $options
        ));
        $column->appendChild($label);
        $group->appendChild($column);

        // Parameters ---------------------------------------------------------

        $column = new XMLElement('div');
        $column->setAttribute('class', 'column');

        $label = Widget::Label(__('Parameters'));
        $label->appendChild(Widget::Input(
            'fields[params]', $fields['params'], 'text', array('placeholder' => 'param1/param2')
        ));
        $column->appendChild($label);

        // Type -----------------------------------------------------------

        $label = Widget::Label(__('Type'));
        $label->appendChild(Widget::Input('fields[type]', $fields['type']));

        if (isset($this->_errors['type'])) {
            $label = Widget::Error($label, $this->_errors['type']);
        }

        $column->appendChild($label);

        $tags = new XMLElement('ul');
        $tags->setAttribute('class', 'tags');
        $tags->setAttribute('data-interactive', 'data-interactive');

        $types = PageManager::fetchAvailablePageTypes();

        foreach ($types as $type) {
            $tags->appendChild(new XMLElement('li', $type));
        }

        $column->appendChild($tags);
        $group->appendChild($column);
        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);

        // Events -------------------------------------------------------------

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Page Resources')));

        $group = new XMLElement('div');
        $group->setAttribute('class', 'two columns');

        $label = Widget::Label(__('Events'));
        $label->setAttribute('class', 'column');

        $events = ResourceManager::fetch(ResourceManager::RESOURCE_TYPE_EVENT, array(), array(), 'name ASC');
        $options = array();

        if (is_array($events) && !empty($events)) {
            if (!isset($fields['events'])) {
                $fields['events'] = array();
            }

            foreach ($events as $name => $about) {
                $options[] = array(
                    $name, in_array($name, $fields['events']), $about['name']
                );
            }
        }

        $label->appendChild(Widget::Select('fields[events][]', $options, array('multiple' => 'multiple')));
        $group->appendChild($label);

        // Data Sources -------------------------------------------------------

        $label = Widget::Label(__('Data Sources'));
        $label->setAttribute('class', 'column');

        $datasources = ResourceManager::fetch(ResourceManager::RESOURCE_TYPE_DS, array(), array(), 'name ASC');
        $options = array();

        if (is_array($datasources) && !empty($datasources)) {
            if (!isset($fields['data_sources'])) {
                $fields['data_sources'] = array();
            }

            foreach ($datasources as $name => $about) {
                $options[] = array(
                    $name, in_array($name, $fields['data_sources']), $about['name']
                );
            }
        }

        $label->appendChild(Widget::Select('fields[data_sources][]', $options, array('multiple' => 'multiple')));
        $group->appendChild($label);
        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);

        // Controls -----------------------------------------------------------

        /**
         * After all Page related Fields have been added to the DOM, just before the
         * actions.
         *
         * @delegate AppendPageContent
         * @param string $context
         *  '/blueprints/pages/'
         * @param XMLElement $form
         * @param array $fields
         * @param array $errors
         */
        Symphony::ExtensionManager()->notifyMembers(
            'AppendPageContent',
            '/blueprints/pages/',
            array(
                'form'        => &$this->Form,
                'fields'    => &$fields,
                'errors'    => $this->_errors
            )
        );

        $div = new XMLElement('div');
        $div->setAttribute('class', 'actions');
        $div->appendChild(Widget::Input(
            'action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create Page')),
            'submit', array('accesskey' => 's')
        ));

        if ($this->_context[0] == 'edit') {
            $button = new XMLElement('button', __('Delete'));
            $button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this page'), 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this page?')));
            $div->appendChild($button);
        }

        $this->Form->appendChild($div);

        if (isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])) {
            $this->Form->appendChild(new XMLElement('input', null, array('type' => 'hidden', 'name' => 'parent', 'value' => $_REQUEST['parent'])));
        }
    }

    public function __compare_pages($a, $b)
    {
        return strnatcasecmp($a[2], $b[2]);
    }

    public function __actionIndex()
    {
        $checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

        if (is_array($checked) && !empty($checked)) {
            /**
             * Extensions can listen for any custom actions that were added
             * through `AddCustomPreferenceFieldsets` or `AddCustomActions`
             * delegates.
             *
             * @delegate CustomActions
             * @since Symphony 2.3.2
             * @param string $context
             *  '/blueprints/pages/'
             * @param array $checked
             *  An array of the selected rows. The value is usually the ID of the
             *  the associated object.
             */
            Symphony::ExtensionManager()->notifyMembers('CustomActions', '/blueprints/pages/', array(
                'checked' => $checked
            ));

            switch ($_POST['with-selected']) {
                case 'delete':
                    $this->__actionDelete($checked, SYMPHONY_URL . '/blueprints/pages/');
                    break;
            }
        }
    }

    public function __actionTemplate()
    {
        $filename = $this->_context[1] . '.xsl';
        $file_abs = PAGES . '/' . $filename;
        $fields = $_POST['fields'];
        $this->_errors = array();

        if (!isset($fields['body']) || trim($fields['body']) == '') {
            $this->_errors['body'] = __('This is a required field.');
        } elseif (!General::validateXML($fields['body'], $errors, false, new XSLTProcess())) {
            $this->_errors['body'] = __('This document is not well formed.') . ' ' . __('The following error was returned:') . ' <code>' . $errors[0]['message'] . '</code>';
        }

        if (empty($this->_errors)) {
            /**
             * Just before a Page Template is about to written to disk
             *
             * @delegate PageTemplatePreEdit
             * @since Symphony 2.2.2
             * @param string $context
             * '/blueprints/pages/template/'
             * @param string $file
             *  The path to the Page Template file
             * @param string $contents
             *  The contents of the `$fields['body']`, passed by reference
             */
            Symphony::ExtensionManager()->notifyMembers('PageTemplatePreEdit', '/blueprints/pages/template/', array('file' => $file_abs, 'contents' => &$fields['body']));

            if (!PageManager::writePageFiles($file_abs, $fields['body'])) {
                $this->pageAlert(
                    __('Page Template could not be written to disk.')
                    . ' ' . __('Please check permissions on %s.', array('<code>/workspace/pages</code>')),
                    Alert::ERROR
                );

            } else {
                /**
                 * Just after a Page Template has been edited and written to disk
                 *
                 * @delegate PageTemplatePostEdit
                 * @since Symphony 2.2.2
                 * @param string $context
                 * '/blueprints/pages/template/'
                 * @param string $file
                 *  The path to the Page Template file
                 */
                Symphony::ExtensionManager()->notifyMembers('PageTemplatePostEdit', '/blueprints/pages/template/', array('file' => $file_abs));

                redirect(SYMPHONY_URL . '/blueprints/pages/template/' . $this->_context[1] . '/saved/');
            }
        }
    }

    public function __actionNew()
    {
        $this->__actionEdit();
    }

    public function __actionEdit()
    {
        if ($this->_context[0] != 'new' && !$page_id = (integer)$this->_context[1]) {
            redirect(SYMPHONY_URL . '/blueprints/pages/');
        }

        $parent_link_suffix = null;

        if (isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])) {
            $parent_link_suffix = '?parent=' . $_REQUEST['parent'];
        }

        if (@array_key_exists('delete', $_POST['action'])) {
            $this->__actionDelete($page_id, SYMPHONY_URL  . '/blueprints/pages/' . $parent_link_suffix);
        }

        if (@array_key_exists('save', $_POST['action'])) {

            $fields = $_POST['fields'];
            $this->_errors = array();
            $autogenerated_handle = false;

            if (!isset($fields['title']) || trim($fields['title']) == '') {
                $this->_errors['title'] = __('This is a required field');
            }

            if (trim($fields['type']) != '' && preg_match('/(index|404|403)/i', $fields['type'])) {
                $types = preg_split('/\s*,\s*/', strtolower($fields['type']), -1, PREG_SPLIT_NO_EMPTY);

                if (in_array('index', $types) && PageManager::hasPageTypeBeenUsed($page_id, 'index')) {
                    $this->_errors['type'] = __('An index type page already exists.');

                } elseif (in_array('404', $types) && PageManager::hasPageTypeBeenUsed($page_id, '404')) {
                    $this->_errors['type'] = __('A 404 type page already exists.');

                } elseif (in_array('403', $types) && PageManager::hasPageTypeBeenUsed($page_id, '403')) {
                    $this->_errors['type'] = __('A 403 type page already exists.');
                }
            }

            if (trim($fields['handle']) == '') {
                $fields['handle'] = $fields['title'];
                $autogenerated_handle = true;
            }

            $fields['handle'] = PageManager::createHandle($fields['handle']);

            if (empty($fields['handle']) && !isset($this->_errors['title'])) {
                $this->_errors['handle'] = __('Please ensure handle contains at least one Latin-based character.');
            }

            /**
             * Just after the Symphony validation has run, allows Developers
             * to run custom validation logic on a Page
             *
             * @delegate PagePostValidate
             * @since Symphony 2.2
             * @param string $context
             * '/blueprints/pages/'
             * @param array $fields
             *  The `$_POST['fields']` array. This should be read-only and not changed
             *  through this delegate.
             * @param array $errors
             *  An associative array of errors, with the key matching a key in the
             *  `$fields` array, and the value being the string of the error. `$errors`
             *  is passed by reference.
             */
            Symphony::ExtensionManager()->notifyMembers('PagePostValidate', '/blueprints/pages/', array('fields' => $fields, 'errors' => &$errors));

            if (empty($this->_errors)) {
                $autogenerated_handle = false;

                if ($fields['params']) {
                    $fields['params'] = trim(preg_replace('@\/{2,}@', '/', $fields['params']), '/');
                }

                // Clean up type list
                $types = preg_split('/\s*,\s*/', $fields['type'], -1, PREG_SPLIT_NO_EMPTY);
                $types = @array_map('trim', $types);
                unset($fields['type']);

                $fields['parent'] = ($fields['parent'] != __('None') ? $fields['parent'] : null);
                $fields['data_sources'] = is_array($fields['data_sources']) ? implode(',', $fields['data_sources']) : null;
                $fields['events'] = is_array($fields['events']) ? implode(',', $fields['events']) : null;
                $fields['path'] = null;

                if ($fields['parent']) {
                    $fields['path'] = PageManager::resolvePagePath((integer)$fields['parent']);
                }

                // Check for duplicates:
                $current = PageManager::fetchPageByID($page_id);

                if (empty($current)) {
                    $fields['sortorder'] = PageManager::fetchNextSortOrder();
                }

                $where = array();

                if (!empty($current)) {
                    $where[] = "p.id != {$page_id}";
                }

                $where[] = "p.handle = '" . $fields['handle'] . "'";
                $where[] = (is_null($fields['path']))
                    ? "p.path IS null"
                    : "p.path = '" . $fields['path'] . "'";
                $duplicate = PageManager::fetch(false, array('*'), $where);

                // If duplicate
                if (!empty($duplicate)) {
                    if ($autogenerated_handle) {
                        $this->_errors['title'] = __('A page with that title already exists');
                    } else {
                        $this->_errors['handle'] = __('A page with that handle already exists');
                    }

                    // Create or move files:
                } else {
                    // New page?
                    if (empty($current)) {
                        $file_created = PageManager::createPageFiles(
                            $fields['path'],
                            $fields['handle']
                        );

                        // Existing page, potentially rename files
                    } else {
                        $file_created = PageManager::createPageFiles(
                            $fields['path'],
                            $fields['handle'],
                            $current['path'],
                            $current['handle']
                        );
                    }

                    // If the file wasn't created, it's usually permissions related
                    if (!$file_created) {
                        $redirect = null;
                        return $this->pageAlert(
                            __('Page Template could not be written to disk.')
                            . ' ' . __('Please check permissions on %s.', array('<code>/workspace/pages</code>')),
                            Alert::ERROR
                        );
                    }

                    // Insert the new data:
                    if (empty($current)) {
                        /**
                         * Just prior to creating a new Page record in `tbl_pages`, provided
                         * with the `$fields` associative array. Use with caution, as no
                         * duplicate page checks are run after this delegate has fired
                         *
                         * @delegate PagePreCreate
                         * @since Symphony 2.2
                         * @param string $context
                         * '/blueprints/pages/'
                         * @param array $fields
                         *  The `$_POST['fields']` array passed by reference
                         */
                        Symphony::ExtensionManager()->notifyMembers('PagePreCreate', '/blueprints/pages/', array('fields' => &$fields));

                        if (!$page_id = PageManager::add($fields)) {
                            $this->pageAlert(
                                __('Unknown errors occurred while attempting to save.')
                                . '<a href="' . SYMPHONY_URL . '/system/log/">'
                                . __('Check your activity log')
                                . '</a>.',
                                Alert::ERROR
                            );
                        } else {
                            /**
                             * Just after the creation of a new page in `tbl_pages`
                             *
                             * @delegate PagePostCreate
                             * @since Symphony 2.2
                             * @param string $context
                             * '/blueprints/pages/'
                             * @param integer $page_id
                             *  The ID of the newly created Page
                             * @param array $fields
                             *  An associative array of data that was just saved for this page
                             */
                            Symphony::ExtensionManager()->notifyMembers('PagePostCreate', '/blueprints/pages/', array('page_id' => $page_id, 'fields' => &$fields));

                            $redirect = "/blueprints/pages/edit/{$page_id}/created/{$parent_link_suffix}";
                        }

                        // Update existing:
                    } else {
                        /**
                         * Just prior to updating a Page record in `tbl_pages`, provided
                         * with the `$fields` associative array. Use with caution, as no
                         * duplicate page checks are run after this delegate has fired
                         *
                         * @delegate PagePreEdit
                         * @since Symphony 2.2
                         * @param string $context
                         * '/blueprints/pages/'
                         * @param integer $page_id
                         *  The ID of the Page that is about to be updated
                         * @param array $fields
                         *  The `$_POST['fields']` array passed by reference
                         */
                        Symphony::ExtensionManager()->notifyMembers('PagePreEdit', '/blueprints/pages/', array('page_id' => $page_id, 'fields' => &$fields));

                        if (!PageManager::edit($page_id, $fields, true)) {
                            return $this->pageAlert(
                                __('Unknown errors occurred while attempting to save.')
                                . '<a href="' . SYMPHONY_URL . '/system/log/">'
                                . __('Check your activity log')
                                . '</a>.',
                                Alert::ERROR
                            );
                        } else {
                            /**
                             * Just after updating a page in `tbl_pages`
                             *
                             * @delegate PagePostEdit
                             * @since Symphony 2.2
                             * @param string $context
                             * '/blueprints/pages/'
                             * @param integer $page_id
                             *  The ID of the Page that was just updated
                             * @param array $fields
                             *  An associative array of data that was just saved for this page
                             */
                            Symphony::ExtensionManager()->notifyMembers('PagePostEdit', '/blueprints/pages/', array('page_id' => $page_id, 'fields' => $fields));

                            $redirect = "/blueprints/pages/edit/{$page_id}/saved/{$parent_link_suffix}";
                        }
                    }
                }

                // Only proceed if there was no errors saving/creating the page
                if (empty($this->_errors)) {
                    /**
                     * Just before the page's types are saved into `tbl_pages_types`.
                     * Use with caution as no further processing is done on the `$types`
                     * array to prevent duplicate `$types` from occurring (ie. two index
                     * page types). Your logic can use the PageManger::hasPageTypeBeenUsed
                     * function to perform this logic.
                     *
                     * @delegate PageTypePreCreate
                     * @since Symphony 2.2
                     * @see toolkit.PageManager#hasPageTypeBeenUsed
                     * @param string $context
                     * '/blueprints/pages/'
                     * @param integer $page_id
                     *  The ID of the Page that was just created or updated
                     * @param array $types
                     *  An associative array of the types for this page passed by reference.
                     */
                    Symphony::ExtensionManager()->notifyMembers('PageTypePreCreate', '/blueprints/pages/', array('page_id' => $page_id, 'types' => &$types));

                    // Assign page types:
                    PageManager::addPageTypesToPage($page_id, $types);

                    // Find and update children:
                    if ($this->_context[0] == 'edit') {
                        PageManager::editPageChildren($page_id, $fields['path'] . '/' . $fields['handle']);
                    }

                    if ($redirect) {
                        redirect(SYMPHONY_URL . $redirect);
                    }
                }
            }

            // If there was any errors, either with pre processing or because of a
            // duplicate page, return.
            if (is_array($this->_errors) && !empty($this->_errors)) {
                return $this->pageAlert(
                    __('An error occurred while processing this form. See below for details.'),
                    Alert::ERROR
                );
            }
        }
    }

    public function __actionDelete($pages, $redirect)
    {
        $success = true;
        $deleted_page_ids = array();

        if (!is_array($pages)) {
            $pages = array($pages);
        }

        /**
         * Prior to deleting Pages
         *
         * @delegate PagePreDelete
         * @since Symphony 2.2
         * @param string $context
         * '/blueprints/pages/'
         * @param array $page_ids
         *  An array of Page ID's that are about to be deleted, passed
         *  by reference
         * @param string $redirect
         *  The absolute path that the Developer will be redirected to
         *  after the Pages are deleted
         */
        Symphony::ExtensionManager()->notifyMembers('PagePreDelete', '/blueprints/pages/', array('page_ids' => &$pages, 'redirect' => &$redirect));

        foreach ($pages as $page_id) {
            $page = PageManager::fetchPageByID($page_id);

            if (empty($page)) {
                $success = false;
                $this->pageAlert(
                    __('Page could not be deleted because it does not exist.'),
                    Alert::ERROR
                );

                break;
            }

            if (PageManager::hasChildPages($page_id)) {
                $this->_hilights[] = $page['id'];
                $success = false;
                $this->pageAlert(
                    __('Page could not be deleted because it has children.'),
                    Alert::ERROR
                );

                continue;
            }

            if (!PageManager::deletePageFiles($page['path'], $page['handle'])) {
                $this->_hilights[] = $page['id'];
                $success = false;
                $this->pageAlert(
                    __('One or more pages could not be deleted.')
                    . ' ' . __('Please check permissions on %s.', array('<code>/workspace/pages</code>')),
                    Alert::ERROR
                );

                continue;
            }

            if (PageManager::delete($page_id, false)) {
                $deleted_page_ids[] = $page_id;
            }
        }

        if ($success) {
            /**
             * Fires after all Pages have been deleted
             *
             * @delegate PagePostDelete
             * @since Symphony 2.3
             * @param string $context
             * '/blueprints/pages/'
             * @param array $page_ids
             *  The page ID's that were just deleted
             */
            Symphony::ExtensionManager()->notifyMembers('PagePostDelete', '/blueprints/pages/', array('page_ids' => $deleted_page_ids));
            redirect($redirect);
        }
    }
}
