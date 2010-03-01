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
				'description'	=> 'Create data sources from backend user data.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'NewDataSourceAction',
					'callback'	=> 'action'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'NewDataSourceForm',
					'callback'	=> 'form'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'EditDataSourceAction',
					'callback'	=> 'action'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'EditDataSourceForm',
					'callback'	=> 'form'
				)
			);
		}
		
		protected function getTemplate() {
			$file = EXTENSIONS . '/ds_template_users/templates/datasource.php';
			
			if (!file_exists($file)) {
				throw new Exception(sprintf("Unable to find template '%s'.", $file));
			}
			
			return file_get_contents($file);
		}
		
		public function action($context = array()) {
			$template = $this->getTemplate();
			
			/*
			$context = array(
				'type'		=> '',			// Type of datasource
				'fields'	=> array(),		// Array of post data
				'errors'	=> null			// Instance of MessageStack to be filled with errors
			);
			*/
		}
		
		public function form($context = array()) {
			if ($context['type'] != 'users') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual ' . __('sections') . ' ' . __('users') . ' ' . __('navigation') . ' ' . __('Sections') . ' ' . __('System'));
			$fieldset->appendChild(new XMLElement('legend', __('Filter Results')));
			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to filter by page parameters.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'contextual ' . __('users'));
			$h3 = new XMLElement('h3', __('Filter Users by'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			
			$this->appendFilter($ol, __('ID'), 'id', $fields['filter']['user']['id'], (!isset($fields['filter']['user']['id'])));	
			$this->appendFilter($ol, __('Username'), 'username', $fields['filter']['user']['username'], (!isset($fields['filter']['user']['username'])));
			$this->appendFilter($ol, __('First Name'), 'first_name', $fields['filter']['user']['first_name'], (!isset($fields['filter']['user']['first_name'])));
			$this->appendFilter($ol, __('Last Name'), 'last_name', $fields['filter']['user']['last_name'], (!isset($fields['filter']['user']['last_name'])));
			$this->appendFilter($ol, __('Email'), 'email', $fields['filter']['user']['email'], (!isset($fields['filter']['user']['email'])));
			$this->appendFilter($ol, __('User Type'), 'user_type', $fields['filter']['user']['user_type'], (!isset($fields['filter']['user']['user_type'])));
								
			$div->appendChild($ol);			
						
			$fieldset->appendChild($div);
			$wrapper->appendChild($fieldset);
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual inverse ' . __('static_xml') . ' ' . __('dynamic_xml'));
			$fieldset->appendChild(new XMLElement('legend', __('Sorting and Limiting')));
			
			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to limit by page parameters.'));
			$p->setAttribute('class', 'help contextual inverse ' . __('navigation'));
			$fieldset->appendChild($p);				
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group contextual ' . __('sections') . ' ' . __('Sections'));
			
			$label = Widget::Label(__('Sort By'));
			
			$options = array(
				array('id', ($fields['source'] == 'users' && $fields['sort'] == 'id'), __('User ID')),
				array('username', ($fields['source'] == 'users' && $fields['sort'] == 'username'), __('Username')),
				array('first-name', ($fields['source'] == 'users' && $fields['sort'] == 'first-name'), __('First Name')),
				array('last-name', ($fields['source'] == 'users' && $fields['sort'] == 'last-name'), __('Last Name')),
				array('email', ($fields['source'] == 'users' && $fields['sort'] == 'email'), __('Email')),
				array('status', ($fields['source'] == 'users' && $fields['sort'] == 'status'), __('Status')),
			);
		
			
			$label->appendChild(Widget::Select('fields[sort]', $options, array('class' => 'filtered')));
			$div->appendChild($label);
			

			$label = Widget::Label(__('Sort Order'));
			
			$options = array(
				array('asc', ('asc' == $fields['order']), __('ascending')),
				array('desc', ('desc' == $fields['order']), __('descending')),
				array('random', ('random' == $fields['order']), __('random')),
			);
			
			$label->appendChild(Widget::Select('fields[order]', $options));
			$div->appendChild($label);
			
			$fieldset->appendChild($div);
				
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group contextual inverse ' . __('navigation'));

			$label = Widget::Label();
			$input = Widget::Input('fields[max_records]', $fields['max_records'], NULL, array('size' => '6'));
			$label->setValue(__('Show a maximum of %s results', array($input->generate(false))));
			if(isset($this->_errors['max_records'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['max_records']));
			else $div->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('fields[page_number]', $fields['page_number'], NULL, array('size' => '6'));		
			$label->setValue(__('Show page %s of results', array($input->generate(false))));
			if(isset($this->_errors['page_number'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['page_number']));
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
			$fieldset->setAttribute('class', 'settings contextual inverse ' .__('navigation') . ' ' . __('static_xml') . ' ' . __('dynamic_xml'));
			$fieldset->appendChild(new XMLElement('legend', __('Output Options')));
	
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'group');
			
			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('Parameter Output')));
			
			$label = Widget::Label(__('Use Field'));
			$options = array(
				array('id', ($fields['source'] == 'users' && in_array('id', $fields['param'])), __('User ID')),
				array('username', ($fields['source'] == 'users' && in_array('username', $fields['param'])), __('Username')),
				array('name', ($fields['source'] == 'users' && in_array('name', $fields['param'])), __('Name')),
				array('email', ($fields['source'] == 'users' && in_array('email', $fields['param'])), __('Email')),
				array('user_type', ($fields['source'] == 'users' && in_array('user_type', $fields['param'])), __('User type')),
			);
			
			$label->appendChild(Widget::Select('fields[param][]', $options, array('class' => 'filtered', 'multiple' => 'multiple')));
			$li->appendChild($label);

			$p = new XMLElement('p', __('The parameter <code id="output-param-name">$ds-%s-FIELD</code> will be created with this field\'s value for XSLT or other data sources to use. <code>FIELD</code> is the element name of the chosen field.', array(($this->_context[0] == 'edit' ? $existing->dsParamROOTELEMENT : __('Untitled')))));
			$p->setAttribute('class', 'help');
			$li->appendChild($p);
			
			$ul->appendChild($li);

			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('XML Output')));

			$label = Widget::Label(__('Included Elements'));
			
			$options = array(
				array('username', ($fields['source'] == 'users' && in_array('username', $fields['xml_elements'])), 'username'),
				array('name', ($fields['source'] == 'users' && in_array('name', $fields['xml_elements'])), 'name'),
				array('email', ($fields['source'] == 'users' && in_array('email', $fields['xml_elements'])), 'email'),
				array('authentication-token', (@in_array('authentication-token', $fields['xml_elements'])), 'authentication-token'),
				array('default-section', (@in_array('default-section', $fields['xml_elements'])), 'default-section'),	
				array('formatting-preference', (@in_array('formatting-preference', $fields['xml_elements'])), 'formatting-preference'),
			);
			
			$label->appendChild(Widget::Select('fields[xml_elements][]', $options, array('multiple' => 'multiple', 'class' => 'filtered')));
			$li->appendChild($label);
			$ul->appendChild($li);

			$fieldset->appendChild($ul);
			$wrapper->appendChild($fieldset);
		}
		
		protected function appendFilter(&$wrapper, $h4_label, $name, $value=NULL, $templateOnly=true){
						
			if(!$templateOnly){				
			
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', $h4_label));		
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][user]['.$name.']', General::sanitize($value)));
				$li->appendChild($label);
			
			 	$wrapper->appendChild($li);	
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', $h4_label));		
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter][user]['.$name.']'));
			$li->appendChild($label);
		
		 	$wrapper->appendChild($li);
		}
	}
	
?>
