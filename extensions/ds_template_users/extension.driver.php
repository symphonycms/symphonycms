<?php
	
	class Extension_DS_Template_Users extends Extension {
		public function about() {
			return array(
				'name'			=> 'Users',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'type'			=> array(
					'Data Source Type'
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
			
			$datasource = $context['datasource'];
			
			// Load defaults:
			if (!$datasource instanceof UsersDataSource) {
				$datasource = new UsersDataSource(Administration::instance());
			}
			
			$context['fields']['filters'] = $datasource->getFilters();
			$context['fields']['limit'] = $datasource->getLimit();
			$context['fields']['start_page'] = $datasource->getStartPage();
			$context['fields']['required_url_param'] = $datasource->getRequiredURLParam();
			$context['fields']['redirect_on_empty'] = 'no';
			
			if ($datasource->canRedirectOnEmpty()) {
				$context['fields']['redirect_on_empty'] = 'yes';
			}
			
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
			
			if (!isset($fields['redirect_on_empty'])) {
				$fields['redirect_on_empty'] = 'no';
			}
			
			if (!isset($fields['limit']) or empty($fields['limit'])) {
				$errors['limit'] = 'Limit must not be empty.';
				$failed = true;
			}
			
			if (!isset($fields['start_page']) or empty($fields['start_page'])) {
				$errors['start_page'] = 'Show page must not be empty.';
				$failed = true;
			}
			
			$context['fields'] = $fields;
			$context['errors'] = $errors;
			$context['failed'] = $failed;
			
			// Send back template to save:
			$context['template_file'] = EXTENSIONS . '/ds_template_users/templates/datasource.php';
			$context['template_data'] = array(
				$fields['redirect_on_empty'] == 'yes',
				(array)$fields['filters'],
				$fields['limit'],
				(array)$fields['output_params'],
				$fields['required_url_param'],
				Lang::createHandle($fields['about']['name']),
				$fields['sort_field'],
				$fields['sort_order'],
				$fields['start_page']
			);
		}
		
		public function view($context = array()) {
			if ($context['template'] != 'users') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Filter Results')));
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
			$wrapper->appendChild($fieldset);
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Sorting and Limiting')));
			
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

			$label = Widget::Label();
			$input = Widget::Input('fields[limit]', $fields['limit'], NULL, array('size' => '6'));
			$label->setValue(__('Show a maximum of %s results', array($input->generate(false))));
			if(isset($errors['limit'])) $div->appendChild(Widget::wrapFormElementWithError($label, $errors['limit']));
			else $div->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('fields[start_page]', $fields['start_page'], NULL, array('size' => '6'));		
			$label->setValue(__('Show page %s of results', array($input->generate(false))));
			
			if(isset($errors['start_page'])) $div->appendChild(Widget::wrapFormElementWithError($label, $errors['start_page']));
			else $div->appendChild($label);
			
			$fieldset->appendChild($div);
			
			$label = Widget::Label(__('Required URL Parameter <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields[required_url_param]', $fields['required_url_param']));
			$fieldset->appendChild($label);
			
			$p = new XMLElement('p', __('An empty result will be returned when this parameter does not have a value. Do not wrap the parameter with curly-braces.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);			

			$label = Widget::Label();
			$input = Widget::Input('fields[redirect_on_empty]', 'yes', 'checkbox', (isset($fields['redirect_on_empty']) ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Redirect to 404 page when no results are found', array($input->generate(false))));
			$fieldset->appendChild($label);
						
			$wrapper->appendChild($fieldset);			
			
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
	
?>
