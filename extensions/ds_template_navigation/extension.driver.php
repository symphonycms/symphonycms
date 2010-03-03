<?php
	
	class Extension_DS_Template_Navigation extends Extension {
		public function about() {
			return array(
				'name'			=> 'Navigation',
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
				'description'	=> 'Create data sources from page navigation data.'
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
			$file = EXTENSIONS . '/ds_template_navigation/templates/datasource.php';
			
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
				'data'		=> array(),		// Array of post data
				'errors'	=> null			// Instance of MessageStack to be filled with errors
			);
			*/
		}
		
		public function form($context = array()) {
			if ($context['type'] != 'navigation') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			$admin = Administration::instance()->Page;
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual ' . __('sections') . ' ' . __('users') . ' ' . __('navigation') . ' ' . __('Sections') . ' ' . __('System'));
			$fieldset->appendChild(new XMLElement('legend', __('Filter Results')));
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

			$pages = Symphony::Database()->fetch("SELECT * FROM `tbl_pages` ORDER BY `title` ASC");
				
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');
	
			foreach($pages as $page){
				$ul->appendChild(new XMLElement('li', preg_replace('/\/{2,}/i', '/', '/' . $page['path'] . '/' . $page['handle'])));
			}
				
			if(isset($fields['filter']['navigation']['parent'])){
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', __('Parent Page')));		
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][navigation][parent]', General::sanitize($fields['filter']['navigation']['parent'])));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', __('Parent Page')));		
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter][navigation][parent]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);

			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');
			if($types = $admin->__fetchAvailablePageTypes()) foreach($types as $type) $ul->appendChild(new XMLElement('li', $type));

			if(isset($fields['filter']['navigation']['type'])){
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', __('Page Type')));		
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][navigation][type]', General::sanitize($fields['filter']['navigation']['type'])));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', __('Page Type')));		
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter][navigation][type]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);
			
			$div->appendChild($ol);			
						
			$fieldset->appendChild($div);	
			$wrapper->appendChild($fieldset);
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual inverse ' . __('static_xml') . ' ' . __('dynamic_xml'));
			$fieldset->appendChild(new XMLElement('legend', __('Sorting and Limiting')));		

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
		}
	}
	