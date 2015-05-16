<?php

/**
 * @package content
 */
/**
 * The Datasource Editor page allows a developer to create new datasources
 * from the four Symphony types, Section, Authors, Navigation and Static XML
 */

class contentBlueprintsDatasources extends ResourcesPage
{
    public $_errors = array();

    public function __viewIndex($resource_type)
    {
        parent::__viewIndex(ResourceManager::RESOURCE_TYPE_DS);

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Data Sources'), __('Symphony'))));
        $this->appendSubheading(__('Data Sources'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a new data source'), 'create button', null, array('accesskey' => 'c')));
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
                    $message = __('Data Source updated at %s.', array($time->generate()));
                    break;
                case 'created':
                    $message = __('Data Source created at %s.', array($time->generate()));
            }

            $this->pageAlert(
                $message
                . ' <a href="' . SYMPHONY_URL . '/blueprints/datasources/new/" accesskey="c">'
                . __('Create another?')
                . '</a> <a href="' . SYMPHONY_URL . '/blueprints/datasources/" accesskey="a">'
                . __('View all Data Sources')
                . '</a>',
                Alert::SUCCESS
            );
        }

        $providers = Symphony::ExtensionManager()->getProvidersOf(iProvider::DATASOURCE);
        $isEditing = false;
        $about = $handle = null;
        $fields = array('name'=>null, 'source'=>null, 'filter'=>null, 'required_url_param'=>null, 'negate_url_param'=>null, 'param'=>null);

        if (isset($_POST['fields'])) {
            $fields = $_POST['fields'];

            if (
                !in_array($fields['source'], array('authors', 'navigation', 'static_xml'))
                && !empty($fields['filter']) && is_array($fields['filter'])
            ) {
                $filters = array();
                foreach ($fields['filter'] as $f) {
                    foreach ($f as $key => $val) {
                        $filters[$key] = $val;
                    }
                }

                $fields['filter'][$fields['source']] = $filters;
            }

            if (!isset($fields['xml_elements']) || !is_array($fields['xml_elements'])) {
                $fields['xml_elements'] = array();
            }

            if ($this->_context[0] == 'edit') {
                $isEditing = true;
            }
        } elseif ($this->_context[0] == 'edit') {
            $isEditing = true;
            $handle = $this->_context[1];
            $existing = DatasourceManager::create($handle, array(), false);
            $order = isset($existing->dsParamORDER) ? $existing->dsParamORDER : 'asc';

            if (!$existing->allowEditorToParse()) {
                redirect(SYMPHONY_URL . '/blueprints/datasources/info/' . $handle . '/');
            }

            $about = $existing->about();
            $fields['name'] = $about['name'];

            $fields['order'] = ($order == 'rand') ? 'random' : $order;
            $fields['param'] = isset($existing->dsParamPARAMOUTPUT) ? $existing->dsParamPARAMOUTPUT : null;
            $fields['required_url_param'] = isset($existing->dsParamREQUIREDPARAM) ? trim($existing->dsParamREQUIREDPARAM) : null;
            $fields['negate_url_param'] = isset($existing->dsParamNEGATEPARAM) ? trim($existing->dsParamNEGATEPARAM) : null;

            if (isset($existing->dsParamINCLUDEDELEMENTS) && is_array($existing->dsParamINCLUDEDELEMENTS)) {
                $fields['xml_elements'] = $existing->dsParamINCLUDEDELEMENTS;
            } else {
                $fields['xml_elements'] = array();
            }

            $fields['sort'] = isset($existing->dsParamSORT) ? $existing->dsParamSORT : null;
            $fields['paginate_results'] = isset($existing->dsParamPAGINATERESULTS) ? $existing->dsParamPAGINATERESULTS : 'yes';
            $fields['page_number'] = isset($existing->dsParamSTARTPAGE) ? $existing->dsParamSTARTPAGE : '1';
            $fields['group'] = isset($existing->dsParamGROUP) ? $existing->dsParamGROUP : null;
            $fields['html_encode'] = isset($existing->dsParamHTMLENCODE) ? $existing->dsParamHTMLENCODE : 'no';
            $fields['associated_entry_counts'] = isset($existing->dsParamASSOCIATEDENTRYCOUNTS) ? $existing->dsParamASSOCIATEDENTRYCOUNTS : 'no';
            $fields['redirect_on_empty'] = isset($existing->dsParamREDIRECTONEMPTY) ? $existing->dsParamREDIRECTONEMPTY : 'no';
            $fields['redirect_on_forbidden'] = isset($existing->dsParamREDIRECTONFORBIDDEN) ? $existing->dsParamREDIRECTONFORBIDDEN : 'no';
            $fields['redirect_on_required'] = isset($existing->dsParamREDIRECTONREQUIRED) ? $existing->dsParamREDIRECTONREQUIRED : 'no';

            if (!isset($existing->dsParamFILTERS) || !is_array($existing->dsParamFILTERS)) {
                $existing->dsParamFILTERS = array();
            }

            if (!empty($existing->dsParamFILTERS)) {
                $existing->dsParamFILTERS = array_map('stripslashes', $existing->dsParamFILTERS);
            }

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

            if ($provided == false) {
                switch($fields['source']){
                    case 'authors':
                        $fields['filter']['author'] = $existing->dsParamFILTERS;
                        break;
                    case 'navigation':
                        $fields['filter']['navigation'] = $existing->dsParamFILTERS;
                        break;
                    case 'static_xml':
                        // Symphony 2.3+
                        if (isset($existing->dsParamSTATIC)) {
                            $fields['static_xml'] = trim($existing->dsParamSTATIC);

                            // Handle Symphony 2.2.2 to 2.3 DS's
                        } elseif (isset($existing->dsSTATIC)) {
                            $fields['static_xml'] = trim($existing->dsSTATIC);

                            // Handle pre Symphony 2.2.1 Static DS's
                        } else {
                            $fields['static_xml'] = trim($existing->grab());
                        }
                        break;
                    default:
                        $fields['filter'][$fields['source']] = $existing->dsParamFILTERS;
                        $fields['max_records'] = $existing->dsParamLIMIT;
                        break;
                }
            }
        } else {
            $fields['max_records'] = '20';
            $fields['page_number'] = '1';
            $fields['order'] = 'desc';
        }

        // Handle name on edited changes, or from reading an edited datasource
        if (isset($about['name'])) {
            $name = $about['name'];
        } elseif (isset($fields['name'])) {
            $name = $fields['name'];
        }

        $this->setPageType('form');
        $this->setTitle(__(($isEditing ? '%1$s &ndash; %2$s &ndash; %3$s' : '%2$s &ndash; %3$s'), array($name, __('Data Sources'), __('Symphony'))));
        $this->appendSubheading(($isEditing ? $name : __('Untitled')));
        $this->insertBreadcrumbs(array(
            Widget::Anchor(__('Data Sources'), SYMPHONY_URL . '/blueprints/datasources/'),
        ));

        // Sources
        $sources = new XMLElement('div', null, array('class' => 'apply actions'));
        $div = new XMLElement('div');
        $label = Widget::Label(__('Source'), null, 'apply-label-left');
        $sources->appendChild($label);
        $sources->appendChild($div);

        $sections = SectionManager::fetch(null, 'ASC', 'name');

        if (!is_array($sections)) {
            $sections = array();
        }

        $field_groups = array();

        foreach ($sections as $section) {
            $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);
        }

