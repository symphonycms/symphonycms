<?php
	
	class Extension_DS_Users extends Extension {
		public function about() {
			return array(
				'name'			=> 'Users',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'type'			=> array(
					'Data Source',
				),
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'provides'		=> array(
					'datasource_template'
				),
				'description'	=> 'Create data sources from backend user data.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DataSourceFormPrepare',
					'callback'	=> 'prepare'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DataSourceFormAction',
					'callback'	=> 'action'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DataSourceFormView',
					'callback'	=> 'view'
				)
			);
		}
		
		public function prepare($context = array()) {
			if ($context['template'] != 'users') return;
			
			require_once $this->getExtensionPath() . '/lib/usersdatasource.php';
			
			$datasource = $context['datasource'];
			
			// Load defaults:
			if (!$datasource instanceof UsersDataSource) {
				$datasource = new UsersDataSource(Administration::instance());
			}
			
			$context['fields']['can_append_pagination'] = 'no';
			$context['fields']['can_html_encode_text'] = 'no';
			$context['fields']['can_redirect_on_empty'] = 'no';
			
			if ($datasource->canAppendPagination()) {
				$context['fields']['can_append_pagination'] = 'yes';
			}
			
			if ($datasource->canHTMLEncodeText()) {
				$context['fields']['can_html_encode_text'] = 'yes';
			}
			
			if ($datasource->canRedirectOnEmpty()) {
				$context['fields']['can_redirect_on_empty'] = 'yes';
			}
			
			$context['fields']['filters'] = $datasource->getFilters();
			$context['fields']['pagination_limit'] = $datasource->getPaginationLimit();
			$context['fields']['pagination_page'] = $datasource->getPaginationPage();
			$context['fields']['required_url_param'] = $datasource->getRequiredURLParam();
			$context['fields']['sort_field'] = $datasource->getSortField();
			$context['fields']['sort_order'] = $datasource->getSortOrder();
			$context['fields']['output_params'] = (array)$datasource->getOutputParams();
			$context['fields']['included_elements'] = (array)$datasource->getIncludedElements();
		}
		
		public function action($context = array()) {
			if ($context['template'] != 'users') return;
			
			// Validate data:
			$fields = $context['fields'];
			$errors = $context['errors'];
			$failed = $context['failed'];
			
			if (!isset($fields['can_redirect_on_empty'])) {
				$fields['can_redirect_on_empty'] = 'no';
			}
			
			if (!isset($fields['pagination_limit']) or empty($fields['pagination_limit'])) {
				$errors['pagination_limit'] = 'Limit must not be empty.';
				$failed = true;
			}
			
			if (!isset($fields['pagination_page']) or empty($fields['pagination_page'])) {
				$errors['pagination_page'] = 'Show page must not be empty.';
				$failed = true;
			}
			
			$context['errors'] = $errors;
			$context['failed'] = $failed;
			
			// Send back template to save:
			$context['template_file'] = EXTENSIONS . '/ds_template_users/templates/datasource.php';
			$context['template_data'] = array(
				$fields['can_append_pagination'] == 'yes',
				$fields['can_html_encode_text'] == 'yes',
				$fields['can_redirect_on_empty'] == 'yes',
				(array)$fields['filters'],
				(array)$fields['included_elements'],
				(array)$fields['output_params'],
				$fields['pagination_limit'],
				$fields['pagination_page'],
				$fields['required_url_param'],
				Lang::createHandle($fields['about']['name']),
				$fields['sort_field'],
				$fields['sort_order']
			);
		}
		
		public function view($context = array()) {
			if ($context['template'] != 'users') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			
		//	Essentials --------------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			// Name:
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[about][name]', General::sanitize($fields['about']['name']));
			$label->appendChild($input);
			
			if (isset($errors['about']['name'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['about']['name']);
			}
			
			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
			
		//	Filtering ---------------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Filtering')));
			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to filter by page parameters.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Filter Users by'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			
			$this->appendFilter($ol, __('ID'), 'id', $fields['filters']);
			$this->appendFilter($ol, __('Username'), 'username', $fields['filters']);
			$this->appendFilter($ol, __('First Name'), 'first_name', $fields['filters']);
			$this->appendFilter($ol, __('Last Name'), 'last_name', $fields['filters']);
			$this->appendFilter($ol, __('Email'), 'email', $fields['filters']);
			$this->appendFilter($ol, __('User Type'), 'user_type', $fields['filters']);
			
			$div->appendChild($ol);			
						
			$fieldset->appendChild($div);
			
		//	Redirect/404 ------------------------------------------------------
			
			$label = Widget::Label(__('Required URL Parameter <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields[required_url_param]', $fields['required_url_param']));
			$fieldset->appendChild($label);
			
			$p = new XMLElement('p', __('An empty result will be returned when this parameter does not have a value. Do not wrap the parameter with curly-braces.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			// Can redirect on empty:
			$fieldset->appendChild(Widget::Input('fields[can_redirect_on_empty]', 'no', 'hidden'));
			
			$label = Widget::Label();
			$input = Widget::Input('fields[can_redirect_on_empty]', 'yes', 'checkbox');
			
			if ($fields['can_redirect_on_empty'] == 'yes') {
				$input->setAttribute('checked', 'checked');
			}
			
			$label->setValue(__('%s Redirect to 404 page when no results are found', array($input->generate(false))));
			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
			
		//	Sorting -----------------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Sorting')));
			
			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to limit by page parameters.'));
			$p->setAttribute('class', 'help contextual inverse ' . __('navigation'));
			$fieldset->appendChild($p);				
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$select = Widget::Select('fields[sort_field]', array(
				array('id', $fields['sort_order'] == 'id', __('User ID')),
				array('username', $fields['sort_order'] == 'username', __('Username')),
				array('first-name', $fields['sort_order'] == 'first-name', __('First Name')),
				array('last-name', $fields['sort_order'] == 'last-name', __('Last Name')),
				array('email', $fields['sort_order'] == 'email', __('Email')),
				array('status', $fields['sort_order'] == 'status', __('Status')),
			));
			$select->setAttribute('class', 'filtered');
			
			$label = Widget::Label(__('Sort By'));
			$label->appendChild($select);
			$div->appendChild($label);
			
			$select = Widget::Select('fields[sort_order]', array(
				array('asc', ('asc' == $fields['sort_order']), __('ascending')),
				array('desc', ('desc' == $fields['sort_order']), __('descending')),
				array('random', ('random' == $fields['sort_order']), __('random')),
			));
			
			$label = Widget::Label(__('Sort Order'));
			$label->appendChild($select);
			$div->appendChild($label);
			
			$fieldset->appendChild($div);
				
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			$wrapper->appendChild($fieldset);
			
		//	Limiting ----------------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Limiting')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label();
			$input = Widget::Input('fields[pagination_limit]', $fields['pagination_limit'], NULL, array('size' => '6'));
			$label->setValue(__('Show a maximum of %s results', array($input->generate(false))));
			
			if (isset($errors['pagination_limit'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['pagination_limit']);
			}
			
			$group->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('fields[pagination_page]', $fields['pagination_page'], NULL, array('size' => '6'));		
			$label->setValue(__('Show page %s of results', array($input->generate(false))));
			
			if (isset($errors['pagination_page'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['pagination_page']);
			}
			
			$group->appendChild($label);
			$fieldset->appendChild($group);
			
			$fieldset->appendChild(Widget::Input('fields[can_append_pagination]', 'no', 'hidden'));
			
			$label = Widget::Label();
			$input = Widget::Input('fields[can_append_pagination]', 'yes', 'checkbox');
			
			if ($fields['can_append_pagination'] == 'yes') {
				$input->setAttribute('checked', 'checked');
			}
			
			$label->setValue(__('%s Append pagination data to output', array($input->generate(false))));
			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
			
		//	Output options ----------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Output Options')));
	
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'group');
			
			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('Parameter Output')));
			
			$select = Widget::Select('fields[output_params][]', array(
				array('id', in_array('id', $fields['output_params']), __('User ID')),
				array('username', in_array('username', $fields['output_params']), __('Username')),
				array('name', in_array('name', $fields['output_params']), __('Name')),
				array('email', in_array('email', $fields['output_params']), __('Email')),
				array('user_type', in_array('user_type', $fields['output_params']), __('User type'))
			));
			$select->setAttribute('class', 'filtered');
			$select->setAttribute('multiple', 'multiple');
			
			$label = Widget::Label(__('Use Field'));
			$label->appendChild($select);
			$li->appendChild($label);
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__(
				'The parameter <code id="output-param-name">$ds-%s-FIELD</code> will be created with this field\'s value for XSLT or other data sources to use. <code>FIELD</code> is the element name of the chosen field.', array(
					Lang::createHandle(isset($fields['about']['name']) ? $fields['about']['name'] : __('Untitled'))
				)
			));
			$li->appendChild($help);
			$ul->appendChild($li);

			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('XML Output')));
			
			$select = Widget::Select('fields[included_elements][]', array(
				array('username', in_array('username', $fields['included_elements']), 'username'),
				array('name', in_array('name', $fields['included_elements']), 'name'),
				array('email', in_array('email', $fields['included_elements']), 'email'),
				array('authentication-token', in_array('authentication-token', $fields['included_elements']), 'authentication-token'),
				array('default-section', in_array('default-section', $fields['included_elements']), 'default-section'),	
				array('formatting-preference', in_array('formatting-preference', $fields['included_elements']), 'formatting-preference')
			));
			$select->setAttribute('class', 'filtered');
			$select->setAttribute('multiple', 'multiple');
			
			$label = Widget::Label(__('Included Elements'));
			$label->appendChild($select);
			$li->appendChild($label);
			$ul->appendChild($li);
			
			$fieldset->appendChild($ul);
			$wrapper->appendChild($fieldset);
		}
		
		protected function appendFilter(&$wrapper, $name, $handle, $filters) {
			if (isset($filters[$handle])) {
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', $name));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input(
					'fields[filters][' . $handle . ']',
					General::sanitize($filters[$handle])
				));
				$li->appendChild($label);
			 	$wrapper->appendChild($li);	
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', $name));		
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filters][' . $handle . ']'));
			$li->appendChild($label);
		 	$wrapper->appendChild($li);
		}
	}