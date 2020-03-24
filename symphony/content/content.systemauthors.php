<?php

/**
 * @package content
 */

/**
 * Controller page for all Symphony Author related activity
 * including making new Authors, editing Authors or deleting
 * Authors from Symphony
 */

class contentSystemAuthors extends AdministrationPage
{
    public $_Author;

    /**
     * The Authors page has /action/id/flag/ context.
     * eg. /edit/1/saved/
     *
     * @param array $context
     * @param array $parts
     * @return array
     */
    public function parseContext(array &$context, array $parts)
    {
        // Order is important!
        $params = array_fill_keys(array('action', 'id', 'flag'), null);

        if (isset($parts[2])) {
            $extras = preg_split('/\//', $parts[2], -1, PREG_SPLIT_NO_EMPTY);
            list($params['action'], $params['id'], $params['flag']) = array_replace([null,null,null], $extras);
            $params['id'] = (int)$params['id'];
        }

        $context = array_filter($params);
    }

    public function sort(&$sort, &$order, $params)
    {
        $authorQuery = (new AuthorManager)->select();
        if (is_null($sort) || $sort == 'name') {
            $authorQuery
                ->sort('first_name', $order)
                ->sort('last_name', $order);
        } else {
            $authorQuery->sort((string)$sort, $order);
        }

        return $authorQuery->execute()->rows();
    }

    public function isRemoteLoginActionChecked()
    {
        return is_array($_POST['action']) &&
            array_key_exists('remote_login', $_POST['action']) &&
            $_POST['action']['remote_login'] === 'yes';
    }

