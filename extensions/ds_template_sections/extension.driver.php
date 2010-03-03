<?php
	
	class Extension_DS_Template_Sections extends Extension {
		public function about() {
			return array(
				'name'			=> 'Sections',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-03-02',
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
				'description'	=> 'Create data sources from an XML string.'
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
			if ($context['template'] != 'sections') return;
			
			$datasource = $context['datasource'];
			
			// Load defaults:
			if (!$datasource instanceof SectionsDataSource) {
				$datasource = new SectionsDataSource(Administration::instance());
			}
			
			$context['fields']['can_append_associated_entry_count'] = 'no';
			$context['fields']['can_append_pagination'] = 'no';
			$context['fields']['can_html_encode_text'] = 'no';
			$context['fields']['can_redirect_on_empty'] = 'no';
			
			if ($datasource->canAppendAssociatedEntryCount()) {
				$context['fields']['can_append_associated_entry_count'] = 'yes';
			}
			
			if ($datasource->canAppendPagination()) {
				$context['fields']['can_append_pagination'] = 'yes';
			}
			
			if ($datasource->canHTMLEncodeText()) {
				$context['fields']['can_html_encode_text'] = 'yes';
			}
			
			if ($datasource->canRedirectOnEmpty()) {
				$context['fields']['can_redirect_on_empty'] = 'yes';
			}
			
			$context['fields']['section'] = $datasource->getSection();
			$context['fields']['filters'] = $datasource->getFilters();
			$context['fields']['pagination_limit'] = $datasource->getPaginationLimit();
			$context['fields']['pagination_page'] = $datasource->getPaginationPage();
			$context['fields']['required_url_param'] = $datasource->getRequiredURLParam();
			$context['fields']['sort_field'] = $datasource->getSortField();
			$context['fields']['sort_order'] = $datasource->getSortOrder();
			$context['fields']['output_params'] = (array)$datasource->getOutputParams();
			$context['fields']['group_field'] = $datasource->getGroupField();
			$context['fields']['included_elements'] = (array)$datasource->getIncludedElements();
		}
		
		public function action($context = array()) {
			if ($context['template'] != 'sections') return;
			
			// Validate data:
			$fields = $context['fields'];
			$errors = $context['errors'];
			$failed = $context['failed'];
			
			if (strlen(trim($fields['pagination_limit'])) == 0 or (is_numeric($fields['pagination_limit']) and $fields['pagination_limit'] < 1)) {
				$errors['pagination_limit'] = __('A result limit must be set');
				$failed = true;
			}
			
			if (strlen(trim($fields['pagination_page'])) == 0 or (is_numeric($fields['pagination_page']) and $fields['pagination_page'] < 1)) {
				$errors['pagination_page'] = __('A page number must be set');
				$failed = true;
			}
			
			$context['errors'] = $errors;
			$context['failed'] = $failed;
			
			// Send back template to save:
			$context['template_file'] = EXTENSIONS . '/ds_template_sections/templates/datasource.php';
			$context['template_data'] = array(
				$fields['can_append_associated_entry_count'] == 'yes',
				$fields['can_append_pagination'] == 'yes',
				$fields['can_redirect_on_empty'] == 'yes',
				(array)$fields['filters'],
				(array)$fields['included_elements'],
				$fields['group_field'],
				(array)$fields['output_params'],
				$fields['pagination_limit'],
				$fields['pagination_page'],
				$fields['required_url_param'],
				Lang::createHandle($fields['about']['name']),
				$fields['section'],
				$fields['sort_field'],
				$fields['sort_order']
			);
		}
		
		public function view($context = array()) {
			if ($context['template'] != 'sections') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			$page = Administration::instance()->Page;
			$page->addScriptToHead(URL . '/extensions/ds_template_sections/assets/view.js', 55533140);
			
		//	Essentials --------------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			// Name:
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[about][name]', General::sanitize($fields['about']['name']));
			$label->appendChild($input);
			
			if (isset($errors['about']['name'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['about']['name']);
			}
			
			$group->appendChild($label);
			
			// Section:
			$sectionManager = SectionManager::instance();
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			$field_groups = $options = array();
			
			if (is_array($sections) && !empty($sections)) {
				foreach ((array)$sections as $section) {
					$field_groups[$section->get('id')] = array(
						'fields'	=> $section->fetchFields(),
						'section'	=> $section
					);
				}
				
				foreach ($sections as $s) {
					$options[] = array($s->get('id'), ($fields['source'] == $s->get('id')), $s->get('name'));
				}
			}
			
			$label = Widget::Label(__('Section'));
			$label->appendChild(Widget::Select('fields[section]', $options, array('id' => 'context')));
			$group->appendChild($label);
			
			$fieldset->appendChild($group);
			$wrapper->appendChild($fieldset);
			
		//	Filter results ----------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Filtering')));
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Use <code>{$param}</code> syntax to filter by page parameters.'));
			$fieldset->appendChild($help);
			
			$container_filter_results = new XMLElement('div');
			$fieldset->appendChild($container_filter_results);
			
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
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$container_sort_by = new XMLElement('div');
			$group->appendChild($container_sort_by);
			
			$label = Widget::Label(__('Sort Order'));
			
			$options = array(
				array('asc', ('asc' == $fields['sort_order']), __('ascending')),
				array('desc', ('desc' == $fields['sort_order']), __('descending')),
				array('random', ('random' == $fields['sort_order']), __('random')),
			);
			
			$label->appendChild(Widget::Select('fields[sort_order]', $options));
			$group->appendChild($label);
			
			$fieldset->appendChild($group);
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
			$fieldset->appendChild(new XMLElement('legend', __('Output')));
			
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'group');
			
			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('Parameter Output')));
			
			$container_parameter_output = new XMLElement('div');
			$li->appendChild($container_parameter_output);
			
			$p = new XMLElement('p', __('The parameter <code id="output-param-name">$ds-%s-FIELD</code> will be created with this field\'s value for XSLT or other data sources to use. <code>FIELD</code> is the element name of the chosen field.', array(($this->_context[0] == 'edit' ? $existing->dsParamROOTELEMENT : __('Untitled')))));
			$p->setAttribute('class', 'help');
			$li->appendChild($p);
			
			$ul->appendChild($li);
			
			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('XML Output')));
			
			$container_xml_output = new XMLElement('div');
			$li->appendChild($container_xml_output);
			
			$fieldset->appendChild(Widget::Input('fields[can_append_associated_entry_count]', 'yes', 'hidden'));
			
			$label = Widget::Label();
			$input = Widget::Input('fields[can_append_associated_entry_count]', 'yes', 'checkbox');
			
			if ($fields['can_append_associated_entry_count'] == 'yes') {
				$input->setAttribute('checked', 'checked');
			}
			
			$label->setValue(__('%s Include a count of entries in associated sections', array($input->generate(false))));
			$li->appendChild($label);
			
			$fieldset->appendChild(Widget::Input('fields[can_html_encode_text]', 'yes', 'hidden'));
			
			$label = Widget::Label();
			$input = Widget::Input('fields[can_html_encode_text]', 'yes', 'checkbox');
			
			if ($fields['can_html_encode_text'] == 'yes') {
				$input->setAttribute('checked', 'checked');
			}
			
			$label->setValue(__('%s HTML-encode text', array($input->generate(false))));
			$li->appendChild($label);
			$ul->appendChild($li);
			
			$fieldset->appendChild($ul);
			$wrapper->appendChild($fieldset);
			
		//	Build contexts ----------------------------------------------------
			
			foreach ($field_groups as $section_id => $section_data) {
				$section = $section_data['section'];
				$section_handle = $section->get('handle');
				$section_active = $fields['section'] == $section_handle;
				$filter_name = "fields[filter][{$section_id}][id]";
				$filter_data = $fields['filter'][$section_data['section']->get('id')];
				
				// Filters:
				$section = $section_data['section'];
				$filter_name = "fields[filter][{$section_id}][id]";
				$filter_data = $fields['filter'][$section_data['section']->get('id')];
				
				$context = new XMLElement('div');
				$context->setAttribute('class', 'context context-' . $section_id);
				$h3 = new XMLElement('h3', __('Filter %s by', array($section->get('name'))));
				$h3->setAttribute('class', 'label');
				$context->appendChild($h3);
				
				$ol = new XMLElement('ol');
				$ol->setAttribute('class', 'filters-duplicator');
				
				if (isset($filter_data['id'])) {
					$li = new XMLElement('li');
					$li->setAttribute('class', 'unique');
					$li->appendChild(new XMLElement('h4', __('System ID')));
					$label = Widget::Label(__('Value'));
					$label->appendChild(Widget::Input(
						"{$filter_name}[id]", General::sanitize($filter_data['id'])
					));
					$li->appendChild($label);
					$ol->appendChild($li);				
				}
				
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique template');
				$li->appendChild(new XMLElement('h4', __('System ID')));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input("{$filter_name}[id]"));
				$li->appendChild($label);
				$ol->appendChild($li);
				
				if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
					foreach ($section_data['fields'] as $input) {
						if (!$input->canFilter()) continue;
						
						$field_id = $input->get('id');
						
						if (isset($filter_data[$field_id])) {
							$filter = new XMLElement('li');
							$filter->setAttribute('class', 'unique');
							$input->displayDatasourceFilterPanel(
								$filter, $filter_data[$field_id],
								$errors[$field_id], $section->get('id')
							);
							$ol->appendChild($filter);					
						}
						
						$filter = new XMLElement('li');
						$filter->setAttribute('class', 'unique template');
						$input->displayDatasourceFilterPanel($filter, null, null, $section->get('id'));
						$ol->appendChild($filter);
					}
				}
				
				$context->appendChild($ol);			
				$container_filter_results->appendChild($context);
				
				// Select boxes:
				$sort_by_options = array(
					array('system:id', ($section_active and $fields['sort_field'] == 'system:id'), __('System ID')),
					array('system:date', ($section_active and $fields['sort_field'] == 'system:date'), __('System Date')),
				);
				$options_parameter_output = array(				
					array(
						'system:id',
						($section_active and in_array('system:id', $fields['output_params'])),
						__('System ID')
					),
					array(
						'system:date',
						($section_active and in_array('system:date', $fields['output_params'])),
						__('System Date')
					),
					array(
						'system:user',
						($section_active and in_array('system:user', $fields['output_params'])),
						__('System User')
					)
				);
				$group_options = array(
					array('', null, __('None')),
					array(
						'system:date',
						($section_active and $fields['group_field'] == 'system:date'),
						__('System Date')
					),
					array(
						'system:user',
						($section_active and $fields['group_field'] == 'system:user'),
						__('System User')
					)
				);
				$included_elements_options = array(
					// TODO: Determine what system fields will be included.
					array(
						'system:date',
						($section_active and in_array('system:date', $fields['included_elements'])),
						__('system:date')
					),
					array(
						'system:user',
						($section_active and in_array('system:user', $fields['included_elements'])),
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
								($section_active and $field_handle == $fields['sort_field']),
								$field_label
							);
						}
						
						if ($field->allowDatasourceParamOutput()) {
							$options_parameter_output[] = array(
								$field_handle,
								($section_active and in_array($field_handle, $fields['output_params'])), 
								$field_label
							);
						}
						
						if ($field->allowDatasourceOutputGrouping()) {
							$group_options[] = array(
								$field_handle,
								($section_active and $field_handle == $fields['group_field']),
								$field_label
							);
						}
						
						if (is_array($modes)) foreach ($modes as $field_mode) {
							$included_elements_options[] = array(
								$field_mode,
								($section_active and in_array($field_mode, $fields['included_elements'])),
								$field_mode
							);
						}
					}
				}
				
				$label = Widget::Label(__('Sort By'));
				$label->setAttribute('class', 'context context-' . $section_id);
				
				$label->appendChild(Widget::Select('fields[sort_field]', $sort_by_options, array('class' => 'filtered')));
				$container_sort_by->appendChild($label);
				
				$label = Widget::Label(__('Use Field'));
				$label->setAttribute('class', 'context context-' . $section_id);
				
				$select = Widget::Select('fields[param][]', $options_parameter_output);
				$select->setAttribute('class', 'filtered');
				$select->setAttribute('multiple', 'multiple');
				
				$label->appendChild($select);
				$container_parameter_output->appendChild($label);
				
				$label = Widget::Label(__('Group By'));
				$label->setAttribute('class', 'context context-' . $section_id);
				
				$select = Widget::Select('fields[group_field]', $group_options);
				$select->setAttribute('class', 'filtered');
				
				$label->appendChild($select);
				$container_xml_output->appendChild($label);
				
				$label = Widget::Label(__('Included Elements'));
				$label->setAttribute('class', 'context context-' . $section_id);
				
				$select = Widget::Select('fields[included_elements][]', $included_elements_options);
				$select->setAttribute('class', 'filtered');
				$select->setAttribute('multiple', 'multiple');
				
				$label->appendChild($select);
				$container_xml_output->appendChild($label);
			}
		}
	}
	
?>