        $options = array(
            array('label' => __('System'), 'data-label' => 'system', 'options' => array(
                    array('authors', ($fields['source'] == 'authors'), __('Authors'), null, null, array('data-context' => 'authors')),
                    array('navigation', ($fields['source'] == 'navigation'), __('Navigation'), null, null, array('data-context' => 'navigation')),
            )),
            array('label' => __('Custom XML'), 'data-label' => 'custom-xml', 'options' => array(
                    array('static_xml', ($fields['source'] == 'static_xml'), __('Static XML'), null, null, array('data-context' => 'static-xml')),
            )),
        );

        // Loop over the datasource providers
        if (!empty($providers)) {
            $p = array('label' => __('From extensions'), 'data-label' => 'from_extensions', 'options' => array());

            foreach ($providers as $providerClass => $provider) {
                $p['options'][] = array(
                    $providerClass, ($fields['source'] == $providerClass), $provider, null, null, array('data-context' => Lang::createHandle($provider))
                );
            }

            $options[] = $p;
        }

        // Add Sections
        if (is_array($sections) && !empty($sections)) {
            array_unshift($options, array('label' => __('Sections'), 'data-label' => 'sections', 'options' => array()));

            foreach ($sections as $s) {
                $options[0]['options'][] = array($s->get('id'), ($fields['source'] == $s->get('id')), General::sanitize($s->get('name')));
            }
        }

        $div->appendChild(Widget::Select('source', $options, array('id' => 'ds-context')));
        $this->Context->prependChild($sources);

        $this->Form->appendChild(
            Widget::Input('fields[source]', null, 'hidden', array('id' => 'ds-source'))
        );

        // Name
        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Essentials')));

        $group = new XMLElement('div');

