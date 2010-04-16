<?php

	require_once 'lib/sectionsdatasource.php';

	Class Extension_DS_Sections extends Extension {
		public function about() {
			return array(
				'name'			=> 'Sections',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-03-02',
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
				'description'	=> 'Create data sources from an XML string.'
			);
		}

		public function prepare(array $data=NULL, DataSource $datasource=NULL) {

			if(is_null($datasource)){
				$datasource = new SectionsDataSource;
			}

			if(!is_null($data)){

				if(isset($data['about']['name'])) $datasource->about()->name = $data['about']['name'];
				$datasource->parameters()->section = $data['section'];

				if(isset($data['conditions']) && is_array($data['conditions'])){
					foreach($data['conditions']['parameter'] as $index => $parameter){
						$datasource->parameters()->conditions[$index] = array(
							'parameter' => $parameter,
							'logic' => $data['conditions']['logic'][$index],
							'action' => $data['conditions']['action'][$index]
						);
					}
				}

				if(isset($data['filter']) && is_array($data['filter'])){
					$datasource->parameters()->filter = $data['filter'];
				}

				$datasource->parameters()->{'redirect-404-on-empty'} = (isset($data['redirect-404-on-empty']) && $data['redirect-404-on-empty'] == 'yes');
				$datasource->parameters()->{'append-pagination'} = (isset($data['append-pagination']) && $data['append-pagination'] == 'yes');
				$datasource->parameters()->{'append-associated-entry-count'} = (isset($data['append-associated-entry-count']) && $data['append-associated-entry-count'] == 'yes');
				$datasource->parameters()->{'html-encode'} = (isset($data['html-encode']) && $data['html-encode'] == 'yes');

				if(isset($data['sort-field'])) $datasource->parameters()->{'sort-field'} = $data['sort-field'];
				if(isset($data['sort-order'])) $datasource->parameters()->{'sort-order'} = $data['sort-order'];
				if(isset($data['limit'])) $datasource->parameters()->{'limit'} = $data['limit'];
				if(isset($data['page'])) $datasource->parameters()->{'page'} = $data['page'];

				$datasource->parameters()->{'included-elements'} = (array)$data['included-elements'];
				$datasource->parameters()->{'parameter-output'} = (array)$data['parameter-output'];

			}

			return $datasource;

		}

		public function view(Datasource $datasource, SymphonyDOMElement &$wrapper, MessageStack $errors) {

			$page = Administration::instance()->Page;
			$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/ds_sections/assets/view.js'), 55533140);

		//	Essentials --------------------------------------------------------

			$fieldset = $page->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild($page->createElement('legend', __('Essentials')));

			$group = $page->createElement('div');
			$group->setAttribute('class', 'group');

			// Name:
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[about][name]', General::sanitize($datasource->about()->name));
			$label->appendChild($input);

			if (isset($errors->{'about::name'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'about::name'});
			}

			$group->appendChild($label);

			// Section:
			//$sectionManager = SectionManager::instance();
		    //$sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			$field_groups = $options = array();

			//if (is_array($sections) && !empty($sections)) {
				foreach (new SectionIterator as $section) {
					$field_groups[$section->handle] = array(
						'fields'	=> $section->fields,
						'section'	=> $section
					);

					$options[] = array($section->handle, ($datasource->parameters()->source == $section->handle), $section->name);
				}
			//}

			$label = Widget::Label(__('Section'));
			$label->appendChild(Widget::Select('fields[section]', $options, array('id' => 'context')));
			$group->appendChild($label);

			$fieldset->appendChild($group);
			$wrapper->appendChild($fieldset);

		//	Conditions ---------------------------------------------------------

			$fieldset = $page->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild($page->createElement('legend', __('Conditions')));

			$help = $page->createElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue('<code>$param</code>');
			$fieldset->appendChild($help);

			$conditionals_container = $page->createElement('div');
			$ol = $page->createElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');

			if(is_array($datasource->parameters()->conditions) && !empty($datasource->parameters()->conditions)){
				foreach($datasource->parameters()->conditions as $condition){
					$li = $page->createElement('li');
					$li->setAttribute('class', 'unique');

					$li->appendChild($page->createElement('h4', 'When'));
					$group = $page->createElement('div');
					$group->setAttribute('class', 'group triple');

					// Parameter
					$label = $page->createElement('label', 'Parameter');
					$label->appendChild(Widget::input('fields[conditions][parameter][]', $condition['parameter']));
					$group->appendChild($label);

					// Logic
					$label = $page->createElement('label', 'Logic');
					$label->appendChild(Widget::select('fields[conditions][logic][]', array(
						array('set', ($condition['logic'] == 'set'), 'is set'),
						array('not-set', ($condition['logic'] == 'not-set'), 'is not set'),
					), array('class' => 'filtered')));
					$group->appendChild($label);

					// Action
					$label = $page->createElement('label', 'Action');
					$label->appendChild(Widget::select('fields[conditions][action][]', array(
						//array('label' => 'Execution', 'options' => array(
							array('execute', ($condition['action'] == 'execute'), 'Execute'),
							array('do-not-execute', ($condition['action'] == 'do-not-execute'), 'Do not Execute'),
						//)),
						//array('label' => 'Redirect', 'options' => array(
						//	array('redirect:404', false, '404'),
						//	array('redirect:/about/me/', false, '/about/me/'),
						//)),
					), array('class' => 'filtered')));

					$group->appendChild($label);
					$li->appendChild($group);
					$ol->appendChild($li);
				}
			}

			// Conditionals Template:
			$li = $page->createElement('li');
			$li->setAttribute('class', 'unique template');

			$li->appendChild($page->createElement('h4', 'When'));
			$group = $page->createElement('div');
			$group->setAttribute('class', 'group triple');

			// Parameter
			$label = $page->createElement('label', 'Parameter');
			$label->appendChild(Widget::input('fields[conditions][parameter][]'));
			$group->appendChild($label);

			// Logic
			$label = $page->createElement('label', 'Logic');
			$label->appendChild(Widget::select('fields[conditions][logic][]', array(
				array('set', false, 'is set'),
				array('not-set', false, 'is not set'),
			), array('class' => 'filtered')));
			$group->appendChild($label);

			// Action
			$label = $page->createElement('label', 'Action');
			$label->appendChild(Widget::select('fields[conditions][action][]', array(
				//array('label' => 'Execution', 'options' => array(
					array('execute', false, 'Execute'),
					array('do-not-execute', false, 'Do not Execute'),
				//)),
				//array('label' => 'Redirect', 'options' => array(
				//	array('redirect:404', false, '404'),
				//	array('redirect:/about/me/', false, '/about/me/'),
				//)),
			), array('class' => 'filtered')));

			$group->appendChild($label);
			$li->appendChild($group);
			$ol->appendChild($li);

			$conditionals_container->appendChild($ol);
			$fieldset->appendChild($conditionals_container);

			$wrapper->appendChild($fieldset);

		//	Filtering ---------------------------------------------------------

			$fieldset = $page->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild($page->createElement('legend', __('Filtering')));

			$help = $page->createElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('<code>{$param}</code> or <code>Value</code>'));
			$fieldset->appendChild($help);

			$container_filter_results = $page->createElement('div');
			$fieldset->appendChild($container_filter_results);

		//	Redirect/404 ------------------------------------------------------
		/*
			$label = Widget::Label(__('Required URL Parameter <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields[required_url_param]', $datasource->parameters()->required_url_param));
			$fieldset->appendChild($label);

			$p = new XMLElement('p', __('An empty result will be returned when this parameter does not have a value. Do not wrap the parameter with curly-braces.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
		*/
			// Can redirect on empty:
			$fieldset->appendChild(Widget::Input('fields[redirect-404-on-empty]', 'no', 'hidden'));

			$label = Widget::Label();
			$input = Widget::Input('fields[redirect-404-on-empty]', 'yes', 'checkbox');

			if ($datasource->parameters()->{'redirect-404-on-empty'} == true) {
				$input->setAttribute('checked', 'checked');
			}

			$label->appendChild($input);
			$label->setValue(__('Redirect to 404 page when no results are found'));
			$fieldset->appendChild($label);

			$wrapper->appendChild($fieldset);


		//	Sorting -----------------------------------------------------------

			$fieldset = $page->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild($page->createElement('legend', __('Sorting')));

			$group = $page->createElement('div');
			$group->setAttribute('class', 'group');

			$container_sort_by = $page->createElement('div');
			$group->appendChild($container_sort_by);

			$label = Widget::Label(__('Sort Order'));

			$options = array(
				array('asc', ('asc' == $datasource->parameters()->{'sort-order'}), __('Acending')),
				array('desc', ('desc' == $datasource->parameters()->{'sort-order'}), __('Descending')),
				array('random', ('random' == $datasource->parameters()->{'sort-order'}), __('Random')),
			);

			$label->appendChild(Widget::Select('fields[sort-order]', $options));
			$group->appendChild($label);

			$fieldset->appendChild($group);
			$wrapper->appendChild($fieldset);

		//	Limiting ----------------------------------------------------------

			$fieldset = $page->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild($page->createElement('legend', __('Limiting')));

			$help = $page->createElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('<code>{$param}</code> or <code>Value</code>'));
			$fieldset->appendChild($help);

			$group = $page->createElement('div');
			$group->setAttribute('class', 'group');

			$label = Widget::Label();
			$input = Widget::Input('fields[limit]', $datasource->parameters()->limit, NULL, array('size' => '6'));
			$label->setValue(__('Show a maximum of %s results', array((string)$input)));

			if (isset($errors->limit)) {
				$label = Widget::wrapFormElementWithError($label, $errors->limit);
			}

			$group->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('fields[page]', $datasource->parameters()->page, NULL, array('size' => '6'));

			$label->setValue(__('Show page %s of results', array((string)$input)));

			if (isset($errors->page)) {
				$label = Widget::wrapFormElementWithError($label, $errors->page);
			}

			$group->appendChild($label);
			$fieldset->appendChild($group);

			$wrapper->appendChild($fieldset);

		//	Output options ----------------------------------------------------

			$fieldset = $page->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild($page->createElement('legend', __('Output Options')));

			$group = $page->createElement('div');
			$group->setAttribute('class', 'group');

			$container_parameter_output = $page->createElement('div');
			$group->appendChild($container_parameter_output);

			$container_xml_output = $page->createElement('div');
			$group->appendChild($container_xml_output);

			$fieldset->appendChild($group);

			$group = $page->createElement('div');
			$group->setAttribute('class', 'group');

			$fieldset->appendChild(Widget::Input('fields[append-pagination]', 'no', 'hidden'));

			$label = Widget::Label();
			$input = Widget::Input('fields[append-pagination]', 'yes', 'checkbox');

			if ($datasource->parameters()->{'append-pagination'} == true) {
				$input->setAttribute('checked', 'checked');
			}

			$label->appendChild($input);
			$label->setValue(__('Append pagination data'));
			$group->appendChild($label);

			$fieldset->appendChild(Widget::Input('fields[append-associated-entry-count]', 'no', 'hidden'));

			$label = Widget::Label();
			$input = Widget::Input('fields[append-associated-entry-count]', 'yes', 'checkbox');

			if ($datasource->parameters()->{'append-associated-entry-count'} == true) {
				$input->setAttribute('checked', 'checked');
			}

			$label->appendChild($input);
			$label->setValue(__('Append entry count'));
			$group->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('fields[html-encode]', 'yes', 'checkbox');

			if ($datasource->parameters()->{'html-encode'} == true) {
				$input->setAttribute('checked', 'checked');
			}

			$label->appendChild($input);
			$label->setValue(__('HTML-encode text'));
			$group->appendChild($label);

			$fieldset->appendChild($group);

			$wrapper->appendChild($fieldset);

		//	Build contexts ----------------------------------------------------

			foreach ($field_groups as $section_handle => $section_data) {
				$section = $section_data['section'];
				$section_active = ($datasource->parameters()->section == $section_handle);
				$filter_data = $datasource->parameters()->filter;

				// Filters:
				$context = $page->createElement('div');
				$context->setAttribute('class', 'context context-' . $section_handle);

				$ol =$page->createElement('ol');
				$ol->setAttribute('class', 'filters-duplicator');

				if (isset($filter_data['id'])) {
					$li = $page->createElement('li');
					$li->setAttribute('class', 'unique');
					$li->appendChild($page->createElement('h4', __('System ID')));

					$label = Widget::Label(__('Value'));
					$label->appendChild(Widget::Input(
						"fields[filter][id]", General::sanitize($filter_data['id'])
					));
					$li->appendChild($label);
					$ol->appendChild($li);
				}

				$li = $page->createElement('li');
				$li->setAttribute('class', 'unique template');
				$li->appendChild($page->createElement('h4', __('System ID')));

				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][id]'));
				$li->appendChild($label);
				$ol->appendChild($li);

				if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
					foreach ($section_data['fields'] as $input) {
						if (!$input->canFilter()) continue;

						//$field_id = $input->get('id');
						$element_name = $input->get('element_name');

						if (isset($filter_data[$element_name])) {
							$filter = $page->createElement('li');
							$filter->setAttribute('class', 'unique');
							$input->displayDatasourceFilterPanel(
								$filter, $filter_data[$element_name],
								$errors->$element_name//, $section->get('id')
							);
							$ol->appendChild($filter);
						}

						$filter = $page->createElement('li');
						$filter->setAttribute('class', 'unique template');
						$input->displayDatasourceFilterPanel($filter, null, null); //, $section->get('id'));
						$ol->appendChild($filter);
					}
				}

				$context->appendChild($ol);
				$container_filter_results->appendChild($context);

				// Select boxes:
				$sort_by_options = array(
					array('system:id', ($section_active and $datasource->parameters()->{'sort-field'} == 'system:id'), __('System ID')),
					array('system:date', ($section_active and $datasource->parameters()->{'sort-field'} == 'system:date'), __('System Date')),
				);
				$options_parameter_output = array(
					array(
						'system:id',
						($section_active and in_array('system:id', $datasource->parameters()->{'parameter-output'})),
						__('System ID')
					),
					array(
						'system:date',
						($section_active and in_array('system:date', $datasource->parameters()->{'parameter-output'})),
						__('System Date')
					),
					array(
						'system:user',
						($section_active and in_array('system:user', $datasource->parameters()->{'parameter-output'})),
						__('System User')
					)
				);
				$included_elements_options = array(
					// TODO: Determine what system fields will be included.
					array(
						'system:date',
						($section_active and in_array('system:date', $datasource->parameters()->{'included-elements'})),
						__('system:date')
					),
					array(
						'system:user',
						($section_active and in_array('system:user', $datasource->parameters()->{'included-elements'})),
						__('system:user')
					)
				);

				if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
					foreach ($section_data['fields'] as $field) {
						$field_handle = $field->get('element_name');
						$field_label = $field->get('label');
						$modes = $field->fetchIncludableElements();

						if ($field->isSortable()) {
							$sort_by_options[] = array(
								$field_handle,
								($section_active and $field_handle == $datasource->parameters()->{'sort-field'}),
								$field_label
							);
						}

						if ($field->allowDatasourceParamOutput()) {
							$options_parameter_output[] = array(
								$field_handle,
								($section_active and in_array($field_handle, $datasource->parameters()->{'parameter-output'})),
								$field_label
							);
						}

						if (is_array($modes)) foreach ($modes as $field_mode) {
							$included_elements_options[] = array(
								$field_mode,
								($section_active and in_array($field_mode, $datasource->parameters()->{'included-elements'})),
								$field_mode
							);
						}
					}
				}

				$label = Widget::Label(__('Sort By'));
				$label->setAttribute('class', 'context context-' . $section_handle);

				$label->appendChild(Widget::Select('fields[sort-field]', $sort_by_options, array('class' => 'filtered')));
				$container_sort_by->appendChild($label);

				$label = Widget::Label(__('Parameter Output'));
				$label->setAttribute('class', 'context context-' . $section_handle);

				$select = Widget::Select('fields[parameter-output][]', $options_parameter_output);
				$select->setAttribute('class', 'filtered');
				$select->setAttribute('multiple', 'multiple');

				$label->appendChild($select);
				$container_parameter_output->appendChild($label);

				$label = Widget::Label(__('Included XML Elements'));
				$label->setAttribute('class', 'context context-' . $section_handle);

				$select = Widget::Select('fields[included-elements][]', $included_elements_options);
				$select->setAttribute('class', 'filtered');
				$select->setAttribute('multiple', 'multiple');

				$label->appendChild($select);
				$container_xml_output->appendChild($label);
			}
		}
	}