    public function __viewIndex()
    {
        $this->setPageType('table');
        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Authors'), __('Symphony'))));

        if (Symphony::Author()->isDeveloper() || Symphony::Author()->isManager()) {
            $this->appendSubheading(__('Authors'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a new author'), 'create button', null, array('accesskey' => 'c')));
        } else {
            $this->appendSubheading(__('Authors'));
        }

        Sortable::initialize($this, $authors, $sort, $order);

        $columns = array(
            array(
                'label' => __('Name'),
                'sortable' => true,
                'handle' => 'name'
            ),
            array(
                'label' => __('Email Address'),
                'sortable' => true,
                'handle' => 'email'
            ),
            array(
                'label' => __('Last Seen'),
                'sortable' => true,
                'handle' => 'last_seen'
            )
        );

        if (Symphony::Author()->isDeveloper() || Symphony::Author()->isManager()) {
            $columns = array_merge($columns, array(
                array(
                    'label' => __('User Type'),
                    'sortable' => true,
                    'handle' => 'user_type'
                ),
                array(
                    'label' => __('Language'),
                    'sortable' => true,
                    'handle' => 'language'
                )
            ));
        }

        /**
         * Allows the creation of custom table columns for each author. Called
         * after all the table headers columns have been added.
         *
         * @delegate AddCustomAuthorColumn
         * @since Symphony 2.7.0
         * @param string $context
         * '/system/authors/'
         * @param array $columns
         * An array of the current columns, passed by reference
         * @param string $sort
         *  @since Symphony 3.0.0
         *  The sort field
         * @param string $order
         *  @since Symphony 3.0.0
         *  The sort order
         */
        Symphony::ExtensionManager()->notifyMembers('AddCustomAuthorColumn', '/system/authors/', array(
            'columns' => &$columns,
            'sort' => $sort,
            'order' => $order,
        ));

        $aTableHead = Sortable::buildTableHeaders($columns, $sort, $order, (isset($_REQUEST['filter']) ? '&amp;filter=' . $_REQUEST['filter'] : ''));

        $aTableBody = array();

        if (!is_array($authors) || empty($authors)) {
            $aTableBody = array(
                Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead))), 'odd')
            );
        } else {
            foreach ($authors as $a) {
                // Setup each cell
                if (
                    (Symphony::Author()->isDeveloper() || (Symphony::Author()->isManager() && !$a->isDeveloper() && !$a->isManager()))
                    || Symphony::Author()->get('id') == $a->get('id')
                ) {
                    $td1 = Widget::TableData(
                        Widget::Anchor($a->getFullName(), Administration::instance()->getCurrentPageURL() . 'edit/' . $a->get('id') . '/', $a->get('username'), 'author')
                    );
                } else {
                    $td1 = Widget::TableData($a->getFullName(), 'inactive');
                }

                // Can this Author be edited by the current Author?
                if (Symphony::Author()->isDeveloper() || Symphony::Author()->isManager()) {
                    if ($a->get('id') != Symphony::Author()->get('id')) {
                        $td1->appendChild(Widget::Label(__('Select Author %s', array($a->getFullName())), null, 'accessible', null, array(
                            'for' => 'author-' . $a->get('id')
                        )));
                        $td1->appendChild(Widget::Input('items['.$a->get('id').']', 'on', 'checkbox', array(
                            'id' => 'author-' . $a->get('id')
                        )));
                    }
                }

                $td2 = Widget::TableData(Widget::Anchor($a->get('email'), 'mailto:'.$a->get('email'), __('Email this author')));

                if (!is_null($a->get('last_seen'))) {
                    $td3 = Widget::TableData(
                        DateTimeObj::format($a->get('last_seen'), __SYM_DATETIME_FORMAT__)
                    );
                } else {
                    $td3 = Widget::TableData(__('Unknown'), 'inactive');
                }

                if ($a->isDeveloper()) {
                    $type = 'Developer';
                } elseif ($a->isManager()) {
                    $type = 'Manager';
                } else {
                    $type = 'Author';
                }

                $td4 = Widget::TableData(__($type));

                $languages = Lang::getAvailableLanguages();

                $td5 = Widget::TableData($a->get("language") == null ? __("System Default") : $languages[$a->get("language")]);

                $tableData = array();
                // Add a row to the body array, assigning each cell to the row
                if (Symphony::Author()->isDeveloper() || Symphony::Author()->isManager()) {
                    $tableData = array($td1, $td2, $td3, $td4, $td5);
                } else {
                    $tableData = array($td1, $td2, $td3);
                }

                /**
                 * Allows Extensions to inject custom table data for each Author
                 * into the Authors Index
                 *
                 * @delegate AddCustomAuthorColumnData
                 * @since Symphony 2.7.0
                 * @param string $context
                 * '/system/authors/'
                 * @param array $tableData
                 *  An array of `Widget::TableData`, passed by reference
                 * @param array $columns
                 * An array of the current columns
                 * @param Author $author
                 *  The Author object.
                 */
                Symphony::ExtensionManager()->notifyMembers('AddCustomAuthorColumnData', '/system/authors/', array(
                    'tableData' => &$tableData,
                    'columns' => $columns,
                    'author' => $a,
                ));

                $aTableBody[] = Widget::TableRow($tableData);
            }
        }

        $table = Widget::Table(
            Widget::TableHead($aTableHead),
            null,
            Widget::TableBody($aTableBody),
            'selectable',
            null,
            array('role' => 'directory', 'aria-labelledby' => 'symphony-subheading')
        );

        $this->Form->appendChild($table);

        $version = new XMLElement('p', 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'), array(
            'id' => 'version'
        ));

        $this->Form->appendChild($version);
    }

    // Both the Edit and New pages need the same form
    public function __viewNew()
    {
        $this->__form();
    }

    public function __viewEdit()
    {
        $this->__form();
    }

    public function __form()
    {
        // Handle unknown context
        if (!in_array($this->_context['action'], array('new', 'edit'))) {
            Administration::instance()->errorPageNotFound();
        }

        if ($this->_context['action'] === 'new' && !Symphony::Author()->isDeveloper() && !Symphony::Author()->isManager()) {
            Administration::instance()->throwCustomError(
                __('You are not authorised to access this page.'),
                __('Access Denied'),
                Page::HTTP_STATUS_UNAUTHORIZED
            );
        }

        if (isset($this->_context['flag'])) {
            $time = Widget::Time();

            switch ($this->_context['flag']) {
                case 'saved':
                    $message = __('Author updated at %s.', array($time->generate()));
                    break;
                case 'created':
                    $message = __('Author created at %s.', array($time->generate()));
            }

            $this->pageAlert(
                $message
                . ' <a href="' . SYMPHONY_URL . '/system/authors/new/" accesskey="c">'
                . __('Create another?')
                . '</a> <a href="' . SYMPHONY_URL . '/system/authors/" accesskey="a">'
                . __('View all Authors')
                . '</a>',
                Alert::SUCCESS
            );
        }

        $this->setPageType('form');
        $isOwner = false;
        $isEditing = ($this->_context['action'] === 'edit');
        $canonical_link = null;

        if (isset($_POST['fields'])) {
            $author = $this->_Author;
        } elseif ($isEditing) {
            if (!$author_id = $this->_context['id']) {
                redirect(SYMPHONY_URL . '/system/authors/');
            }

            if (!$author = AuthorManager::fetchByID($author_id)) {
                Administration::instance()->throwCustomError(
                    __('The author profile you requested does not exist.'),
                    __('Author not found'),
                    Page::HTTP_STATUS_NOT_FOUND
                );
            }
            $canonical_link = '/system/authors/edit/' . $author_id . '/';
        } else {
            $author = new Author();
        }

        if ($isEditing && $author->get('id') == Symphony::Author()->get('id')) {
            $isOwner = true;
        }

        if ($isEditing && !$isOwner && !Symphony::Author()->isDeveloper() && !Symphony::Author()->isManager()) {
            Administration::instance()->throwCustomError(
                __('You are not authorised to edit other authors.'),
                __('Access Denied'),
                Page::HTTP_STATUS_FORBIDDEN
            );
        }

        $this->setTitle(
            __(
                $this->_context['action'] === 'new'
                    ? '%2$s &ndash; %3$s'
                    : '%1$s &ndash; %2$s &ndash; %3$s',
                [$author->getFullName(), __('Authors'), __('Symphony')]
            )
        );
        if ($canonical_link) {
            $this->addElementToHead(new XMLElement('link', null, array(
                'rel' => 'canonical',
                'href' => SYMPHONY_URL . $canonical_link,
            )));
        }
        $this->appendSubheading(($this->_context['action'] === 'new' ? __('Untitled') : $author->getFullName()));
        $this->insertBreadcrumbs(array(
            Widget::Anchor(__('Authors'), SYMPHONY_URL . '/system/authors/'),
        ));

        // Essentials
        $group = new XMLElement('fieldset');
        $group->setAttribute('class', 'settings');
        $group->appendChild(new XMLElement('legend', __('Essentials')));

        $div = new XMLElement('div');
        $div->setAttribute('class', 'two columns');

        $label = Widget::Label(__('First Name'), null, 'column');
        $label->appendChild(Widget::Input('fields[first_name]', $author->get('first_name')));
        $div->appendChild((isset($this->_errors['first_name']) ? Widget::Error($label, $this->_errors['first_name']) : $label));


        $label = Widget::Label(__('Last Name'), null, 'column');
        $label->appendChild(Widget::Input('fields[last_name]', $author->get('last_name')));
        $div->appendChild((isset($this->_errors['last_name']) ? Widget::Error($label, $this->_errors['last_name']) : $label));

        $group->appendChild($div);

        $label = Widget::Label(__('Email Address'));
        $label->appendChild(Widget::Input('fields[email]', $author->get('email'), 'text', array('autocomplete' => 'off')));
        $group->appendChild((isset($this->_errors['email']) ? Widget::Error($label, $this->_errors['email']) : $label));

        $this->Form->appendChild($group);

        // Login Details
        $group = new XMLElement('fieldset');
        $group->setAttribute('class', 'settings');
        $group->appendChild(new XMLElement('legend', __('Login Details')));

        $div = new XMLElement('div');

        $label = Widget::Label(__('Username'));
        $label->appendChild(Widget::Input('fields[username]', $author->get('username'), 'text', array('autocomplete' => 'off')));
        $div->appendChild((isset($this->_errors['username']) ? Widget::Error($label, $this->_errors['username']) : $label));

        // Only developers can change the user type. Primary account should NOT be able to change this
        if ((Symphony::Author()->isDeveloper() || Symphony::Author()->isManager()) && !$author->isPrimaryAccount()) {

            // Create columns
            $div->setAttribute('class', 'two columns');
            $label->setAttribute('class', 'column');

            // User type
            $label = Widget::Label(__('User Type'), null, 'column');

            $options = array(
                array('author', false, __('Author')),
            );

            if (Symphony::Author()->isDeveloper() || ($isOwner && $author->isManager())) {
                $options[] = array('manager', $author->isManager(), __('Manager'));
            }

            if (Symphony::Author()->isDeveloper()) {
                $options[] = array('developer', $author->isDeveloper(), __('Developer'));
            }

            $label->appendChild(Widget::Select('fields[user_type]', $options));
            if (isset($this->_errors['user_type'])) {
                $div->appendChild(Widget::Error($label, $this->_errors['user_type']));
            } else {
                $div->appendChild($label);
            }
        }

        $group->appendChild($div);

        // Password
        $fieldset = new XMLElement('fieldset', null, array('class' => 'two columns', 'id' => 'password'));
        $legend = new XMLElement('legend', __('Password'));
        $help = new XMLElement('i', __('Leave password fields blank to keep the current password'));
        $fieldset->appendChild($legend);
        $fieldset->appendChild($help);

        /*
            Password reset rules:
            - Primary account can edit all accounts.
            - Developers can edit all developers, managers and authors, and their own.
            - Managers can edit all Authors, and their own.
            - Authors can edit their own.
        */

        $canEdit = // Managers can edit all Authors, and their own.
                (Symphony::Author()->isManager() && $author->isAuthor())
            // Primary account can edit all accounts.
            || Symphony::Author()->isPrimaryAccount()
            // Developers can edit all developers, managers and authors, and their own.
            || Symphony::Author()->isDeveloper() && $author->isPrimaryAccount() === false;

        // At this point, only developers, managers and owner are authorized
        // Make sure all users except developers needs to input the old password
        if ($isEditing && ($canEdit || $isOwner) && !Symphony::Author()->isDeveloper()) {
            $fieldset->setAttribute('class', 'three columns');

            $label = Widget::Label(null, null, 'column');
            $label->appendChild(Widget::Input('fields[old-password]', null, 'password', array('placeholder' => __('Old Password'), 'autocomplete' => 'off')));
            $fieldset->appendChild((isset($this->_errors['old-password']) ? Widget::Error($label, $this->_errors['old-password']) : $label));
        }

        // New password
        $placeholder = ($isEditing ? __('New Password') : __('Password'));
        $label = Widget::Label(null, null, 'column');
        $label->appendChild(Widget::Input('fields[password]', null, 'password', array('placeholder' => $placeholder, 'autocomplete' => 'off')));
        $fieldset->appendChild((isset($this->_errors['password']) ? Widget::Error($label, $this->_errors['password']) : $label));

        // Confirm password
        $label = Widget::Label(null, null, 'column');
        $label->appendChild(Widget::Input('fields[password-confirmation]', null, 'password', array('placeholder' => __('Confirm Password'), 'autocomplete' => 'off')));
        $fieldset->appendChild((isset($this->_errors['password-confirmation']) ? Widget::Error($label, $this->_errors['password']) : $label));

        $group->appendChild($fieldset);

        // Auth token
        if (Symphony::Author()->isDeveloper() || Symphony::Author()->isManager() || $isOwner) {
            $label = Widget::Label();
            $group->appendChild(Widget::Input('action[remote_login]', 'no', 'hidden'));
            $input = Widget::Input('action[remote_login]', 'yes', 'checkbox');

            if ($author->isTokenActive()) {
                $input->setAttribute('checked', 'checked');
                $tokenUrl = SYMPHONY_URL . '/login/' . $author->getAuthToken() . '/';
                $label->setValue(__('%s Remote login with the token %s is enabled.', [
                     $input->generate(),
                     '<a href="' . $tokenUrl . '">' . $author->getAuthToken() . '</a>',
                ]));
            } else {
                $label->setValue(__('%s Remote login is currently disabled.', [
                    $input->generate(),
                ]) . ' ' . __('Check the box to generate a new token.'));
            }

            $group->appendChild($label);
        }

        $label = Widget::Label(__('Default Area'));

        $sections = (new SectionManager)->select()->sort('sortorder')->execute()->rows();

        $options = array();

        // If the Author is the Developer, allow them to set the Default Area to
        // be the Sections Index.
        if ($author->isDeveloper()) {
            $options[] = array(
                '/blueprints/sections/',
                $author->get('default_area') == '/blueprints/sections/',
                __('Sections Index')
            );
        }

        if (is_array($sections) && !empty($sections)) {
            foreach ($sections as $s) {
                $options[] = array(
                    $s->get('id'),
                    $author->get('default_area') == $s->get('id'),
                    General::sanitize($s->get('name'))
                );
            }
        }

        /**
        * Allows injection or manipulation of the Default Area dropdown for an Author.
        * Take care with adding in options that are only valid for Developers, as if a
        * normal Author is set to that option, they will be redirected to their own
        * Author record.
        *
        *
        * @delegate AddDefaultAuthorAreas
        * @since Symphony 2.2
        * @param string $context
        * '/system/authors/'
        * @param array $options
        * An associative array of options, suitable for use for the Widget::Select
        * function. By default this will be an array of the Sections in the current
        * installation. New options should be the path to the page after the `SYMPHONY_URL`
        * constant.
        * @param string $default_area
        * The current `default_area` for this Author.
        * @param Author $author
        *  The Author object.
        *  This parameter is available @since Symphony 2.7.0
        */
        Symphony::ExtensionManager()->notifyMembers('AddDefaultAuthorAreas', '/system/authors/', array(
            'options' => &$options,
            'default_area' => $author->get('default_area'),
            'author' => $author,
        ));

        $label->appendChild(Widget::Select('fields[default_area]', $options));
        $group->appendChild($label);

        $this->Form->appendChild($group);

        // Custom Language Selection
        $languages = Lang::getAvailableLanguages();
        if (count($languages) > 1) {
            // Get language names
            asort($languages);

            $group = new XMLElement('fieldset');
            $group->setAttribute('class', 'settings');
            $group->appendChild(new XMLElement('legend', __('Custom Preferences')));

            $label = Widget::Label(__('Language'));

            $options = array(
                array(null, is_null($author->get('language')), __('System Default'))
            );

            foreach ($languages as $code => $name) {
                $options[] = array($code, $code == $author->get('language'), $name);
            }
            $select = Widget::Select('fields[language]', $options);
            $label->appendChild($select);
            $group->appendChild($label);

            $this->Form->appendChild($group);
        }

        // Administration password double check
        if ($isEditing && !$isOwner) {
            $group = new XMLElement('fieldset');
            $group->setAttribute('class', 'settings');
            $group->setAttribute('id', 'confirmation');
            $group->appendChild(new XMLElement('legend', __('Confirmation')));
            $group->appendChild(new XMLElement('p', __('Please confirm changes to this author with your password.'), array('class' => 'help')));

            $label = Widget::Label(__('Password'));
            $label->appendChild(Widget::Input('fields[confirm-change-password]', null, 'password', array(
                'autocomplete' => 'off',
                'placeholder' => __('Your Password')
            )));
            $group->appendChild(
                isset($this->_errors['confirm-change-password']) ? Widget::Error($label, $this->_errors['confirm-change-password']) : $label
            );

            $this->Form->appendChild($group);
        }

        // Actions
        $div = new XMLElement('div');
        $div->setAttribute('class', 'actions');

        $div->appendChild(Widget::Input('action[save]', ($this->_context['action'] == 'edit' ? __('Save Changes') : __('Create Author')), 'submit', array('accesskey' => 's')));

        if ($isEditing && !$isOwner && !$author->isPrimaryAccount() && $canEdit) {
            $button = new XMLElement('button', __('Delete'));
            $button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this author'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this author?')));
            $div->appendChild($button);
        }

        $this->Form->appendChild($div);

        /**
        * Allows the injection of custom form fields given the current `$this->Form`
        * object. Please note that this custom data should be saved in own extension
        * tables and that modifying `tbl_authors` to house your data is highly discouraged.
        *
        * @delegate AddElementstoAuthorForm
        * @since Symphony 2.2
        * @param string $context
        * '/system/authors/'
        * @param XMLElement $form
        * The contents of `$this->Form` after all the default form elements have been appended.
        * @param Author $author
        * The current Author object that is being edited
        * @param array $fields
        *  The POST fields
        *  This parameter is available @since Symphony 2.7.0
        * @param array $errors
        *  The error array used to validate the Author.
        *  Extension should register their own errors elsewhere and used the value
        *  to modify the UI accordingly.
        *  This parameter is available @since Symphony 2.7.0
        */
        Symphony::ExtensionManager()->notifyMembers('AddElementstoAuthorForm', '/system/authors/', array(
            'form' => &$this->Form,
            'author' => $author,
            'fields' => isset($_POST['fields']) ? $_POST['fields'] : null,
            'errors' => $this->_errors,
        ));
    }

    public function __actionNew()
    {
        if (is_array($_POST['action']) && array_key_exists('save', $_POST['action'])) {
            $fields = $_POST['fields'];
            $canCreate = Symphony::Author()->isDeveloper() || Symphony::Author()->isManager();

            if (!$canCreate) {
                Administration::instance()->throwCustomError(
                    __('You are not authorised to create authors.'),
                    __('Access Denied'),
                    Page::HTTP_STATUS_UNAUTHORIZED
                );
            }

            $this->_Author = new Author();
            $this->_Author->set('user_type', $fields['user_type']);
            $this->_Author->set('primary', 'no');
            $this->_Author->set('email', $fields['email']);
            $this->_Author->set('username', General::sanitize($fields['username']));
            $this->_Author->set('first_name', General::sanitize($fields['first_name']));
            $this->_Author->set('last_name', General::sanitize($fields['last_name']));
            $this->_Author->set('last_seen', null);
            $this->_Author->set('password', (trim($fields['password']) == '' ? '' : Cryptography::hash($fields['password'])));
            $this->_Author->set('default_area', $fields['default_area']);
            if ($this->isRemoteLoginActionChecked() && !$this->_Author->isTokenActive()) {
                $this->_Author->set('auth_token', Cryptography::randomBytes());
            } elseif (!$this->isRemoteLoginActionChecked()) {
                $this->_Author->set('auth_token', null);
            }
            $this->_Author->set('language', isset($fields['language']) ? $fields['language'] : null);

            /**
             * Creation of a new Author. The Author object is provided as read
             * only through this delegate.
             *
             * @delegate AuthorPreCreate
             * @since Symphony 2.7.0
             * @param string $context
             * '/system/authors/'
             * @param Author $author
             *  The Author object that has just been created, but not yet committed, nor validated
             * @param array $fields
             *  The POST fields
             * @param array $errors
             *  The error array used to validate the Author, passed by reference.
             *  Extension should append to this array if they detect validation problems.
             */
            Symphony::ExtensionManager()->notifyMembers('AuthorPreCreate', '/system/authors/', array(
                'author' => $this->_Author,
                'field' => $fields, // @deprecated
                'fields' => $fields,
                'errors' => &$this->_errors,
            ));

            // Make sure managers only create authors
            if (Symphony::Author()->isManager() && $this->_Author->get('user_type') !== 'author') {
                $this->_errors['user_type'] = __('The user type is invalid. You can only create Authors.');
            }

            if (empty($this->_errors) && $this->_Author->validate($this->_errors)) {
                if ($fields['password'] != $fields['password-confirmation']) {
                    $this->_errors['password'] = $this->_errors['password-confirmation'] = __('Passwords did not match');
                } elseif ($author_id = $this->_Author->commit()) {
                    /**
                     * Creation of a new Author. The Author object is provided as read
                     * only through this delegate.
                     *
                     * @delegate AuthorPostCreate
                     * @since Symphony 2.2
                     * @param string $context
                     * '/system/authors/'
                     * @param Author $author
                     *  The Author object that has just been created
                     * @param integer $author_id
                     *  The ID of Author ID that was just created
                     * @param array $fields
                     *  The POST fields
                     *  This parameter is available @since Symphony 2.7.0
                     * @param array $errors
                     *  The error array used to validate the Author, passed by reference.
                     *  Extension should append to this array if they detect saving problems.
                     *  This parameter is available @since Symphony 2.7.0
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorPostCreate', '/system/authors/', array(
                        'author' => $this->_Author,
                        'author_id' => $author_id,
                        'field' => $fields, // @deprecated
                        'fields' => $fields,
                        'errors' => &$this->_errors,
                    ));

                    if (empty($this->_errors)) {
                        redirect(SYMPHONY_URL . "/system/authors/edit/$author_id/created/");
                    }
                }
            }

            if (is_array($this->_errors) && !empty($this->_errors)) {
                $this->pageAlert(__('There were some problems while attempting to save. Please check below for problem fields.'), Alert::ERROR);
            } else {
                $this->pageAlert(
                    __('Unknown errors occurred while attempting to save.')
                    . '<a href="' . SYMPHONY_URL . '/system/log/">'
                    . __('Check your activity log')
                    . '</a>.',
                    Alert::ERROR
                );
            }
        }
    }

    public function __actionEdit()
    {
        if (!$author_id = $this->_context['id']) {
            redirect(SYMPHONY_URL . '/system/authors/');
        }

        $isOwner = ($author_id == Symphony::Author()->get('id'));
        $fields = $_POST['fields'];
        $this->_Author = AuthorManager::fetchByID($author_id);
        $oldData = $this->_Author->get();

        $canEdit = // Managers can edit all Authors, and their own.
                (Symphony::Author()->isManager() && $this->_Author->isAuthor())
                // Primary account can edit all accounts.
                || Symphony::Author()->isPrimaryAccount()
                // Developers can edit all developers, managers and authors, and their own,
                // but not the primary account
                || (Symphony::Author()->isDeveloper() && $this->_Author->isPrimaryAccount() === false);

        if (!$isOwner && !$canEdit) {
            Administration::instance()->throwCustomError(
                __('You are not authorised to modify this author.'),
                __('Access Denied'),
                Page::HTTP_STATUS_UNAUTHORIZED
            );
        }

        if (is_array($_POST['action']) && array_key_exists('save', $_POST['action'])) {
            $authenticated = $changing_password = $changing_email = false;

            if ($fields['email'] != $this->_Author->get('email')) {
                $changing_email = true;
            }

            // Check the old password was correct
            if (isset($fields['old-password']) && strlen(trim($fields['old-password'])) > 0 && Cryptography::compare(trim($fields['old-password']), $this->_Author->get('password'))) {
                $authenticated = true;

                // Developers don't need to specify the old password, unless it's their own account
            } elseif (
                // All accounts can edit their own
                $isOwner ||
                // Is allowed to edit?
                $canEdit
            ) {
                $authenticated = true;
            }

            if (!empty($fields['user_type'])) {
                $this->_Author->set('user_type', $fields['user_type']);
            }
            if (!empty($fields['email'])) {
                $this->_Author->set('email', $fields['email']);
            }
            if (!empty($fields['username'])) {
                $this->_Author->set('username', General::sanitize($fields['username']));
            }
            if (!empty($fields['first_name'])) {
                $this->_Author->set('first_name', General::sanitize($fields['first_name']));
            }
            if (!empty($fields['last_name'])) {
                $this->_Author->set('last_name', General::sanitize($fields['last_name']));
            }
            $this->_Author->set('language', isset($fields['language']) ? $fields['language'] : null);

            if (!empty($fields['password']) && trim($fields['password']) != '') {
                $this->_Author->set('password', Cryptography::hash($fields['password']));
                $changing_password = true;
            }

            // Don't allow authors to set the Section Index as a default area
            // If they had it previously set, just save `null` which will redirect
            // the Author (when logging in) to their own Author record
            if (
                $this->_Author->get('user_type') == 'author'
                && $fields['default_area'] == '/blueprints/sections/'
            ) {
                $this->_Author->set('default_area', null);
            } else {
                $this->_Author->set('default_area', $fields['default_area']);
            }

            if ($authenticated && $this->isRemoteLoginActionChecked() && !$this->_Author->isTokenActive()) {
                $this->_Author->set('auth_token', Cryptography::randomBytes());
            } elseif (!$this->isRemoteLoginActionChecked()) {
                $this->_Author->set('auth_token', null);
            }

            /**
             * Before editing an author, provided with the Author object
             *
             * @delegate AuthorPreEdit
             * @since Symphony 2.7.0
             * @param string $context
             * '/system/authors/'
             * @param Author $author
             * An Author object not yet committed, nor validated
             * @param array $fields
             *  The POST fields
             * @param array $data
             *  @since Symphony 3.0.0
             *  The values as they are in the database
             * @param array $errors
             *  The error array used to validate the Author, passed by reference.
             *  Extension should append to this array if they detect validation problems.
             * @param bool $changing_email
             *  @since Symphony 3.0.0
             *  The changing email flag, so extension can act only if the email changes.
             * @param bool $changing_password
             *  @since Symphony 3.0.0
             *  The changing password flag, so extension can act only if the password changes.
             */
            Symphony::ExtensionManager()->notifyMembers('AuthorPreEdit', '/system/authors/', array(
                'author' => $this->_Author,
                'field' => $fields, // @deprecated
                'fields' => $fields,
                'data' => $oldData,
                'errors' => &$this->_errors,
                'changing_email' => $changing_email,
                'changing_password' => $changing_password,
            ));

            // Make sure this did not change
            $this->_Author->set('id', $author_id);

            // Primary accounts are always developer, Developers can't lower their level
            if ($this->_Author->isPrimaryAccount() || ($isOwner && Symphony::Author()->isDeveloper())) {
                $this->_Author->set('user_type', 'developer');
            // Manager can only change user type for author or keep existing managers
            } elseif (Symphony::Author()->isManager()) {
                $validUserTypes = ['author'];
                if ($oldData['user_type'] === 'manager') {
                    $validUserTypes[] = 'manager';
                }
                if (!in_array($this->_Author->get('user_type'), $validUserTypes)) {
                    $this->_errors['user_type'] = __('The user type is invalid. You can only edit Authors.');
                }
            // Only developer can change user type
            } elseif (!Symphony::Author()->isDeveloper() && $this->_Author->get('user_type') !== $oldData['user_type']) {
                $this->_errors['user_type'] = __('The user type is invalid. You can only edit Authors.');
            }

            if (empty($this->_errors) && $this->_Author->validate($this->_errors)) {
                // Admin changing another profile
                if (!$isOwner) {
                    $entered_password = $fields['confirm-change-password'];

                    if (!isset($fields['confirm-change-password']) || empty($fields['confirm-change-password'])) {
                        $this->_errors['confirm-change-password'] = __('Please provide your own password to make changes to this author.');
                    } elseif (Cryptography::compare($entered_password, Symphony::Author()->get('password')) !== true) {
                        $this->_errors['confirm-change-password'] = __('Wrong password, please enter your own password to make changes to this author.');
                    }
                }

                // Author is changing their password
                if (!$authenticated && ($changing_password || $changing_email)) {
                    if ($changing_password) {
                        $this->_errors['old-password'] = __('Wrong password. Enter old password to change it.');
                    } elseif ($changing_email) {
                        $this->_errors['old-password'] = __('Wrong password. Enter old one to change email address.');
                    }

                    // Passwords provided, but doesn't match.
                } elseif (($fields['password'] != '' || $fields['password-confirmation'] != '') && $fields['password'] != $fields['password-confirmation']) {
                    $this->_errors['password'] = $this->_errors['password-confirmation'] = __('Passwords did not match');
                }

                // All good, let's save the Author
                if (is_array($this->_errors) && empty($this->_errors) && $this->_Author->commit()) {
                    Symphony::Database()
                        ->delete('tbl_forgotpass')
                        ->where(['or' => [
                            'expiry' => ['<' => DateTimeObj::getGMT('c')],
                            'author_id' => $author_id,
                        ]])
                        ->execute();

                    if ($isOwner) {
                        Administration::instance()->login($this->_Author->get('username'), $this->_Author->get('password'), true);
                    }

                    /**
                     * After editing an author, provided with the Author object
                     *
                     * @delegate AuthorPostEdit
                     * @since Symphony 2.2
                     * @param string $context
                     * '/system/authors/'
                     * @param Author $author
                     * An Author object
                     * @param array $fields
                     *  The POST fields
                     *  This parameter is available @since Symphony 2.7.0
                     * @param array $errors
                     *  The error array used to validate the Author, passed by reference.
                     *  Extension should append to this array if they detect saving problems.
                     *  This parameter is available @since Symphony 2.7.0
                     * @param bool $changing_email
                     *  @since Symphony 3.0.0
                     *  The changing email flag, so extension can act only if the email changes.
                     * @param bool $changing_password
                     *  @since Symphony 3.0.0
                     *  The changing password flag, so extension can act only if the password changes.
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorPostEdit', '/system/authors/', array(
                        'author' => $this->_Author,
                        'field' => $fields, // @deprecated
                        'fields' => $fields,
                        'errors' => &$this->_errors,
                        'changing_email' => $changing_email,
                        'changing_password' => $changing_password,
                    ));

                    if (empty($this->_errors)) {
                        redirect(SYMPHONY_URL . '/system/authors/edit/' . $author_id . '/saved/');
                    }

                    // Problems.
                } else {
                    $this->pageAlert(
                        __('Unknown errors occurred while attempting to save.')
                        . '<a href="' . SYMPHONY_URL . '/system/log/">'
                        . __('Check your activity log')
                        . '</a>.',
                        Alert::ERROR
                    );
                }
            }

            // Author doesn't have valid data, throw back.
            if (is_array($this->_errors) && !empty($this->_errors)) {
                $this->pageAlert(__('There were some problems while attempting to save. Please check below for problem fields.'), Alert::ERROR);
            }
        } elseif (is_array($_POST['action']) && array_key_exists('delete', $_POST['action'])) {
            // Validate rights
            if (!$canEdit) {
                $this->pageAlert(__('You are not allowed to delete this author.'), Alert::ERROR);
                return;
            }
            // Admin changing another profile
            if (!$isOwner) {
                $entered_password = $fields['confirm-change-password'];

                if (!isset($fields['confirm-change-password']) || empty($fields['confirm-change-password'])) {
                    $this->_errors['confirm-change-password'] = __('Please provide your own password to make changes to this author.');
                } elseif (Cryptography::compare($entered_password, Symphony::Author()->get('password')) !== true) {
                    $this->_errors['confirm-change-password'] = __('Wrong password, please enter your own password to make changes to this author.');
                }
            }
            if (is_array($this->_errors) && !empty($this->_errors)) {
                $this->pageAlert(__('There were some problems while attempting to save. Please check below for problem fields.'), Alert::ERROR);
                return;
            }

            $this->_Author = AuthorManager::fetchByID($author_id);

            /**
             * Prior to deleting an author, provided with the Author ID.
             *
             * @delegate AuthorPreDelete
             * @since Symphony 2.2
             * @param string $context
             * '/system/authors/'
             * @param integer $author_id
             *  The ID of Author ID that is about to be deleted
             * @param Author $author
             *  The Author object.
             *  This parameter is available @since Symphony 2.7.0
             */
            Symphony::ExtensionManager()->notifyMembers('AuthorPreDelete', '/system/authors/', array(
                'author_id' => $author_id,
                'author' => $this->_Author,
            ));

            if (!$isOwner) {
                $result = AuthorManager::delete($author_id);

                /**
                 * After deleting an author, provided with the Author ID.
                 *
                 * @delegate AuthorPostDelete
                 * @since Symphony 2.7.0
                 * @param string $context
                 * '/system/authors/'
                 * @param integer $author_id
                 *  The ID of Author ID that is about to be deleted
                 * @param Author $author
                 *  The Author object.
                 * @param integer $result
                 *  The result of the delete statement
                 */
                Symphony::ExtensionManager()->notifyMembers('AuthorPostDelete', '/system/authors/', array(
                    'author_id' => $author_id,
                    'author' => $this->_Author,
                    'result' => $result
                ));

                redirect(SYMPHONY_URL . '/system/authors/');
            } else {
                $this->pageAlert(__('You cannot remove yourself as you are the active Author.'), Alert::ERROR);
            }
        }
    }
}
