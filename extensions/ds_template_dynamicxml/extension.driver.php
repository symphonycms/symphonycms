<?php
	
	class Extension_DS_Template_DynamicXML extends Extension {
		public function about() {
			return array(
				'name'			=> 'Dynamic XML',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'type'			=> array(
					'Data Source Template',
				),
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'provides'		=> array(
					'datasource_template'
				),
				'description'	=> 'Create data sources from XML fetched over HTTP or FTP.'
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
			if ($context['template'] != 'dynamic_xml') return;
			
			require_once $this->getExtensionPath() . '/lib/dynamicxmldatasource.php';
			
			$datasource = $context['datasource'];
			
			// Load defaults:
			if (!$datasource instanceof DynamicXMLDataSource) {
				$datasource = new DynamicXMLDataSource(Administration::instance());
			}
			
			$context['fields']['namespaces'] = $datasource->getNamespaces();
			$context['fields']['url'] = $datasource->getURL();
			$context['fields']['xpath'] = $datasource->getXPath();
			$context['fields']['cache'] = $datasource->getCacheTime();
			$context['fields']['can_redirect_on_empty'] = 'no';
		}
		
		public function action($context = array()) {
			if ($context['template'] != 'dynamic_xml') return;
			
			// Validate data:
			$fields = $context['fields'];
			$errors = $context['errors'];
			$failed = $context['failed'];
			
			if (trim($fields['url']) == '') {
				$errors['url'] = __('This is a required field');
				$failed = true;
			}
			
			if (trim($fields['xpath']) == '') {
				$errors['xpath'] = __('This is a required field');
				$failed = true;
			}
			
			if (!is_numeric($fields['cache'])) {
				$errors['cache'] = __('Must be a valid number');
				$failed = true;
			}
			
			else if ($fields['cache'] < 0) {
				$this->_errors['cache'] = __('Must be greater than zero');
				$failed = true;
			}
			
			$context['errors'] = $errors;
			$context['failed'] = $failed;
			
			// Send back template to save:
			$context['template_file'] = EXTENSIONS . '/ds_template_dynamicxml/templates/datasource.php';
			$context['template_data'] = array(
				(integer)$fields['cache'],
				(array)$fields['namespaces'],
				Lang::createHandle($fields['about']['name']),
				$fields['url'],
				$fields['xpath']
			);
		}
		
		public function view($context = array()) {
			if ($context['template'] != 'dynamic_xml') return;
			
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
			
		//	Source ------------------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Source')));	
			$label = Widget::Label(__('URL'));
			$label->appendChild(Widget::Input(
				'fields[url]', General::sanitize($fields['url'])
			));
			
			if (isset($errors['url'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['url']);
			}
			
			$fieldset->appendChild($label);
			
			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to specify dynamic portions of the URL.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Namespace Declarations <i>Optional</i>'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			
			if (is_array($fields['namespaces'])) foreach ($fields['namespaces'] as $index => $namespace) {
				$name = "fields[namespaces][{$index}]";
				
				$li = new XMLElement('li');
				$li->appendChild(new XMLElement('h4', 'Namespace'));
				
				$group = new XMLElement('div');
				$group->setAttribute('class', 'group');
				
				$label = Widget::Label(__('Name'));
				$label->appendChild(Widget::Input("{$name}[name]", General::sanitize($namespace['name'])));
				$group->appendChild($label);
				
				$label = Widget::Label(__('URI'));
				$label->appendChild(Widget::Input("{$name}[uri]", General::sanitize($namespace['uri'])));
				$group->appendChild($label);
				
				$li->appendChild($group);
				$ol->appendChild($li);
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->appendChild(new XMLElement('h4', __('Namespace')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[namespaces][][name]'));
			$group->appendChild($label);
					
			$label = Widget::Label(__('URI'));
			$label->appendChild(Widget::Input('fields[namespaces][][uri]'));
			$group->appendChild($label);
			
			$li->appendChild($group);
			$ol->appendChild($li);
			
			$div->appendChild($ol);
			$fieldset->appendChild($div);
			
			$fieldset->appendChild(Widget::Input('automatically_discover_namespaces', 'no', 'hidden'));
			
			// TODO: Import this feature from the XML importer extension.
			
			/*
			$input = Widget::Input('automatically_discover_namespaces', 'yes', 'checkbox');
			$label = Widget::Label(__('%s Automatically discover namespaces', array($input->generate())));
			$fieldset->appendChild($label);
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Search the source document for namespaces, any that it finds will be added to the declarations above.'));
			$fieldset->appendChild($help);
			*/
			
			$label = Widget::Label(__('Included Elements'));
			$label->appendChild(Widget::Input('fields[xpath]', General::sanitize($fields['xpath'])));
			
			if (isset($errors['xpath'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['xpath']);
			}
			
			$fieldset->appendChild($label);
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Use an XPath expression to select which elements from the source XML to include.'));
			$fieldset->appendChild($help);
			
			$input = Widget::Input('fields[cache]', max(0, intval($fields['cache'])));
			$input->setAttribute('size', 6);
			
			$label = Widget::Label(__('Update cached result every %s minutes', array($input->generate())));
			
			if (isset($errors['cache'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['cache']);
			}
			
			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
		}
	}