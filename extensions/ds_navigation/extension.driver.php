<?php

	require_once('lib/navigationdatasource.php');

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
					'name'			=> 'Symphony Team',
					'website'		=> 'http://symphony-cms.com/',
					'email'			=> 'team@symphony-cms.com'
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

		public function prepare(array $data=NULL) {

			$datasource = new NavigationDataSource;

			if(!is_null($data)){
				if(isset($data['about']['name'])) $datasource->about()->name = $data['about']['name'];
				if(isset($data['parent'])) $datasource->parameters()->parent = $data['parent'];
				if(isset($data['type'])) $datasource->parameters()->type = $data['type'];
			}

			// Load defaults:
			/*if (!$datasource instanceof NavigationDataSource) {
				$datasource = new NavigationDataSource(Administration::instance());
			}

			$context['fields']['filters'] = $datasource->getFilters();
			$context['fields']['required_url_param'] = $datasource->getRequiredURLParam();
			$context['fields']['can_redirect_on_empty'] = 'no';

			if ($datasource->canRedirectOnEmpty()) {
				$context['fields']['can_redirect_on_empty'] = 'yes';
			}*/

			return $datasource;
		}

	/*	public function action($context = array()) {
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
		}*/

		public function view(Datasource $datasource, SymphonyDOMElement &$wrapper, MessageStack $errors) {
			throw new Exception('Fix me to work with Views');
			/*$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];*/
			$admin = Administration::instance()->Page;

		//	Essentials --------------------------------------------------------

			$fieldset = $admin->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild($admin->createElement('legend', __('Essentials')));

			// Name:
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[about][name]', General::sanitize($datasource->about()->name));
			$label->appendChild($input);

			if (isset($errors->{'about::name'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'about::name'});
			}

			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);

		//	Filtering ---------------------------------------------------------

			$fieldset = $admin->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild($admin->createElement('legend', __('Filtering')));
			$p = $admin->createElement('p', __('<code>{$param}</code> or <code>Value</code>'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			$group = $admin->createElement('div');
			$group->setAttribute('class', 'group');

			$div = $admin->createElement('div');

			// Parent View:
			$label = Widget::Label(__('Parent View'));
			$input = Widget::Input('fields[parent]', General::sanitize($datasource->parameters()->parent));
			$label->appendChild($input);

			if (isset($errors->{'parent'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'parent'});
			}

			$div->appendChild($label);

			$ul = $admin->createElement('ul');
			$ul->setAttribute('class', 'tags');

			foreach (new ViewIterator as $view) {
				$ul->appendChild($admin->createElement('li', $view->path));
			}

			$div->appendChild($ul);
			$group->appendChild($div);

			$div = $admin->createElement('div');

			// View Type:
			$label = Widget::Label(__('View Type'));
			$input = Widget::Input('fields[type]', General::sanitize($datasource->parameters()->type));
			$label->appendChild($input);

			if (isset($errors->{'type'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'type'});
			}

			$div->appendChild($label);

			$ul = $admin->createElement('ul');
			$ul->setAttribute('class', 'tags');

			foreach(View::fetchUsedTypes() as $type){
				$ul->appendChild($admin->createElement('li', $type));
			}

			$div->appendChild($ul);
			$group->appendChild($div);

/*
			if (isset($datasource->parameters()->parent) && !is_null($datasource->parameters()->parent)){
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', __('Parent View')));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input(
					'fields[parent]', General::sanitize($datasource->parameters()->parent)
				));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', __('Parent View')));
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[parent]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);

			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');
			foreach(View::fetchUsedTypes() as $type) $ul->appendChild(new XMLElement('li', $type));

			if (isset($datasource->parameters()->type) && !is_null($datasource->parameters()->type)){
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', __('View Type')));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input(
					'fields[type]',
					General::sanitize($datasource->parameters()->type)
				));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', __('View Type')));
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[type]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);*/


			$fieldset->appendChild($group);

			$wrapper->appendChild($fieldset);
		}
	}