        $label = Widget::Label(__('Name'));
        $label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));

        if (isset($this->_errors['name'])) {
            $group->appendChild(Widget::Error($label, $this->_errors['name']));
        } else {
            $group->appendChild($label);
        }

        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);

        // Conditions
        $fieldset = new XMLElement('fieldset');
        $this->setContext($fieldset, array('sections', 'system', 'custom-xml'));
        $fieldset->appendChild(new XMLElement('legend', __('Conditions')));
        $p = new XMLElement('p', __('Leaving these fields empty will always execute the data source.'));
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        $group = new XMLElement('div');
        $group->setAttribute('class', 'two columns');

        $label = Widget::Label(__('Required Parameter'));
        $label->setAttribute('class', 'column ds-param');
        $label->appendChild(new XMLElement('i', __('Optional')));
        $input = Widget::Input('fields[required_url_param]', trim($fields['required_url_param']), 'text', array(
            'placeholder' => __('$param'),
            'data-search-types' => 'parameters',
            'data-trigger' => '$'
        ));
        $label->appendChild($input);
        $group->appendChild($label);

        $label = Widget::Label(__('Forbidden Parameter'));
        $label->setAttribute('class', 'column ds-param');
        $label->appendChild(new XMLElement('i', __('Optional')));
        $input = Widget::Input('fields[negate_url_param]', trim($fields['negate_url_param']), 'text', array(
            'placeholder' => __('$param'),
            'data-search-types' => 'parameters',
            'data-trigger' => '$'
        ));
        $label->appendChild($input);
        $group->appendChild($label);

        $fieldset->appendChild($group);

        $group = new XMLElement('div');
        $group->setAttribute('class', 'two columns ds-param');

        $label = Widget::Checkbox('fields[redirect_on_required]', $fields['redirect_on_required'], __('Redirect to 404 page when the required parameter is not present'));
        $label->setAttribute('class', 'column');
        $group->appendChild($label);

        $label = Widget::Checkbox('fields[redirect_on_forbidden]', $fields['redirect_on_forbidden'], __('Redirect to 404 page when the forbidden parameter is present'));
        $label->setAttribute('class', 'column');
        $group->appendChild($label);

        $fieldset->appendChild($group);

        $label = Widget::Checkbox('fields[redirect_on_empty]', $fields['redirect_on_empty'], __('Redirect to 404 page when no results are found'));
        $label->setAttribute('class', 'column');
        $fieldset->appendChild($label);

        $this->Form->appendChild($fieldset);

        // Filters
        $fieldset = new XMLElement('fieldset');
        $this->setContext($fieldset, array('sections', 'system'));
        $fieldset->appendChild(new XMLElement('legend', __('Filters')));
        $p = new XMLElement('p',
            __('Use %s syntax to filter by page parameters. A default value can be set using %s.', array(
                '<code>{' . __('$param') . '}</code>',
                '<code>{' . __('$param:default') . '}</code>'
            ))
        );
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        foreach ($field_groups as $section_id => $section_data) {
            $div = new XMLElement('div');
            $div->setAttribute('class', 'contextual frame filters-duplicator');
            $div->setAttribute('data-context', 'section-' . $section_id);
            $div->setAttribute('data-interactive', 'data-interactive');

            $ol = new XMLElement('ol');
            $ol->setAttribute('class', 'suggestable');
            $ol->setAttribute('data-interactive', 'data-interactive');
            $ol->setAttribute('data-add', __('Add filter'));
            $ol->setAttribute('data-remove', __('Remove filter'));

            // Add system:id filter
            if (
                isset($fields['filter'][$section_id]['system:id'])
                || isset($fields['filter'][$section_id]['id'])
            ) {
                $id = isset($fields['filter'][$section_id]['system:id'])
                    ? $fields['filter'][$section_id]['system:id']
                    : $fields['filter'][$section_id]['id'];

                $li = new XMLElement('li');
                $li->setAttribute('class', 'unique');
                $li->setAttribute('data-type', 'system:id');
                $li->appendChild(new XMLElement('header', '<h4>' . __('System ID') . '</h4>'));
                $label = Widget::Label(__('Value'));
                $input = Widget::Input('fields[filter]['.$section_id.'][system:id]', General::sanitize($id));
                $input->setAttribute('data-search-types', 'parameters');
                $input->setAttribute('data-trigger', '{$');
                $label->appendChild($input);
                $li->appendChild($label);
                $ol->appendChild($li);
            }

            $li = new XMLElement('li');
            $li->setAttribute('class', 'unique template');
            $li->setAttribute('data-type', 'system:id');
            $li->appendChild(new XMLElement('header', '<h4>' . __('System ID') . '</h4>'));
            $label = Widget::Label(__('Value'));
            $input = Widget::Input('fields[filter]['.$section_id.'][system:id]', General::sanitize($id));
            $input->setAttribute('data-search-types', 'parameters');
            $input->setAttribute('data-trigger', '{$');
            $label->appendChild($input);
            $li->appendChild($label);
            $ol->appendChild($li);

            // Add system:date filter
            if (
                isset($fields['filter'][$section_id]['system:creation-date'])
                || isset($fields['filter'][$section_id]['system:date'])
            ) {
                $creation_date = isset($fields['filter'][$section_id]['system:creation-date'])
                    ? $fields['filter'][$section_id]['system:creation-date']
                    : $fields['filter'][$section_id]['system:date'];

                $li = new XMLElement('li');
                $li->setAttribute('class', 'unique');
                $li->setAttribute('data-type', 'system:creation-date');
                $li->appendChild(new XMLElement('header', '<h4>' . __('System Creation Date') . '</h4>'));
                $label = Widget::Label(__('Value'));
                $input = Widget::Input('fields[filter]['.$section_id.'][system:creation-date]', General::sanitize($creation_date));
                $input->setAttribute('data-search-types', 'parameters');
                $input->setAttribute('data-trigger', '{$');
                $label->appendChild($input);
                $li->appendChild($label);
                $ol->appendChild($li);
            }

            $li = new XMLElement('li');
            $li->setAttribute('class', 'unique template');
            $li->setAttribute('data-type', 'system:creation-date');
            $li->appendChild(new XMLElement('header', '<h4>' . __('System Creation Date') . '</h4>'));
            $label = Widget::Label(__('Value'));
            $input = Widget::Input('fields[filter]['.$section_id.'][system:creation-date]');
            $input->setAttribute('data-search-types', 'parameters');
            $input->setAttribute('data-trigger', '{$');
            $label->appendChild($input);
            $li->appendChild($label);
            $ol->appendChild($li);

            if (isset($fields['filter'][$section_id]['system:modification-date'])) {
                $li = new XMLElement('li');
                $li->setAttribute('class', 'unique');
                $li->setAttribute('data-type', 'system:modification-date');
                $li->appendChild(new XMLElement('header', '<h4>' . __('System Modification Date') . '</h4>'));
                $label = Widget::Label(__('Value'));
                $input = Widget::Input('fields[filter]['.$section_id.'][system:modification-date]', General::sanitize($fields['filter'][$section_id]['system:modification-date']));
                $input->setAttribute('data-search-types', 'parameters');
                $input->setAttribute('data-trigger', '{$');
                $label->appendChild($input);
                $li->appendChild($label);
                $ol->appendChild($li);
            }

            $li = new XMLElement('li');
            $li->setAttribute('class', 'unique template');
            $li->setAttribute('data-type', 'system:modification-date');
            $li->appendChild(new XMLElement('header', '<h4>' . __('System Modification Date') . '</h4>'));
            $label = Widget::Label(__('Value'));
            $input = Widget::Input('fields[filter]['.$section_id.'][system:modification-date]');
            $input->setAttribute('data-search-types', 'parameters');
            $input->setAttribute('data-trigger', '{$');
            $label->appendChild($input);
            $li->appendChild($label);
            $ol->appendChild($li);

            if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
                foreach ($section_data['fields'] as $field) {

                    if (!$field->canFilter()) {
                        continue;
                    }

                    if (isset($fields['filter'][$section_id], $fields['filter'][$section_id][$field->get('id')])) {
                        $wrapper = new XMLElement('li');
                        $wrapper->setAttribute('class', 'unique');
                        $wrapper->setAttribute('data-type', $field->get('element_name'));
                        $errors = isset($this->_errors[$field->get('id')])
                            ? $this->_errors[$field->get('id')]
                            : array();

                        $field->displayDatasourceFilterPanel($wrapper, $fields['filter'][$section_id][$field->get('id')], $errors, $section_id);
                        $ol->appendChild($wrapper);
                    }

                    $wrapper = new XMLElement('li');
                    $wrapper->setAttribute('class', 'unique template');
                    $wrapper->setAttribute('data-type', $field->get('element_name'));
                    $field->displayDatasourceFilterPanel($wrapper, null, null, $section_id);
                    $ol->appendChild($wrapper);

                }
            }

            $div->appendChild($ol);

            $fieldset->appendChild($div);
        }

        $div = new XMLElement('div');
        $div->setAttribute('class', 'contextual frame filters-duplicator');
        $div->setAttribute('data-context', 'authors');
        $div->setAttribute('data-interactive', 'data-interactive');

        $ol = new XMLElement('ol');
        $ol->setAttribute('class', 'suggestable');
        $ol->setAttribute('data-interactive', 'data-interactive');
        $ol->setAttribute('data-add', __('Add filter'));
        $ol->setAttribute('data-remove', __('Remove filter'));

        if (!isset($fields['filter']['author'])) {
            $fields['filter']['author'] = array(
                'id' => null,
                'username' => null,
                'first_name' => null,
                'last_name' => null,
                'email' => null,
                'user_type' => null
            );
        }

        $this->__appendAuthorFilter($ol, __('ID'), 'id', $fields['filter']['author']['id'], (!isset($fields['filter']['author']['id'])));
        $this->__appendAuthorFilter($ol, __('Username'), 'username', $fields['filter']['author']['username'], (!isset($fields['filter']['author']['username'])));
        $this->__appendAuthorFilter($ol, __('First Name'), 'first_name', $fields['filter']['author']['first_name'], (!isset($fields['filter']['author']['first_name'])));
        $this->__appendAuthorFilter($ol, __('Last Name'), 'last_name', $fields['filter']['author']['last_name'], (!isset($fields['filter']['author']['last_name'])));
        $this->__appendAuthorFilter($ol, __('Email'), 'email', $fields['filter']['author']['email'], (!isset($fields['filter']['author']['email'])));
        $this->__appendAuthorFilter($ol, __('User Type'), 'user_type', $fields['filter']['author']['user_type'], (!isset($fields['filter']['author']['user_type'])));

        $div->appendChild($ol);

        $fieldset->appendChild($div);

        $div = new XMLElement('div');
        $div->setAttribute('class', 'contextual frame filters-duplicator');
        $div->setAttribute('data-context', 'navigation');
        $div->setAttribute('data-interactive', 'data-interactive');

        $ol = new XMLElement('ol');
        $ol->setAttribute('class', 'suggestable');
        $ol->setAttribute('data-interactive', 'data-interactive');
        $ol->setAttribute('data-add', __('Add filter'));
        $ol->setAttribute('data-remove', __('Remove filter'));

        $ul = new XMLElement('ul');
        $ul->setAttribute('class', 'tags');
        $ul->setAttribute('data-interactive', 'data-interactive');

        $pages = PageManager::fetch(false, array('*'), array(), 'title ASC');

        foreach ($pages as $page) {
            $ul->appendChild(new XMLElement('li', preg_replace('/\/{2,}/i', '/', '/' . $page['path'] . '/' . $page['handle'])));
        }

        if (isset($fields['filter']['navigation']['parent'])) {
            $li = new XMLElement('li');
            $li->setAttribute('class', 'unique');
            $li->setAttribute('data-type', 'parent');
            $li->appendChild(new XMLElement('header', '<h4>' . __('Parent Page') . '</h4>'));
            $label = Widget::Label(__('Value'));
            $label->appendChild(Widget::Input('fields[filter][navigation][parent]', General::sanitize($fields['filter']['navigation']['parent'])));
            $li->appendChild($label);
            $li->appendChild($ul);
            $ol->appendChild($li);
        }

        $li = new XMLElement('li');
        $li->setAttribute('class', 'unique template');
        $li->setAttribute('data-type', 'parent');
        $li->appendChild(new XMLElement('header', '<h4>' . __('Parent Page') . '</h4>'));
        $label = Widget::Label(__('Value'));
        $label->appendChild(Widget::Input('fields[filter][navigation][parent]'));
        $li->appendChild($label);
        $li->appendChild($ul);
        $ol->appendChild($li);

        $ul = new XMLElement('ul');
        $ul->setAttribute('class', 'tags');
        $ul->setAttribute('data-interactive', 'data-interactive');

        if ($types = PageManager::fetchAvailablePageTypes()) {
            foreach ($types as $type) {
                $ul->appendChild(new XMLElement('li', $type));
            }
        }

        if (isset($fields['filter']['navigation']['type'])) {
            $li = new XMLElement('li');
            $li->setAttribute('class', 'unique');
            $li->setAttribute('data-type', 'type');
            $li->appendChild(new XMLElement('header', '<h4>' . __('Page Type') . '</h4>'));
            $label = Widget::Label(__('Value'));
            $label->appendChild(Widget::Input('fields[filter][navigation][type]', General::sanitize($fields['filter']['navigation']['type'])));
            $li->appendChild($label);
            $li->appendChild($ul);
            $ol->appendChild($li);
        }

        $li = new XMLElement('li');
        $li->setAttribute('class', 'unique template');
        $li->appendChild(new XMLElement('header', '<h4>' . __('Page Type') . '</h4>'));
        $li->setAttribute('data-type', 'type');
        $label = Widget::Label(__('Value'));
        $label->appendChild(Widget::Input('fields[filter][navigation][type]'));
        $li->appendChild($label);
        $li->appendChild($ul);
        $ol->appendChild($li);

        $div->appendChild($ol);

        $fieldset->appendChild($div);
        $this->Form->appendChild($fieldset);

        // Sorting
        $fieldset = new XMLElement('fieldset');
        $this->setContext($fieldset, array('sections', 'system'));
        $fieldset->appendChild(new XMLElement('legend', __('Sorting')));

        $p = new XMLElement('p',
            __('Use %s syntax to order by page parameters.', array(
                '<code>{' . __('$param') . '}</code>'
            ))
        );
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        $div = new XMLElement('div');

        $label = Widget::Label(__('Sort By'));

        $options = array(
            array('label' => __('Authors'), 'data-label' => 'authors', 'options' => array(
                    array('id', ($fields['source'] == 'authors' && $fields['sort'] == 'id'), __('Author ID')),
                    array('username', ($fields['source'] == 'authors' && $fields['sort'] == 'username'), __('Username')),
                    array('first-name', ($fields['source'] == 'authors' && $fields['sort'] == 'first-name'), __('First Name')),
                    array('last-name', ($fields['source'] == 'authors' && $fields['sort'] == 'last-name'), __('Last Name')),
                    array('email', ($fields['source'] == 'authors' && $fields['sort'] == 'email'), __('Email')),
                    array('status', ($fields['source'] == 'authors' && $fields['sort'] == 'status'), __('Status')),
                )
            ),

            array('label' => __('Navigation'), 'data-label' => 'navigation', 'options' => array(
                    array('id', ($fields['source'] == 'navigation' && $fields['sort'] == 'id'), __('Page ID')),
                    array('handle', ($fields['source'] == 'navigation' && $fields['sort'] == 'handle'), __('Handle')),
                    array('sortorder', ($fields['source'] == 'navigation' && $fields['sort'] == 'sortorder'), __('Sort Order')),
                )
            ),
        );

        foreach ($field_groups as $section_id => $section_data) {
            $optgroup = array('label' => General::sanitize($section_data['section']->get('name')), 'data-label' => 'section-' . $section_data['section']->get('id'), 'options' => array(
                array('system:id', ($fields['source'] == $section_id && $fields['sort'] == 'system:id'), __('System ID')),
                array('system:creation-date', ($fields['source'] == $section_id && ($fields['sort'] == 'system:creation-date' || $fields['sort'] == 'system:date')), __('System Creation Date')),
                array('system:modification-date', ($fields['source'] == $section_id && $fields['sort'] == 'system:modification-date'), __('System Modification Date')),
            ));

            if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
                foreach ($section_data['fields'] as $input) {

                    if (!$input->isSortable()) {
                        continue;
                    }

                    $optgroup['options'][] = array(
                        $input->get('element_name'),
                        ($fields['source'] == $section_id && $input->get('element_name') == $fields['sort']),
                        $input->get('label')
                    );
                }
            }

            $options[] = $optgroup;
        }

        $label->appendChild(Widget::Select('fields[sort]', $options));
        $div->appendChild($label);

        $label = Widget::Label(__('Sort Order'));
        $label->setAttribute('class', 'ds-param');

        $input = Widget::Input('fields[order]', $fields['order'], 'text', array(
            'placeholder' => __('{$param}'),
            'data-search-types' => 'parameters',
            'data-trigger' => '{$'
        ));
        $label->appendChild($input);
        $div->appendChild($label);

        $orders = new XMLElement('ul');
        $orders->setAttribute('class', 'tags singular');
        $orders->setAttribute('data-interactive', 'data-interactive');
        $orders->appendChild(new XMLElement('li', 'asc'));
        $orders->appendChild(new XMLElement('li', 'desc'));
        $orders->appendChild(new XMLElement('li', 'random'));
        $div->appendChild($orders);

        $fieldset->appendChild($div);
        $this->Form->appendChild($fieldset);

        // Grouping
        $fieldset = new XMLElement('fieldset');
        $this->setContext($fieldset, array('sections', 'authors'));
        $fieldset->appendChild(new XMLElement('legend', __('Grouping')));

        $label = Widget::Label(__('Group By'));
        $options = array(
            array('', null, __('None')),
        );

        foreach ($field_groups as $section_id => $section_data) {
            $optgroup = array('label' => $section_data['section']->get('name'), 'data-label' => 'section-' . $section_data['section']->get('id'), 'options' => array());

            if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
                foreach ($section_data['fields'] as $input) {

                    if (!$input->allowDatasourceOutputGrouping()) {
                        continue;
                    }

                    $optgroup['options'][] = array($input->get('id'), ($fields['source'] == $section_id && $fields['group'] == $input->get('id')), $input->get('label'));
                }
            }

            $options[] = $optgroup;
        }

        $label->appendChild(Widget::Select('fields[group]', $options));
        $fieldset->appendChild($label);

        $this->Form->appendChild($fieldset);

        // Pagination
        $fieldset = new XMLElement('fieldset');
        $this->setContext($fieldset, array('sections'));
        $fieldset->appendChild(new XMLElement('legend', __('Pagination')));

        $p = new XMLElement('p',
            __('Use %s syntax to limit by page parameters.', array(
                '<code>{' . __('$param') . '}</code>'
            ))
        );
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        $group = new XMLElement('div');
        $group->setAttribute('class', 'two columns pagination');

        $label = Widget::Label(__('Entries per Page'));
        $label->setAttribute('class', 'column ds-param');
        $input = Widget::Input('fields[max_records]', isset($fields['max_records']) ? $fields['max_records'] : '10', 'text', array(
            'placeholder' => __('{$param}'),
            'data-search-types' => 'parameters',
            'data-trigger' => '{$'
        ));
        $label->appendChild($input);
        $group->appendChild($label);

        $label = Widget::Label(__('Page Number'));
        $label->setAttribute('class', 'column ds-param');
        $input = Widget::Input('fields[page_number]', $fields['page_number'], 'text', array(
            'placeholder' => __('{$param}'),
            'data-search-types' => 'parameters',
            'data-trigger' => '{$'
        ));
        $label->appendChild($input);
        $group->appendChild($label);

        $fieldset->appendChild($group);

        $label = Widget::Checkbox('fields[paginate_results]', $fields['paginate_results'], __('Enable pagination'));
        $fieldset->appendChild($label);
        $this->Form->appendChild($fieldset);

        // Content
        $fieldset = new XMLElement('fieldset');
        $this->setContext($fieldset, array('sections', 'authors'));
        $fieldset->appendChild(new XMLElement('legend', __('Content')));

        // XML
        $group = new XMLElement('div', null, array('class' => 'two columns'));

        $label = Widget::Label(__('Included Elements'));
        $label->setAttribute('class', 'column');

        $options = array(
            array('label' => __('Authors'), 'data-label' => 'authors', 'options' => array(
                    array('username', ($fields['source'] == 'authors' && in_array('username', $fields['xml_elements'])), 'username'),
                    array('name', ($fields['source'] == 'authors' && in_array('name', $fields['xml_elements'])), 'name'),
                    array('email', ($fields['source'] == 'authors' && in_array('email', $fields['xml_elements'])), 'email'),
                    array('author-token', ($fields['source'] == 'authors' && in_array('author-token', $fields['xml_elements'])), 'author-token'),
                    array('default-area', ($fields['source'] == 'authors' && in_array('default-area', $fields['xml_elements'])), 'default-area'),
            )),
        );

        foreach ($field_groups as $section_id => $section_data) {
            $optgroup = array(
                'label' => General::sanitize($section_data['section']->get('name')),
                'data-label' => 'section-' . $section_data['section']->get('id'),
                'options' => array(
                    array(
                        'system:pagination',
                        ($fields['source'] == $section_id && in_array('system:pagination', $fields['xml_elements'])),
                        'system: pagination'
                    ),
                    array(
                        'system:date',
                        ($fields['source'] == $section_id && in_array('system:date', $fields['xml_elements'])),
                        'system: date'
                    )
                )
            );

            if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
                foreach ($section_data['fields'] as $field) {
                    $elements = $field->fetchIncludableElements();

                    if (is_array($elements) && !empty($elements)) {
                        foreach ($elements as $name) {
                            $selected = false;

                            if ($fields['source'] == $section_id && in_array($name, $fields['xml_elements'])) {
                                $selected = true;
                            }

                            $optgroup['options'][] = array($name, $selected, $name);
                        }
                    }
                }
            }

            $options[] = $optgroup;
        }

        $label->appendChild(Widget::Select('fields[xml_elements][]', $options, array('multiple' => 'multiple')));
        $group->appendChild($label);

        // Support multiple parameters
        if (!isset($fields['param'])) {
            $fields['param'] = array();
        } elseif (!is_array($fields['param'])) {
            $fields['param'] = array($fields['param']);
        }

        $label = Widget::Label(__('Parameters'));
        $label->setAttribute('class', 'column');
        $prefix = '$ds-' . (isset($this->_context[1]) ? Lang::createHandle($fields['name']) : __('untitled')) . '.';

        $options = array(
            array('label' => __('Authors'), 'data-label' => 'authors', 'options' => array())
        );

        foreach (array('id', 'username', 'name', 'email', 'user_type') as $p) {
            $options[0]['options'][] = array(
                $p,
                ($fields['source'] == 'authors' && in_array($p, $fields['param'])),
                $prefix . $p,
                null,
                null,
                array(
                    'data-handle' => $p
                )
            );
        }

        foreach ($field_groups as $section_id => $section_data) {
            $optgroup = array('label' => $section_data['section']->get('name'), 'data-label' => 'section-' . $section_data['section']->get('id'), 'options' => array());

            foreach (array('id', 'creation-date', 'modification-date', 'author') as $p) {
                $option = array(
                    'system:' . $p,
                    ($fields['source'] == $section_id && in_array('system:' . $p, $fields['param'])),
                    $prefix . 'system-' . $p,
                    null,
                    null,
                    array(
                        'data-handle' => 'system-' . $p
                    )
                );

                // Handle 'system:date' as an output paramater (backwards compatibility)
                if ($p === 'creation-date') {
                    if ($fields['source'] == $section_id && in_array('system:date', $fields['param'])) {
                        $option[1] = true;
                    }
                }

                $optgroup['options'][] = $option;
            }

            if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
                foreach ($section_data['fields'] as $input) {

                    if (!$input->allowDatasourceParamOutput()) {
                        continue;
                    }

                    $optgroup['options'][] = array(
                        $input->get('element_name'),
                        ($fields['source'] == $section_id && in_array($input->get('element_name'), $fields['param'])),
                        $prefix . $input->get('element_name'),
                        null,
                        null,
                        array(
                            'data-handle' => $input->get('element_name')
                        )
                    );
                }
            }

            $options[] = $optgroup;
        }

        $label->appendChild(Widget::Select('fields[param][]', $options, array('multiple' => 'multiple')));
        $group->appendChild($label);

        $fieldset->appendChild($group);

        // Associations
        $label = Widget::Checkbox('fields[associated_entry_counts]', $fields['associated_entry_counts'], __('Include a count of entries in associated sections'));
        $this->setContext($label, array('sections'));
        $fieldset->appendChild($label);

        // Encoding
        $label = Widget::Checkbox('fields[html_encode]', $fields['html_encode'], __('HTML-encode text'));
        $this->setContext($label, array('sections'));
        $fieldset->appendChild($label);

        $this->Form->appendChild($fieldset);

        // Static XML
        if (!isset($fields['static_xml'])) {
            $fields['static_xml'] = null;
        }

        $fieldset = new XMLElement('fieldset');
        $this->setContext($fieldset, array('static-xml'));
        $fieldset->appendChild(new XMLElement('legend', __('Static XML')));
        $p = new XMLElement('p', __('Enter valid XML, exclude XML declaration'));
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        $label = Widget::Label();
        $label->appendChild(Widget::Textarea('fields[static_xml]', 12, 50, General::sanitize(stripslashes($fields['static_xml'])), array('class' => 'code', 'placeholder' => '<static>content</static>')));

        if (isset($this->_errors['static_xml'])) {
            $fieldset->appendChild(Widget::Error($label, $this->_errors['static_xml']));
        } else {
            $fieldset->appendChild($label);
        }

        $this->Form->appendChild($fieldset);

        // Connections
        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Attach to Pages')));
        $p = new XMLElement('p', __('The data will only be available on the selected pages.'));
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        $div = new XMLElement('div');
        $label = Widget::Label(__('Pages'));

        $pages = PageManager::fetch();
        $ds_handle = str_replace('-', '_', Lang::createHandle($fields['name']));
        $connections = ResourceManager::getAttachedPages(ResourceManager::RESOURCE_TYPE_DS, $ds_handle);
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


        // Call the provided datasources to let them inject their filters
        // @todo Ideally when a new Datasource is chosen an AJAX request will fire
        // to get the HTML from the extension. This is hardcoded for now into
        // creating a 'big' page and then hiding the fields with JS
        if (!empty($providers)) {
            foreach ($providers as $providerClass => $provider) {
                call_user_func_array(array($providerClass, 'buildEditor'), array($this->Form, &$this->_errors, $fields, $handle));
            }
        }

        $div = new XMLElement('div');
        $div->setAttribute('class', 'actions');
        $div->appendChild(Widget::Input('action[save]', ($isEditing ? __('Save Changes') : __('Create Data Source')), 'submit', array('accesskey' => 's')));

        if ($isEditing) {
            $button = new XMLElement('button', __('Delete'));
            $button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this data source'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this data source?')));
            $div->appendChild($button);
        }

        $this->Form->appendChild($div);

    }

    public function __viewInfo()
    {
        $this->setPageType('form');

        $datasource = DatasourceManager::create($this->_context[1], array(), false);
        $about = $datasource->about();

        $this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array($about['name'], __('Data Source'), __('Symphony'))));
        $this->appendSubheading(( ($this->_context[0] == 'info') ? $about['name'] : __('Untitled')));
        $this->insertBreadcrumbs(array(
            Widget::Anchor(__('Data Sources'), SYMPHONY_URL . '/blueprints/datasources/'),
        ));
        $this->Form->setAttribute('id', 'controller');

        $link = $about['author']['name'];

        if (isset($about['author']['website'])) {
            $link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));
        } elseif (isset($about['author']['email'])) {
            $link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);
        }

        foreach ($about as $key => $value) {
            $fieldset = null;

            switch ($key) {
                case 'author':
                    if ($link) {
                        $fieldset = new XMLElement('fieldset');
                        $fieldset->appendChild(new XMLElement('legend', __('Author')));
                        $fieldset->appendChild(new XMLElement('p', $link->generate(false)));
                    }
                    break;
                case 'version':
                    $fieldset = new XMLElement('fieldset');
                    $fieldset->appendChild(new XMLElement('legend', __('Version')));
                    $release_date = array_key_exists('release-date', $about) ? $about['release-date'] : filemtime(DatasourceManager::__getDriverPath($this->_context[1]));

                    if (preg_match('/^\d+(\.\d+)*$/', $value)) {
                        $fieldset->appendChild(new XMLElement('p', __('%1$s released on %2$s', array($value, DateTimeObj::format($release_date, __SYM_DATE_FORMAT__)))));
                    } else {
                        $fieldset->appendChild(new XMLElement('p', __('Created by %1$s at %2$s', array($value, DateTimeObj::format($release_date, __SYM_DATE_FORMAT__)))));
                    }
                    break;
                case 'description':
                    $fieldset = new XMLElement('fieldset');
                    $fieldset->appendChild(new XMLElement('legend', __('Description')));
                    $fieldset->appendChild((is_object($about['description']) ? $about['description'] : new XMLElement('p', $about['description'])));
                    break;
                case 'example':
                    if (is_callable(array($datasource, 'example'))) {
                        $fieldset = new XMLElement('fieldset');
                        $fieldset->appendChild(new XMLElement('legend', __('Example XML')));

                        $example = $datasource->example();

                        if (is_object($example)) {
                             $fieldset->appendChild($example);
                        } else {
                            $p = new XMLElement('p');
                            $p->appendChild(new XMLElement('pre', '<code>' . str_replace('<', '&lt;', $example) . '</code>'));
                            $fieldset->appendChild($p);
                        }
                    }
                    break;
            }

            if ($fieldset) {
                $fieldset->setAttribute('class', 'settings');
                $this->Form->appendChild($fieldset);
            }
        }

        // Display source
        $file = DatasourceManager::__getClassPath($this->_context[1]) . '/data.' . $this->_context[1] . '.php';

        if (file_exists($file)) {
            $fieldset = new XMLElement('fieldset');
            $fieldset->setAttribute('class', 'settings');
            $fieldset->appendChild(new XMLElement('legend', __('Source')));

            $source = file_get_contents($file);
            $code = new XMLElement('code', htmlspecialchars($source));
            $pre = new XMLElement('pre');
            $pre->appendChild($code);

            $fieldset->appendChild($pre);
            $this->Form->appendChild($fieldset);
        }
    }

    public function __actionIndex($resource_type)
    {
        return parent::__actionIndex(ResourceManager::RESOURCE_TYPE_DS);
    }

    public function __actionEdit()
    {
        if (array_key_exists('save', $_POST['action'])) {
            return $this->__formAction();
        } elseif (array_key_exists('delete', $_POST['action'])) {
            /**
             * Prior to deleting the Datasource file. Target file path is provided.
             *
             * @delegate DatasourcePreDelete
             * @since Symphony 2.2
             * @param string $context
             * '/blueprints/datasources/'
             * @param string $file
             *  The path to the Datasource file
             */
            Symphony::ExtensionManager()->notifyMembers('DatasourcePreDelete', '/blueprints/datasources/', array('file' => DATASOURCES . "/data." . $this->_context[1] . ".php"));

            if (!General::deleteFile(DATASOURCES . '/data.' . $this->_context[1] . '.php')) {
                $this->pageAlert(
                    __('Failed to delete %s.', array('<code>' . $this->_context[1] . '</code>'))
                    . ' ' . __('Please check permissions on %s.', array('<code>/workspace/data-sources</code>')),
                    Alert::ERROR
                );
            } else {
                $pages = ResourceManager::getAttachedPages(ResourceManager::RESOURCE_TYPE_DS, $this->_context[1]);

                foreach ($pages as $page) {
                    ResourceManager::detach(ResourceManager::RESOURCE_TYPE_DS, $this->_context[1], $page['id']);
                }

                redirect(SYMPHONY_URL . '/blueprints/datasources/');
            }
        }
    }

    public function __actionNew()
    {
        if (array_key_exists('save', $_POST['action'])) {
            return $this->__formAction();
        }
    }

    public function __formAction()
    {
        $fields = $_POST['fields'];
        $this->_errors = array();
        $providers = Symphony::ExtensionManager()->getProvidersOf(iProvider::DATASOURCE);
        $providerClass = null;

        if (trim($fields['name']) == '') {
            $this->_errors['name'] = __('This is a required field');
        }

        if ($fields['source'] == 'static_xml') {
            if (trim($fields['static_xml']) == '') {
                $this->_errors['static_xml'] = __('This is a required field');
            } else {
                $xml_errors = null;

                include_once TOOLKIT . '/class.xsltprocess.php';

                General::validateXML($fields['static_xml'], $xml_errors, false, new XsltProcess());

                if (!empty($xml_errors)) {
                    $this->_errors['static_xml'] = __('XML is invalid.');
                }
            }
        } elseif (is_numeric($fields['source'])) {
            if (strlen(trim($fields['max_records'])) == 0 || (is_numeric($fields['max_records']) && $fields['max_records'] < 1)) {
                if ($fields['paginate_results'] === 'yes') {
                    $this->_errors['max_records'] = __('A result limit must be set');
                }
            } elseif (!self::__isValidPageString($fields['max_records'])) {
                $this->_errors['max_records'] = __('Must be a valid number or parameter');
            }

            if (strlen(trim($fields['page_number'])) == 0 || (is_numeric($fields['page_number']) && $fields['page_number'] < 1)) {
                if ($fields['paginate_results'] === 'yes') {
                    $this->_errors['page_number'] = __('A page number must be set');
                }
            } elseif (!self::__isValidPageString($fields['page_number'])) {
                $this->_errors['page_number'] = __('Must be a valid number or parameter');
            }

            // See if a Provided Datasource is saved
        } elseif (!empty($providers)) {
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

        // Check to make sure the classname is not empty after handlisation.
        if (empty($classname) && !isset($this->_errors['name'])) {
            $this->_errors['name'] = __('Please ensure name contains at least one Latin-based character.', array($classname));
        }

        $file = DATASOURCES . '/data.' . $classname . '.php';

        $isDuplicate = false;
        $queueForDeletion = null;

        if ($this->_context[0] == 'new' && is_file($file)) {
            $isDuplicate = true;
        } elseif ($this->_context[0] == 'edit') {
            $existing_handle = $this->_context[1];

            if ($classname != $existing_handle && is_file($file)) {
                $isDuplicate = true;
            } elseif ($classname != $existing_handle) {
                $queueForDeletion = DATASOURCES . '/data.' . $existing_handle . '.php';
            }
        }

        // Duplicate
        if ($isDuplicate) {
            $this->_errors['name'] = __('A Data source with the name %s already exists', array('<code>' . $classname . '</code>'));
        }

        if (empty($this->_errors)) {
            $filters = array();
            $elements = null;
            $source = $fields['source'];
            $params = array(
                'rootelement' => $rootelement
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
                $dsShell = file_get_contents(call_user_func(array($providerClass, 'getTemplate')));
            } else {
                $dsShell = file_get_contents($this->getTemplate('blueprints.datasource'));
            }

            // Author metadata
            self::injectAboutInformation($dsShell, $about);

            // Do dependencies, the template file must have <!-- CLASS NAME -->
            $dsShell = str_replace('<!-- CLASS NAME -->', $classname, $dsShell);

            // If there is a provider, let them do the prepartion work
            if ($providerClass) {
                $dsShell = call_user_func(array($providerClass, 'prepare'), $fields, $params, $dsShell);
            } else {
                switch($source){
                    case 'authors':
                        $extends = 'AuthorDatasource';
                        if (isset($fields['filter']['author'])) {
                            $filters = $fields['filter']['author'];
                        }

                        $elements = $fields['xml_elements'];

                        $params['order'] = $fields['order'];
                        $params['redirectonempty'] = $fields['redirect_on_empty'];
                        $params['redirectonforbidden'] = $fields['redirect_on_forbidden'];
                        $params['redirectonrequired'] = $fields['redirect_on_required'];
                        $params['requiredparam'] = trim($fields['required_url_param']);
                        $params['negateparam'] = trim($fields['negate_url_param']);
                        $params['paramoutput'] = $fields['param'];
                        $params['sort'] = $fields['sort'];

                        break;
                    case 'navigation':
                        $extends = 'NavigationDatasource';
                        if (isset($fields['filter']['navigation'])) {
                            $filters = $fields['filter']['navigation'];
                        }

                        $params['order'] = $fields['order'];
                        $params['redirectonempty'] = $fields['redirect_on_empty'];
                        $params['redirectonforbidden'] = $fields['redirect_on_forbidden'];
                        $params['redirectonrequired'] = $fields['redirect_on_required'];
                        $params['requiredparam'] = trim($fields['required_url_param']);
                        $params['negateparam'] = trim($fields['negate_url_param']);

                        break;
                    case 'static_xml':
                        $extends = 'StaticXMLDatasource';
                        $fields['static_xml'] = trim($fields['static_xml']);

                        if (preg_match('/^<\?xml/i', $fields['static_xml']) == true) {
                            // Need to remove any XML declaration
                            $fields['static_xml'] = preg_replace('/^<\?xml[^>]+>/i', null, $fields['static_xml']);
                        }

                        $params['static'] = sprintf(
                            '%s',
                            trim($fields['static_xml'])
                        );
                        break;
                    default:
                        $extends = 'SectionDatasource';
                        $elements = $fields['xml_elements'];

                        if (is_array($fields['filter']) && !empty($fields['filter'])) {
                            $filters = array();

                            foreach ($fields['filter'] as $f) {
                                foreach ($f as $key => $val) {
                                    $filters[$key] = $val;
                                }
                            }
                        }

                        $params['order'] = $fields['order'];
                        $params['group'] = $fields['group'];
                        $params['paginateresults'] = $fields['paginate_results'];
                        $params['limit'] = $fields['max_records'];
                        $params['startpage'] = $fields['page_number'];
                        $params['redirectonempty'] = $fields['redirect_on_empty'];
                        $params['redirectonforbidden'] = $fields['redirect_on_forbidden'];
                        $params['redirectonrequired'] = $fields['redirect_on_required'];
                        $params['requiredparam'] = trim($fields['required_url_param']);
                        $params['negateparam'] = trim($fields['negate_url_param']);
                        $params['paramoutput'] = $fields['param'];
                        $params['sort'] = $fields['sort'];
                        $params['htmlencode'] = $fields['html_encode'];
                        $params['associatedentrycounts'] = $fields['associated_entry_counts'];

                        break;
                }

                $this->__injectVarList($dsShell, $params);
                $this->__injectIncludedElements($dsShell, $elements);
                self::injectFilters($dsShell, $filters);

                if (preg_match_all('@(\$ds-[0-9a-z_\.\-]+)@i', $dsShell, $matches)) {
                    $dependencies = General::array_remove_duplicates($matches[1]);
                    $dsShell = str_replace('<!-- DS DEPENDENCY LIST -->', "'" . implode("', '", $dependencies) . "'", $dsShell);
                }

                $dsShell = str_replace('<!-- CLASS EXTENDS -->', $extends, $dsShell);
                $dsShell = str_replace('<!-- SOURCE -->', $source, $dsShell);
            }

            if ($this->_context[0] == 'new') {
                /**
                 * Prior to creating the Datasource, the file path where it will be written to
                 * is provided and well as the contents of that file.
                 *
                 * @delegate DatasourcePreCreate
                 * @since Symphony 2.2
                 * @param string $context
                 * '/blueprints/datasources/'
                 * @param string $file
                 *  The path to the Datasource file
                 * @param string $contents
                 *  The contents for this Datasource as a string passed by reference
                 * @param array $params
                 *  An array of all the `$dsParam*` values
                 * @param array $elements
                 *  An array of all the elements included in this datasource
                 * @param array $filters
                 *  An associative array of all the filters for this datasource with the key
                 *  being the `field_id` and the value the filter.
                 * @param array $dependencies
                 *  An array of dependencies that this datasource has
                 */
                Symphony::ExtensionManager()->notifyMembers('DatasourcePreCreate', '/blueprints/datasources/', array(
                    'file' => $file,
                    'contents' => &$dsShell,
                    'params' => $params,
                    'elements' => $elements,
                    'filters' => $filters,
                    'dependencies' => $dependencies
                ));
            } else {
                /**
                 * Prior to editing a Datasource, the file path where it will be written to
                 * is provided and well as the contents of that file.
                 *
                 * @delegate DatasourcePreEdit
                 * @since Symphony 2.2
                 * @param string $context
                 * '/blueprints/datasources/'
                 * @param string $file
                 *  The path to the Datasource file
                 * @param string $contents
                 *  The contents for this Datasource as a string passed by reference
                 * @param array $dependencies
                 *  An array of dependencies that this datasource has
                 * @param array $params
                 *  An array of all the `$dsParam*` values
                 * @param array $elements
                 *  An array of all the elements included in this datasource
                 * @param array $filters
                 *  An associative array of all the filters for this datasource with the key
                 *  being the `field_id` and the value the filter.
                 */
                Symphony::ExtensionManager()->notifyMembers('DatasourcePreEdit', '/blueprints/datasources/', array(
                    'file' => $file,
                    'contents' => &$dsShell,
                    'dependencies' => $dependencies,
                    'params' => $params,
                    'elements' => $elements,
                    'filters' => $filters
                ));
            }

            // Remove left over placeholders
            $dsShell = preg_replace(array('/<!--[\w ]++-->/', '/(\t+[\r\n]){2,}/', '/(\r\n){2,}/'), '', $dsShell);

            // Write the file
            if (!is_writable(dirname($file)) || !General::writeFile($file, $dsShell, Symphony::Configuration()->get('write_mode', 'file'), 'w', true)) {
                $this->pageAlert(
                    __('Failed to write Data source to disk.')
                    . ' ' . __('Please check permissions on %s.', array('<code>/workspace/data-sources</code>')),
                    Alert::ERROR
                );

                // Write successful
            } else {
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($file, true);
                }

                // Attach this datasources to pages
                $connections = $fields['connections'];
                ResourceManager::setPages(ResourceManager::RESOURCE_TYPE_DS, is_null($existing_handle) ? $classname : $existing_handle, $connections);

                // If the datasource has been updated and the name changed, then adjust all the existing pages that have the old datasource name
                if ($queueForDeletion) {
                    General::deleteFile($queueForDeletion);

                    // Update pages that use this DS
                    $pages = PageManager::fetch(false, array('data_sources', 'id'), array("
                        `data_sources` REGEXP '[[:<:]]" . $existing_handle . "[[:>:]]'
                    "));

                    if (is_array($pages) && !empty($pages)) {
                        foreach ($pages as $page) {
                            $page['data_sources'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['data_sources']);

                            PageManager::edit($page['id'], $page);
                        }
                    }
                }

                if ($this->_context[0] == 'new') {
                    /**
                     * After creating the Datasource, the path to the Datasource file is provided
                     *
                     * @delegate DatasourcePostCreate
                     * @since Symphony 2.2
                     * @param string $context
                     * '/blueprints/datasources/'
                     * @param string $file
                     *  The path to the Datasource file
                     */
                    Symphony::ExtensionManager()->notifyMembers('DatasourcePostCreate', '/blueprints/datasources/', array(
                        'file' => $file
                    ));
                } else {
                    /**
                     * After editing the Datasource, the path to the Datasource file is provided
                     *
                     * @delegate DatasourcePostEdit
                     * @since Symphony 2.2
                     * @param string $context
                     * '/blueprints/datasources/'
                     * @param string $file
                     *  The path to the Datasource file
                     * @param string $previous_file
                     *  The path of the previous Datasource file in the case where a Datasource may
                     *  have been renamed. To get the handle from this value, see
                     *  `DatasourceManager::__getHandleFromFilename`
                     */
                    Symphony::ExtensionManager()->notifyMembers('DatasourcePostEdit', '/blueprints/datasources/', array(
                        'file' => $file,
                        'previous_file' => ($queueForDeletion) ? $queueForDeletion : null
                    ));
                }

                redirect(SYMPHONY_URL . '/blueprints/datasources/edit/'.$classname.'/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');
            }
        }
    }

    public static function injectFilters(&$shell, array $filters)
    {
        if (empty($filters)) {
            return;
        }

        $placeholder = '<!-- FILTERS -->';
        $string = 'public $dsParamFILTERS = array(' . PHP_EOL;

        foreach ($filters as $key => $val) {
            if (trim($val) == '') {
                continue;
            }

            $string .= "        '$key' => '" . addslashes($val) . "'," . PHP_EOL;
        }

        $string .= "    );" . PHP_EOL . "        " . $placeholder;

        $shell = str_replace($placeholder, trim($string), $shell);
    }

    public static function injectAboutInformation(&$shell, array $details)
    {
        if (empty($details)) {
            return;
        }

        foreach ($details as $key => $val) {
            $shell = str_replace('<!-- ' . strtoupper($key) . ' -->', addslashes($val), $shell);
        }
    }

    public function __injectIncludedElements(&$shell, $elements)
    {
        if (!is_array($elements) || empty($elements)) {
            return;
        }

        $placeholder = '<!-- INCLUDED ELEMENTS -->';
        $shell = str_replace($placeholder, "public \$dsParamINCLUDEDELEMENTS = array(" . PHP_EOL . "        '" . implode("'," . PHP_EOL . "        '", $elements) . "'" . PHP_EOL . '    );' . PHP_EOL . "    " . $placeholder, $shell);
    }

    public function __injectVarList(&$shell, $vars)
    {
        if (!is_array($vars) || empty($vars)) {
            return;
        }

        $var_list = null;

        foreach ($vars as $key => $val) {
            if (is_array($val)) {
                $val = "array(" . PHP_EOL . "        '" . implode("'," . PHP_EOL . "        '", $val) . "'" . PHP_EOL . '        );';
                $var_list .= '    public $dsParam' . strtoupper($key) . ' = ' . $val . PHP_EOL;
            } elseif (trim($val) !== '') {
                $var_list .= '    public $dsParam' . strtoupper($key) . " = '" . addslashes($val) . "';" . PHP_EOL;
            }
        }

        $placeholder = '<!-- VAR LIST -->';
        $shell = str_replace($placeholder, trim($var_list) . PHP_EOL . "    " . $placeholder, $shell);
    }

    public function __appendAuthorFilter(&$wrapper, $h4_label, $name, $value = null, $templateOnly = true)
    {
        if (!$templateOnly) {
            $li = new XMLElement('li');
            $li->setAttribute('class', 'unique');
            $li->setAttribute('data-type', $name);
            $li->appendChild(new XMLElement('header', '<h4>' . $h4_label . '</h4>'));
            $label = Widget::Label(__('Value'));
            $label->appendChild(Widget::Input('fields[filter][author]['.$name.']', General::sanitize($value)));
            $li->appendChild($label);

             $wrapper->appendChild($li);
        }

        $li = new XMLElement('li');
        $li->setAttribute('class', 'unique template');
        $li->setAttribute('data-type', $name);
        $li->appendChild(new XMLElement('header', '<h4>' . $h4_label . '</h4>'));
        $label = Widget::Label(__('Value'));
        $label->appendChild(Widget::Input('fields[filter][author]['.$name.']'));
        $li->appendChild($label);

         $wrapper->appendChild($li);

    }

    private static function __isValidPageString($string)
    {
        return (bool)preg_match('/^\{\$[\w-]+(.[\w]+(-[\w]+)?){0,1}\}|[\d]+$/', $string);
    }

    /**
     * Given a `$url` and `$timeout`, this function will use the `Gateway`
     * class to determine that it is a valid URL and returns successfully
     * before the `$timeout`. If it does not, an error message will be
     * returned, otherwise true.
     *
     * @since Symphony 2.3
     * @param string $url
     * @param integer $timeout
     *  If not provided, this will default to 6 seconds
     * @param string $error
     *  If this function returns false, this variable will be populated with the
     *  error message.
     * @return array|boolean
     *  Returns an array with the 'data' if it is a valid URL, otherwise a string
     *  containing an error message.
     */
    public static function __isValidURL($url, $timeout = 6, &$error)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $error = __('Invalid URL');
            return false;
        }

        // Check that URL was provided
        $gateway = new Gateway;
        $gateway->init($url);
        $gateway->setopt('TIMEOUT', $timeout);
        $data = $gateway->exec();

        $info = $gateway->getInfoLast();

        // 28 is CURLE_OPERATION_TIMEDOUT
        if ($info['curl_error'] == 28) {
            $error = __('Request timed out. %d second limit reached.', array($timeout));
            return false;
        } elseif ($data === false || $info['http_code'] != 200) {
            $error = __('Failed to load URL, status code %d was returned.', array($info['http_code']));
            return false;
        }

        return array('data' => $data);
    }

    /**
     * Set Data Source context
     *
     * @since Symphony 2.3.3
     * @param XMLElement $element
     * @param array $context
     */
    public function setContext(&$element, $context)
    {
        $element->setAttribute('class', 'settings contextual');
        $element->setAttribute('data-context', implode(' ', (array)$context));
    }
}
