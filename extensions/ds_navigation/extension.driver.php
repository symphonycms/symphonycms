<?php
	
	class Extension_DS_Navigation extends Extension {
		public function about() {
			return array(
				'name'			=> 'Navigation',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'type'			=> array(
					'Data Source', 'Core'
				),
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'provides'		=> array(
					'datasource_template'
				),
				'description'	=> 'Create data sources from page navigation data.'
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
			if ($context['template'] != 'ds_navigation') return;
			
			require_once $this->getExtensionPath() . '/lib/navigationdatasource.php';
			
			$datasource = $context['datasource'];
			
			// Load defaults:
			if (!$datasource instanceof NavigationDataSource) {
				$datasource = new NavigationDataSource(Administration::instance());
			}
			
			$context['fields']['filters'] = $datasource->getFilters();
			$context['fields']['required_url_param'] = $datasource->getRequiredURLParam();
			$context['fields']['can_redirect_on_empty'] = 'no';
			
			if ($datasource->canRedirectOnEmpty()) {
				$context['fields']['can_redirect_on_empty'] = 'yes';
			}
		}
		
		public function action($context = array()) {
			if ($context['template'] != 'ds_navigation') return;
			
			$fields = $context['fields'];
			
			// Send back template to save:
			$context['template_file'] = EXTENSIONS . '/ds_navigation/templates/datasource.php';
			$context['template_data'] = array(
				$fields['can_redirect_on_empty'] == 'yes',
				(array)$fields['filters'],
				$fields['required_url_param'],
				Lang::createHandle($fields['about']['name'])
			);
		}
		
		public function view($context = array()) {
			if ($context['template'] != 'ds_navigation') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			$admin = Administration::instance()->Page;
			
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
			$div->setAttribute('class', 'contextual ' . __('navigation'));
			$h3 = new XMLElement('h3', __('Filter Navigation by'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');

			//$pages = Symphony::Database()->fetch("SELECT * FROM `tbl_pages` ORDER BY `title` ASC");
			
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');
			
			//foreach ($pages as $page) {
			//	$ul->appendChild(new XMLElement('li', preg_replace('/\/{2,}/i', '/', '/' . $page['path'] . '/' . $page['handle'])));
			//}
			
			if (isset($fields['filters']['parent'])) {
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', __('Parent Page')));		
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input(
					'fields[filters][parent]', General::sanitize($fields['filters']['parent'])
				));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', __('Parent Page')));		
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filters][parent]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);
			
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');
			//if($types = $admin->__fetchAvailablePageTypes()) foreach($types as $type) $ul->appendChild(new XMLElement('li', $type));
			
			if (isset($fields['filters']['type'])) {
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', __('Page Type')));		
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input(
					'fields[filters][type]',
					General::sanitize($fields['filters']['type'])
				));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', __('Page Type')));		
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filters][type]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);
			
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
		}
	}
	