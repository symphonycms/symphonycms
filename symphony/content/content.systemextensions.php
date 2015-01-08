<?php

/**
 * @package content
 */
/**
 * This page generates the Extensions index which shows all Extensions
 * that are available in this Symphony installation.
 */

class contentSystemExtensions extends AdministrationPage
{
    public function sort(&$sort, &$order, $params)
    {
        if (is_null($sort)) {
            $sort = 'name';
        }

        return ExtensionManager::fetch(array(), array(), $sort . ' ' . $order);
    }

    public function __viewIndex()
    {
        $this->setPageType('table');
        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Extensions'), __('Symphony'))));
        $this->appendSubheading(__('Extensions'));

        $this->Form->setAttribute('action', SYMPHONY_URL . '/system/extensions/');

        Sortable::initialize($this, $extensions, $sort, $order);

        $columns = array(
            array(
                'label' => __('Name'),
                'sortable' => true,
                'handle' => 'name'
            ),
            array(
                'label' => __('Version'),
                'sortable' => false,
            ),
            array(
                'label' => __('Status'),
                'sortable' => false,
            ),
            array(
                'label' => __('Links'),
                'sortable' => false,
                'handle' => 'links'
            ),
            array(
                'label' => __('Authors'),
                'sortable' => true,
                'handle' => 'author'
            )
        );

        $aTableHead = Sortable::buildTableHeaders(
            $columns,
            $sort,
            $order,
            (isset($_REQUEST['filter']) ? '&amp;filter=' . $_REQUEST['filter'] : '')
        );

        $aTableBody = array();

        if (!is_array($extensions) || empty($extensions)) {
            $aTableBody = array(
                Widget::TableRow(array(
                    Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead))
                ), 'odd')
            );
        } else {
            foreach ($extensions as $name => $about) {
                // Name
                $td1 = Widget::TableData($about['name']);
                $td1->appendChild(Widget::Label(__('Select %s Extension', array($about['name'])), null, 'accessible', null, array(
                    'for' => 'extension-' . $name
                )));
                $td1->appendChild(Widget::Input('items['.$name.']', 'on', 'checkbox', array(
                    'id' => 'extension-' . $name
                )));

                // Version
                $installed_version = Symphony::ExtensionManager()->fetchInstalledVersion($name);

                if (in_array(Extension::EXTENSION_NOT_INSTALLED, $about['status'])) {
                    $td2 = Widget::TableData($about['version']);
                } elseif (in_array(Extension::EXTENSION_REQUIRES_UPDATE, $about['status'])) {
                    $td2 = Widget::TableData($installed_version . '<i> → ' . $about['version'] . '</i>');
                } else {
                    $td2 = Widget::TableData($installed_version);
                }

                // Status
                $trClasses = array();
                $trStatus = '';
                $tdMessage = __('Status unavailable');

                if (in_array(Extension::EXTENSION_NOT_INSTALLED, $about['status'])) {
                    $tdMessage = __('Not installed');
                    $trClasses[] = 'inactive';
                    $trClasses[] = 'extension-can-install';
                }

                if (in_array(Extension::EXTENSION_DISABLED, $about['status'])) {
                    $tdMessage = __('Disabled');
                    $trStatus = 'status-notice';
                }

                if (in_array(Extension::EXTENSION_ENABLED, $about['status'])) {
                    $tdMessage = __('Enabled');
                }

                if (in_array(Extension::EXTENSION_REQUIRES_UPDATE, $about['status'])) {
                    $tdMessage = __('Update available');
                    $trClasses[] = 'extension-can-update';
                    $trStatus = 'status-ok';
                }

                if (in_array(Extension::EXTENSION_NOT_COMPATIBLE, $about['status'])) {
                    $tdMessage .= ', ' . __('requires Symphony %s', array($about['required_version']));
                    $trStatus = 'status-error';
                }

                $trClasses[] = $trStatus;
                $td3 = Widget::TableData($tdMessage);

                // Links
                $tdLinks = array();

                if ($about['github'] != '') {
                    $tdLinks['github'] = Widget::Anchor(__('GitHub'), General::validateURL($about['github']))->generate();
                }

                if ($about['discuss'] != '') {
                    $tdLinks['discuss'] = Widget::Anchor(__('Discuss'), General::validateURL($about['discuss']))->generate();
                    // Update links to point to our 'new' domain, RE: #1995
                    $tdLinks['discuss'] = str_replace('symphony-cms.com', 'getsymphony.com', $tdLinks['discuss']);
                }

                if ($about['homepage'] != '') {
                    $tdLinks['homepage'] = Widget::Anchor(__('Homepage'), General::validateURL($about['homepage']))->generate();
                }

                if ($about['wiki'] != '') {
                    $tdLinks['wiki'] = Widget::Anchor(__('Wiki'), General::validateURL($about['wiki']))->generate();
                }

                if ($about['issues'] != '') {
                    $tdLinks['issues'] = Widget::Anchor(__('Issues'), General::validateURL($about['issues']))->generate();
                }

                $td4 = Widget::TableData($tdLinks);

                // Authors
                $tdAuthors = array();

                if (!is_array($about['author'])) {
                    $about['author'] = array($about['author']);
                }

                foreach ($about['author'] as $author) {
                    if (isset($author['website'])) {
                        $tdAuthors[] = Widget::Anchor($author['name'], General::validateURL($author['website']))->generate();
                    } elseif (isset($author['github'])) {
                        $tdAuthors[] = Widget::Anchor($author['name'], General::validateURL('https://github.com/' . $author['github']))->generate();
                    } elseif (isset($author['email'])) {
                        $tdAuthors[] = Widget::Anchor($author['name'], 'mailto:' . $author['email'])->generate();
                    } else {
                        $tdAuthors[] = $author['name'];
                    }
                }

                $td5 = Widget::TableData($tdAuthors);

                // Create the table row
                $tr = Widget::TableRow(array($td1, $td2, $td3, $td4, $td5), implode(' ', $trClasses));

                // Add some attributes about the extension
                $tr->setAttribute('data-handle', $name);
                $tr->setAttribute('data-installed-version', $installed_version);
                $tr->setAttribute('data-meta-version', $about['version']);

                // Add a row to the body array, assigning each cell to the row
                $aTableBody[] = $tr;
            }
        }

        $table = Widget::Table(
            Widget::TableHead($aTableHead),
            null,
            Widget::TableBody($aTableBody),
            'selectable',
            null,
            array('role' => 'directory', 'aria-labelledby' => 'symphony-subheading', 'data-interactive' => 'data-interactive')
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
            array('enable', false, __('Enable')),
            array('disable', false, __('Disable')),
            array('uninstall', false, __('Uninstall'), 'confirm', null, array(
                'data-message' => __('Are you sure you want to uninstall the selected extensions?')
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
         * '/system/extensions/'
         * @param array $options
         *  An array of arrays, where each child array represents an option
         *  in the With Selected menu. Options should follow the same format
         *  expected by `Widget::__SelectBuildOption`. Passed by reference.
         */
        Symphony::ExtensionManager()->notifyMembers('AddCustomActions', '/system/extensions/', array(
            'options' => &$options
        ));

        if (!empty($options)) {
            $tableActions->appendChild(Widget::Apply($options));
            $this->Form->appendChild($tableActions);
        }
    }

    public function __actionIndex()
    {
        $checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

        /**
         * Extensions can listen for any custom actions that were added
         * through `AddCustomPreferenceFieldsets` or `AddCustomActions`
         * delegates.
         *
         * @delegate CustomActions
         * @since Symphony 2.3.2
         * @param string $context
         *  '/system/extensions/'
         * @param array $checked
         *  An array of the selected rows. The value is usually the ID of the
         *  the associated object.
         */
        Symphony::ExtensionManager()->notifyMembers('CustomActions', '/system/extensions/', array(
            'checked' => $checked
        ));

        if (isset($_POST['with-selected']) && is_array($checked) && !empty($checked)) {
            try {
                switch ($_POST['with-selected']) {
                    case 'enable':
                        /**
                         * Notifies just before an Extension is to be enabled.
                         *
                         * @delegate ExtensionPreEnable
                         * @since Symphony 2.2
                         * @param string $context
                         * '/system/extensions/'
                         * @param array $extensions
                         *  An array of all the extension name's to be enabled, passed by reference
                         */
                        Symphony::ExtensionManager()->notifyMembers('ExtensionPreEnable', '/system/extensions/', array('extensions' => &$checked));

                        foreach ($checked as $name) {
                            if (Symphony::ExtensionManager()->enable($name) === false) {
                                return;
                            }
                        }
                        break;
                    case 'disable':
                        /**
                         * Notifies just before an Extension is to be disabled.
                         *
                         * @delegate ExtensionPreDisable
                         * @since Symphony 2.2
                         * @param string $context
                         * '/system/extensions/'
                         * @param array $extensions
                         *  An array of all the extension name's to be disabled, passed by reference
                         */
                        Symphony::ExtensionManager()->notifyMembers('ExtensionPreDisable', '/system/extensions/', array('extensions' => &$checked));

                        foreach ($checked as $name) {
                            Symphony::ExtensionManager()->disable($name);
                        }
                        break;
                    case 'uninstall':
                        /**
                         * Notifies just before an Extension is to be uninstalled
                         *
                         * @delegate ExtensionPreUninstall
                         * @since Symphony 2.2
                         * @param string $context
                         * '/system/extensions/'
                         * @param array $extensions
                         *  An array of all the extension name's to be uninstalled, passed by reference
                         */
                        Symphony::ExtensionManager()->notifyMembers('ExtensionPreUninstall', '/system/extensions/', array('extensions' => &$checked));

                        foreach ($checked as $name) {
                            Symphony::ExtensionManager()->uninstall($name);
                        }

                        break;
                }

                redirect(Administration::instance()->getCurrentPageURL());
            } catch (Exception $e) {
                $this->pageAlert($e->getMessage(), Alert::ERROR);
            }
        }
    }
}
