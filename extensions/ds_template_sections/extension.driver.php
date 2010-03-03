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
			
			$context['fields']['filters'] = $datasource->getFilters();
			$context['fields']['group'] = $datasource->getGroupField();
			$context['fields']['limit'] = $datasource->getLimit();
			$context['fields']['start_page'] = $datasource->getStartPage();
			$context['fields']['required_url_param'] = $datasource->getRequiredURLParam();
			$context['fields']['redirect_on_empty'] = 'no';
			$context['fields']['associated_entry_counts'] = 'no';
			
			if ($datasource->canRedirectOnEmpty()) {
				$context['fields']['redirect_on_empty'] = 'yes';
			}
			
			if ($datasource->canCountAssociatedEntries()) {
				$context['fields']['associated_entry_counts'] = 'yes';
			}
			
			$context['fields']['sort_field'] = $datasource->getSortField();
			$context['fields']['sort_order'] = $datasource->getSortOrder();
			$context['fields']['output_params'] = (array)$datasource->getOutputParams();
			$context['fields']['included_elements'] = (array)$datasource->getIncludedElements();
		}
		
		public function action($context = array()) {
			if ($context['template'] != 'sections') return;
			
			
		}
		
		public function view($context = array()) {
			if ($context['template'] != 'sections') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			$page = Administration::instance()->Page;
			$page->addScriptToHead(URL . '/extensions/ds_template_sections/assets/view.js', 55533140);
			
			// Essential fields:
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			// Name:
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[about][name]', General::sanitize($this->fields['about']['name']));
			$label->appendChild($input);
			
			if (isset($this->errors['about']['name'])) {
				$label = Widget::wrapFormElementWithError($label, $this->errors['about']['name']);
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
			$label->appendChild(Widget::Select('fields[source]', $options, array('id' => 'context')));
			$group->appendChild($label);
			
			$fieldset->appendChild($group);
			$wrapper->appendChild($fieldset);
			
			// Filter results:
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Filter Results')));
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Use <code>{$param}</code> syntax to filter by page parameters.'));
			
			foreach ($field_groups as $section_id => $section_data) {
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
				$fieldset->appendChild($context);
			}
			
			$fieldset->appendChild($help);
			$wrapper->appendChild($fieldset);
			
			
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Sorting and Limiting')));
			
			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to limit by page parameters.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$sort_field = new XMLElement('div');
			
			// Sorting:
			foreach ($field_groups as $section_id => $section_data) {	
				$options = array(
					array('system:id', ($fields['source'] == $section_data['section']->get('id') && $fields['sort'] == 'system:id'), __('System ID')),
					array('system:date', ($fields['source'] == $section_data['section']->get('id') && $fields['sort'] == 'system:date'), __('System Date')),
				);
				
				if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
					foreach ($section_data['fields'] as $input) {
						if (!$input->isSortable()) continue;
						
						$options[] = array(
							$input->get('element_name'),
							($fields['source'] == $section_data['section']->get('id') && $input->get('element_name') == $fields['sort']),
							$input->get('label')
						);
					}
				}
				
				$label = Widget::Label(__('Sort By'));
				$label->setAttribute('class', 'context context-' . $section_id);
				
				$label->appendChild(Widget::Select('fields[sort]', $options, array('class' => 'filtered')));
				$sort_field->appendChild($label);
			}
			
			$group->appendChild($sort_field);
			
			$label = Widget::Label(__('Sort Order'));
			
			$options = array(
				array('asc', ('asc' == $fields['order']), __('ascending')),
				array('desc', ('desc' == $fields['order']), __('descending')),
				array('random', ('random' == $fields['order']), __('random')),
			);
			
			$label->appendChild(Widget::Select('fields[order]', $options));
			$group->appendChild($label);
			
			$fieldset->appendChild($group);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label();
			$input = Widget::Input('fields[max_records]', $fields['max_records'], NULL, array('size' => '6'));
			$label->setValue(__('Show a maximum of %s results', array($input->generate(false))));
			if(isset($errors['max_records'])) $group->appendChild(Widget::wrapFormElementWithError($label, $errors['max_records']));
			else $group->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('fields[page_number]', $fields['page_number'], NULL, array('size' => '6'));		
			$label->setValue(__('Show page %s of results', array($input->generate(false))));
			if(isset($errors['page_number'])) $group->appendChild(Widget::wrapFormElementWithError($label, $errors['page_number']));
			else $group->appendChild($label);
			
			$fieldset->appendChild($group);
			
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
			
			foreach ($field_groups as $section_id => $section_data) {
				$options = array(				
					array('system:id', ($fields['source'] == $section_data['section']->get('id') && in_array('system:id', $fields['param'])), __('System ID')),
					array('system:date', ($fields['source'] == $section_data['section']->get('id') && in_array('system:date', $fields['param'])), __('System Date')),
					array('system:user', ($fields['source'] == $section_data['section']->get('id') && in_array('system:user', $fields['param'])), __('System User'))
				);
				
				foreach ($section_data['fields'] as $input) {
					if (!$input->allowDatasourceParamOutput()) continue;
					
					$options[] = array(
						$input->get('element_name'), 
						($fields['source'] == $section_data['section']->get('id') && in_array($input->get('element_name'), $fields['param'])), 
						$input->get('label')
					);
				}
				
				$label = Widget::Label(__('Use Field'));
				$label->setAttribute('class', 'context context-' . $section_id);
				
				$select = Widget::Select('fields[param][]', $options);
				$select->setAttribute('class', 'filtered');
				$select->setAttribute('multiple', 'multiple');
				
				$label->appendChild($select);
				$li->appendChild($label);
			}
			
			$p = new XMLElement('p', __('The parameter <code id="output-param-name">$ds-%s-FIELD</code> will be created with this field\'s value for XSLT or other data sources to use. <code>FIELD</code> is the element name of the chosen field.', array(($this->_context[0] == 'edit' ? $existing->dsParamROOTELEMENT : __('Untitled')))));
			$p->setAttribute('class', 'help');
			$li->appendChild($p);
			
			$ul->appendChild($li);
			
			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('XML Output')));
			
			foreach ($field_groups as $section_id => $section_data) {
				$options = array(
					array('', null, __('None')),
					array('system:date', ($fields['source'] == $section_data['section']->get('id') && in_array('system:date', $fields['param'])), __('System Date')),
					array('system:user', ($fields['source'] == $section_data['section']->get('id') && in_array('system:user', $fields['param'])), __('System User'))
				);
				
				if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
					foreach ($section_data['fields'] as $input) {
						if (!$input->allowDatasourceOutputGrouping()) continue;
						
						$options[] = array($input->get('id'), ($fields['source'] == $section_data['section']->get('id') && $fields['group'] == $input->get('id')), $input->get('label'));
					}
				}
				
				$label = Widget::Label(__('Group By'));
				$label->setAttribute('class', 'context context-' . $section_id);
				
				$select = Widget::Select('fields[groups]', $options);
				$select->setAttribute('class', 'filtered');
				
				$label->appendChild($select);
				$li->appendChild($label);
				
				$options = array(
					// TODO: Determine what system fields will be included.
					array('system:date', ($fields['source'] == $section_data['section']->get('id') && in_array('system:date', $fields['param'])), __('system:date')),
					array('system:user', ($fields['source'] == $section_data['section']->get('id') && in_array('system:user', $fields['param'])), __('system:user')),
					array(
						'system:pagination', 
						($fields['source'] == $section_data['section']->get('id') && @in_array('system:pagination', $fields['xml_elements'])), 
						'pagination'
					)
				);
				
				if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
					foreach ($section_data['fields'] as $input) {
						$elements = $input->fetchIncludableElements();
						
						if (is_array($elements) && !empty($elements)) {
							foreach ($elements as $name) {
								$options[] = array($name, ($fields['source'] == $section_data['section']->get('id') && @in_array($name, $fields['xml_elements'])), $name);
							}
						}
					}
				}
				
				$label = Widget::Label(__('Included Elements'));
				$label->setAttribute('class', 'context context-' . $section_id);
				
				$select = Widget::Select('fields[xml_elements][]', $options);
				$select->setAttribute('class', 'filtered');
				$select->setAttribute('multiple', 'multiple');
				
				$label->appendChild($select);
				$li->appendChild($label);
			}
			
			$label = Widget::Label();
			$input = Widget::Input('fields[associated_entry_counts]', 'yes', 'checkbox', ((isset($fields['associated_entry_counts']) && $fields['associated_entry_counts'] == 'yes') ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Include a count of entries in associated sections', array($input->generate(false))));
			$li->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('fields[html_encode]', 'yes', 'checkbox', (isset($fields['html_encode']) ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s HTML-encode text', array($input->generate(false))));
			$li->appendChild($label);
			
			$ul->appendChild($li);

			$fieldset->appendChild($ul);
			$wrapper->appendChild($fieldset);
		}
	}
	
?>
