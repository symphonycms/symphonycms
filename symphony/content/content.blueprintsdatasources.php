<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	
	Class contentBlueprintsDatasources extends AdministrationPage{

		## Both the Edit and New pages need the same form
		function __viewNew(){
			$this->__form();
		}
		
		function __viewEdit(){
			$this->__form();			
		}
		
		function __form(){

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			
			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
						$this->pageAlert(
							__(
								'Data source updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Data sources</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/datasources/new/', 
									URL . '/symphony/blueprints/components/'								)
							), 
							Alert::SUCCESS);
						break;
						
					case 'created':
						$this->pageAlert(
							__(
								'Data source created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Data sources</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/datasources/new/', 
									URL . '/symphony/blueprints/components/' 
								)
							), 
							Alert::SUCCESS);
						break;
					
				}
			}
			
			$sectionManager = new SectionManager($this->_Parent);
			
			if(isset($_POST['fields'])){
				$fields = $_POST['fields'];

				if(!in_array($fields['source'], array('authors', 'navigation', 'dynamic_xml', 'static_xml')) && is_array($fields['filter']) && !empty($fields['filter'])){
					$filters = array();
					foreach($fields['filter'] as $f){
						foreach($f as $key => $val) $filters[$key] = $val;
					}
					
					$fields['filter'][$fields['source']] = $filters;
				}
				
			}
			
			elseif($this->_context[0] == 'edit'){
				
				$isEditing = true;
				$handle = $this->_context[1];
				
				$datasourceManager = new DatasourceManager($this->_Parent);
				$existing =& $datasourceManager->create($handle, NULL, false);
				
				if (!$existing->allowEditorToParse()) redirect(URL . '/symphony/blueprints/datasources/info/' . $handle . '/');
				
				$about = $existing->about();
				$fields['name'] = $about['name'];
				
				$fields['order'] = ($existing->dsParamORDER == 'rand' ? 'random' : $existing->dsParamORDER);
				$fields['param'] = $existing->dsParamPARAMOUTPUT;
				$fields['required_url_param'] = trim($existing->dsParamREQUIREDPARAM);
				
				$fields['xml_elements'] = array();
				if(isset($existing->dsParamINCLUDEDELEMENTS) && is_array($existing->dsParamINCLUDEDELEMENTS)){
					$fields['xml_elements'] = $existing->dsParamINCLUDEDELEMENTS;
				}
				
				$fields['sort'] = $existing->dsParamSORT;
				$fields['page_number'] = $existing->dsParamSTARTPAGE;
				$fields['limit_type'] = $existing->dsParamLIMITTYPE;
				$fields['group'] = $existing->dsParamGROUP;
				$fields['html_encode'] = $existing->dsParamHTMLENCODE;
				$fields['associated_entry_counts'] = $existing->dsParamASSOCIATEDENTRYCOUNTS;				
				if ($fields['associated_entry_counts'] == NULL) $fields['associated_entry_counts'] = 'yes';
				if($existing->dsParamREDIRECTONEMPTY == 'yes') $fields['redirect_on_empty'] = 'yes';
				
				$existing->dsParamFILTERS = @array_map('stripslashes', $existing->dsParamFILTERS);
				
				$fields['source'] = $existing->getSource();
				
				switch($fields['source']){
					case 'authors':					
						$fields['filter']['author'] = $existing->dsParamFILTERS;
						$fields['max_records'] = $existing->dsParamLIMIT;			
						break;
						
					case 'navigation':
						$fields['filter']['navigation'] = $existing->dsParamFILTERS;					
						break;
						
					case 'dynamic_xml':
						$namespaces = $existing->dsParamFILTERS;
						
						$fields['dynamic_xml'] = array('namespace' => array());
						
						$fields['dynamic_xml']['namespace']['name'] = @array_keys($namespaces);
						$fields['dynamic_xml']['namespace']['uri'] = @array_values($namespaces);
					
						$fields['dynamic_xml']['url'] = $existing->dsParamURL;
						$fields['dynamic_xml']['xpath'] = $existing->dsParamXPATH;
						$fields['dynamic_xml']['cache'] = $existing->dsParamCACHE;
						$fields['dynamic_xml']['timeout'] =	(isset($existing->dsParamTIMEOUT) ? $existing->dsParamTIMEOUT : 6);
										
						break;
						
					case 'static_xml':
						$fields['static_xml'] = trim($existing->grab());		
						break;
						
					default:
						$fields['filter'][$fields['source']] = $existing->dsParamFILTERS;	
						$fields['max_records'] = $existing->dsParamLIMIT;			
						break;					
				}	
				
			}
			
			else{
				
				$fields['dynamic_xml']['url'] = 'http://';
				$fields['dynamic_xml']['cache'] = '30';
				$fields['dynamic_xml']['xpath'] = '/';
				$fields['dynamic_xml']['timeout'] = '6';
				
				$fields['max_records'] = '20';
				$fields['page_number'] = '1';
				
				$fields['order'] = 'desc';
				$fields['limit_type'] = 'entries';
				
				$fields['associated_entry_counts'] = NULL;
				
			}
			
			$this->setPageType('form');	
			$this->setTitle(__(($isEditing ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'), array(__('Symphony'), __('Data Sources'), $about['name'])));
			$this->appendSubheading(($isEditing ? $about['name'] : __('Untitled')));
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$div = new XMLElement('div');
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));
			
			if(isset($this->_errors['name'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $div->appendChild($label);
			$group->appendChild($div);
			
			$label = Widget::Label(__('Source'));	
			
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			
			if (!is_array($sections)) $sections = array();
			$field_groups = array();
			
			foreach($sections as $section){
				$field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);
			}
			
			$options = array(
				array('label' => __('System'), 'options' => array(
						array('authors', ($fields['source'] == 'authors'), __('Authors')),
						array('navigation', ($fields['source'] == 'navigation'), __('Navigation')),
				)),
				array('label' => __('Custom XML'), 'options' => array(
						array('dynamic_xml', ($fields['source'] == 'dynamic_xml'), __('Dynamic XML')),
						array('static_xml', ($fields['source'] == 'static_xml'), __('Static XML')),
				)),
			);
			
			if(is_array($sections) && !empty($sections)){
				array_unshift($options, array('label' => __('Sections'), 'options' => array()));
				foreach($sections as $s) $options[0]['options'][] = array($s->get('id'), ($fields['source'] == $s->get('id')), $s->get('name'));
			}
			
			$label->appendChild(Widget::Select('fields[source]', $options, array('id' => 'context')));
			$group->appendChild($label);
			
			$fieldset->appendChild($group);
			$this->Form->appendChild($fieldset);
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual sections authors navigation ' . __('Sections') . ' ' . __('System'));
			$fieldset->appendChild(new XMLElement('legend', __('Filter Results')));
			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to filter by page parameters.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			foreach($field_groups as $section_id => $section_data){	

				$div = new XMLElement('div');
				$div->setAttribute('class', 'contextual ' . $section_data['section']->get('id'));
				$h3 = new XMLElement('h3', __('Filter %s by', array($section_data['section']->get('name'))));
				$h3->setAttribute('class', 'label');
				$div->appendChild($h3);
				
				$ol = new XMLElement('ol');
				$ol->setAttribute('class', 'filters-duplicator');

				if(isset($fields['filter'][$section_data['section']->get('id')]['id'])){
					$li = new XMLElement('li');
					$li->setAttribute('class', 'unique');
					$li->appendChild(new XMLElement('h4', __('System ID')));
					$label = Widget::Label(__('Value'));
					$label->appendChild(Widget::Input('fields[filter]['.$section_data['section']->get('id').'][id]', General::sanitize($fields['filter'][$section_data['section']->get('id')]['id'])));
					$li->appendChild($label);
					$ol->appendChild($li);				
				}
				
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique template');
				$li->appendChild(new XMLElement('h4', __('System ID')));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter]['.$section_data['section']->get('id').'][id]'));
				$li->appendChild($label);
				$ol->appendChild($li);
				
				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $input){
					
						if(!$input->canFilter()) continue;
								
						if(isset($fields['filter'][$section_data['section']->get('id')][$input->get('id')])){
							$wrapper = new XMLElement('li');
							$wrapper->setAttribute('class', 'unique');
							$input->displayDatasourceFilterPanel($wrapper, $fields['filter'][$section_data['section']->get('id')][$input->get('id')], $this->_errors[$input->get('id')], $section_data['section']->get('id'));
							$ol->appendChild($wrapper);					
						}
				
						$wrapper = new XMLElement('li');
						$wrapper->setAttribute('class', 'unique template');
						$input->displayDatasourceFilterPanel($wrapper, NULL, NULL, $section_data['section']->get('id'));
						$ol->appendChild($wrapper);

					}
				}
				
				$div->appendChild($ol);			

				$fieldset->appendChild($div);
				
			}
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'contextual authors');
			$h3 = new XMLElement('h3', __('Filter Authors by'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			
			$this->__appendAuthorFilter($ol, __('ID'), 'id', $fields['filter']['author']['id'], (!isset($fields['filter']['author']['id'])));	
			$this->__appendAuthorFilter($ol, __('Username'), 'username', $fields['filter']['author']['username'], (!isset($fields['filter']['author']['username'])));
			$this->__appendAuthorFilter($ol, __('First Name'), 'first_name', $fields['filter']['author']['first_name'], (!isset($fields['filter']['author']['first_name'])));
			$this->__appendAuthorFilter($ol, __('Last Name'), 'last_name', $fields['filter']['author']['last_name'], (!isset($fields['filter']['author']['last_name'])));
			$this->__appendAuthorFilter($ol, __('Email'), 'email', $fields['filter']['author']['email'], (!isset($fields['filter']['author']['email'])));
			$this->__appendAuthorFilter($ol, __('User Type'), 'user_type', $fields['filter']['author']['user_type'], (!isset($fields['filter']['author']['user_type'])));
								
			$div->appendChild($ol);			
						
			$fieldset->appendChild($div);


			$div = new XMLElement('div');
			$div->setAttribute('class', 'contextual navigation');
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
			if($types = $this->__fetchAvailablePageTypes()) foreach($types as $type) $ul->appendChild(new XMLElement('li', $type));

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
			$this->Form->appendChild($fieldset);


			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual inverse static_xml dynamic_xml');
			$fieldset->appendChild(new XMLElement('legend', __('Sorting and Limiting')));
			
			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to limit by page parameters.'));
			$p->setAttribute('class', 'help contextual inverse navigation');
			$fieldset->appendChild($p);				
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group contextual sections ' . __('Sections'));
			
			$label = Widget::Label(__('Sort By'));
			
			$options = array(
				array('label' => __('Authors'), 'options' => array(	
						array('id', ($fields['source'] == 'authors' && $fields['sort'] == 'id'), __('Author ID')),
						array('username', ($fields['source'] == 'authors' && $fields['sort'] == 'username'), __('Username')),
						array('first-name', ($fields['source'] == 'authors' && $fields['sort'] == 'first-name'), __('First Name')),
						array('last-name', ($fields['source'] == 'authors' && $fields['sort'] == 'last-name'), __('Last Name')),
						array('email', ($fields['source'] == 'authors' && $fields['sort'] == 'email'), __('Email')),
						array('status', ($fields['source'] == 'authors' && $fields['sort'] == 'status'), __('Status')),
						)
					),
					
				array('label' => __('Navigation'), 'options' => array(	
						array('id', ($fields['source'] == 'navigation' && $fields['sort'] == 'id'), __('Page ID')),
						array('handle', ($fields['source'] == 'navigation' && $fields['sort'] == 'handle'), __('Handle')),
						array('sortorder', ($fields['source'] == 'navigation' && $fields['sort'] == 'sortorder'), __('Sort Order')),

						)
					),									
			);
			
			foreach($field_groups as $section_id => $section_data){	
			
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array(
					array('system:id', ($fields['source'] == $section_data['section']->get('id') && $fields['sort'] == 'system:id'), __('System ID')),
					array('system:date', ($fields['source'] == $section_data['section']->get('id') && $fields['sort'] == 'system:date'), __('System Date')),
				));

				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $input){
				
						if(!$input->isSortable()) continue;
				
						$optgroup['options'][] = array($input->get('element_name'), ($fields['source'] == $section_data['section']->get('id') && $input->get('element_name') == $fields['sort']), $input->get('label'));
					}
				}
				
				$options[] = $optgroup;
			}			
			
			$label->appendChild(Widget::Select('fields[sort]', $options, array('class' => 'filtered')));
			$div->appendChild($label);
			

			$label = Widget::Label(__('Sort Order'));
			
			$options = array(
				array('asc', ('asc' == $fields['order']), __('ascending')),
				array('desc', ('desc' == $fields['order']), __('descending')),
				array('random', ('random' == $fields['order']), __('random')),
			);
			
			// Retain custom sort order
			if(!in_array($fields['order'], array('asc', 'desc', 'random'))){
				$options[] = array($fields['order'], true, $fields['order']);
			}
			
			$label->appendChild(Widget::Select('fields[order]', $options));
			$div->appendChild($label);
			
			$fieldset->appendChild($div);
				
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group contextual inverse navigation');

			$div = new XMLElement('div');
			$label = Widget::Label();
			$input = Widget::Input('fields[max_records]', $fields['max_records'], NULL, array('size' => '6'));
			$label->setValue(__('Show a maximum of %s results per page', array($input->generate(false))));
			if(isset($this->_errors['max_records'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['max_records']));
			else $div->appendChild($label);
			$group->appendChild($div);
			
			$div = new XMLElement('div');
			$label = Widget::Label();
			$input = Widget::Input('fields[page_number]', $fields['page_number'], NULL, array('size' => '6'));		
			$label->setValue(__('Show page %s of results', array($input->generate(false))));
			if(isset($this->_errors['page_number'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['page_number']));
			else $div->appendChild($label);
			$group->appendChild($div);
			
			$fieldset->appendChild($group);
			
			$label = Widget::Label(__('Required URL Parameter <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields[required_url_param]', trim($fields['required_url_param'])));
			$fieldset->appendChild($label);
			
			$p = new XMLElement('p', __('An empty result will be returned when this parameter does not have a value. Do not wrap the parameter with curly-braces.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);			

			$label = Widget::Label();
			$input = Widget::Input('fields[redirect_on_empty]', 'yes', 'checkbox', (isset($fields['redirect_on_empty']) ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Redirect to 404 page when no results are found', array($input->generate(false))));
			$fieldset->appendChild($label);
						
			$this->Form->appendChild($fieldset);			
		
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual inverse navigation static_xml dynamic_xml');
			$fieldset->appendChild(new XMLElement('legend', __('Output Options')));
	
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'group');
			
			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('Parameter Output')));
			
			$label = Widget::Label(__('Use Field'));
			$options = array(
				array('', false, __('None')),
				array('label' => __('Authors'), 'options' => array(	
						array('id', ($fields['source'] == 'authors' && $fields['param'] == 'id'), __('Author ID')),
						array('username', ($fields['source'] == 'authors' && $fields['param'] == 'username'), __('Username')),
						array('name', ($fields['source'] == 'authors' && $fields['param'] == 'name'), __('Name')),
						array('email', ($fields['source'] == 'authors' && $fields['param'] == 'email'), __('Email')),
						array('user_type', ($fields['source'] == 'authors' && $fields['param'] == 'user_type'), __('User type')),
						)
					),				
			);
			
			foreach($field_groups as $section_id => $section_data){	
			
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array(				
					array('system:id', ($fields['source'] == $section_data['section']->get('id') && $fields['param'] == 'system:id'), __('System ID')),
					array('system:date', ($fields['source'] == $section_data['section']->get('id') && $fields['param'] == 'system:date'), __('System Date')),
					array('system:author', ($fields['source'] == $section_data['section']->get('id') && $fields['param'] == 'system:author'), __('System Author'))
				));
			
				$authorOverride = false;

				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $input){
				
						if(!$input->allowDatasourceParamOutput()) continue;
				
						$optgroup['options'][] = array($input->get('element_name'), ($fields['source'] == $section_data['section']->get('id') && $fields['param'] == $input->get('element_name')), $input->get('label'));
					}
				}
				
				$options[] = $optgroup;
			}
			
			$label->appendChild(Widget::Select('fields[param]', $options, array('class' => 'filtered')));
			$li->appendChild($label);

			$p = new XMLElement('p', __('The parameter <code id="output-param-name">$ds-%s</code> will be created with this field\'s value for XSLT or other data sources to use.', array(($this->_context[0] == 'edit' ? $existing->dsParamROOTELEMENT : __('Untitled')))));
			$p->setAttribute('class', 'help');
			$li->appendChild($p);
			
			$ul->appendChild($li);

			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', __('XML Output')));

			$label = Widget::Label(__('Group By'));
			$options = array(
				array('', NULL, __('None')),
			);
			
			foreach($field_groups as $section_id => $section_data){	
				
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array());
				
				$authorOverride = false;
				
				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $input){
					
						if(!$input->allowDatasourceOutputGrouping()) continue;
					
						if($input->get('element_name') == 'author') $authorOverride = true;
					
						$optgroup['options'][] = array($input->get('id'), ($fields['source'] == $section_data['section']->get('id') && $fields['group'] == $input->get('id')), $input->get('label'));
					}
				}
				
				if(!$authorOverride) $optgroup['options'][] = array('author', ($fields['source'] == $section_data['section']->get('id') && $fields['group'] == 'author'), __('Author'));
				
				$options[] = $optgroup;
			}
			
			$label->appendChild(Widget::Select('fields[group]', $options, array('class' => 'filtered')));
			$li->appendChild($label);

			$label = Widget::Label(__('Included Elements'));
			
			$options = array(
				array('label' => __('Authors'), 'options' => array(				
						array('username', ($fields['source'] == 'authors' && in_array('username', $fields['xml_elements'])), 'username'),
						array('name', ($fields['source'] == 'authors' && in_array('name', $fields['xml_elements'])), 'name'),
						array('email', ($fields['source'] == 'authors' && in_array('email', $fields['xml_elements'])), 'email'),
						array('author-token', ($fields['source'] == 'authors' && in_array('author-token', $fields['xml_elements'])), 'author-token'),
						array('default-section', ($fields['source'] == 'authors' && in_array('default-section', $fields['xml_elements'])), 'default-section'),
						array('formatting-preference', ($fields['source'] == 'authors' && in_array('formatting-preference', $fields['xml_elements'])), 'formatting-preference'),
				)),					
			);
			
			foreach($field_groups as $section_id => $section_data){	
				
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array());
				
				$optgroup['options'][] = array(
					'system:pagination', 
					($fields['source'] == $section_data['section']->get('id') && @in_array('system:pagination', $fields['xml_elements'])), 
					'pagination'
				);
				
				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $input){
						$elements = $input->fetchIncludableElements();
					
						if(is_array($elements) && !empty($elements)){
							foreach($elements as $name){
								$selected = false;
						
								if($fields['source'] == $section_data['section']->get('id') && @in_array($name, $fields['xml_elements'])){
									$selected = true;	
								}
						
								$optgroup['options'][] = array($name, $selected, $name);
							}
						}
						
					}
				}
				
				$options[] = $optgroup;
			}
			
			$label->appendChild(Widget::Select('fields[xml_elements][]', $options, array('multiple' => 'multiple', 'class' => 'filtered')));
			$li->appendChild($label);
			
			$label = Widget::Label();
			$label->setAttribute('class', 'contextual inverse authors');			
			$input = Widget::Input('fields[associated_entry_counts]', 'yes', 'checkbox', ((isset($fields['associated_entry_counts']) && $fields['associated_entry_counts'] == 'yes') ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Include a count of entries in associated sections', array($input->generate(false))));
			$li->appendChild($label);
			
			$label = Widget::Label();
			$label->setAttribute('class', 'contextual inverse authors');
			$input = Widget::Input('fields[html_encode]', 'yes', 'checkbox', (isset($fields['html_encode']) ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s HTML-encode text', array($input->generate(false))));
			$li->appendChild($label);
			
			$ul->appendChild($li);

			$fieldset->appendChild($ul);
			$this->Form->appendChild($fieldset);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual dynamic_xml');
			$fieldset->appendChild(new XMLElement('legend', __('Dynamic XML')));	
			$label = Widget::Label(__('URL'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][url]', General::sanitize($fields['dynamic_xml']['url'])));
			if(isset($this->_errors['dynamic_xml']['url'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['url']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to specify dynamic portions of the URL.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Namespace Declarations <i>Optional</i>'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			
			if(is_array($fields['dynamic_xml']['namespace']['name'])){
				
				$namespaces = $fields['dynamic_xml']['namespace']['name'];
				$uri = $fields['dynamic_xml']['namespace']['uri'];
				
				for($ii = 0; $ii < count($namespaces); $ii++){
					
					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('h4', 'Namespace'));

					$group = new XMLElement('div');
					$group->setAttribute('class', 'group');

					$label = Widget::Label(__('Name'));
					$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][name][]', General::sanitize($namespaces[$ii])));
					$group->appendChild($label);

					$label = Widget::Label(__('URI'));
					$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][uri][]', General::sanitize($uri[$ii])));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);					
				}
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->appendChild(new XMLElement('h4', __('Namespace')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][name][]'));
			$group->appendChild($label);
					
			$label = Widget::Label(__('URI'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][uri][]'));
			$group->appendChild($label);
			
			$li->appendChild($group);
			$ol->appendChild($li);
			
			$div->appendChild($ol);
			$fieldset->appendChild($div);
			
			$label = Widget::Label(__('Included Elements'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][xpath]', General::sanitize($fields['dynamic_xml']['xpath'])));	
			if(isset($this->_errors['dynamic_xml']['xpath'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['xpath']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', __('Use an XPath expression to select which elements from the source XML to include.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
		
			$label = Widget::Label();
			$input = Widget::Input('fields[dynamic_xml][cache]', max(1, intval($fields['dynamic_xml']['cache'])), NULL, array('size' => '6'));
			$label->setValue(__('Update cached result every %s minutes', array($input->generate(false))));
			if(isset($this->_errors['dynamic_xml']['cache'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['cache']));
			else $fieldset->appendChild($label);		

			$label = Widget::Label();
			$input = Widget::Input('fields[dynamic_xml][timeout]', max(1, intval($fields['dynamic_xml']['timeout'])), NULL, array('type' => 'hidden'));
			$label->appendChild($input);
			$fieldset->appendChild($label);
		
			$this->Form->appendChild($fieldset);
						
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual static_xml');
			$fieldset->appendChild(new XMLElement('legend', __('Static XML')));	
			$label = Widget::Label(__('Body'));
			$label->appendChild(Widget::Textarea('fields[static_xml]', 12, 50, General::sanitize(stripslashes($fields['static_xml'])), array('class' => 'code')));
			
			if(isset($this->_errors['static_xml'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['static_xml']));
			else $fieldset->appendChild($label);

			$this->Form->appendChild($fieldset);
			
	
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($isEditing ? __('Save Changes') : __('Create Data Source')), 'submit', array('accesskey' => 's')));
			
			if($isEditing){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this data source'), 'type' => 'submit'));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);			
				
		}

		function __viewInfo(){
			$this->setPageType('form');	
			
			$DSManager = new DatasourceManager($this->_Parent);
			$datasource = $DSManager->create($this->_context[1], NULL, false);	
			$about = $datasource->about();

			$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(__('Symphony'), __('Data Source'), $about['name'])));
			$this->appendSubheading($about['name']);
			$this->Form->setAttribute('id', 'controller');

			$link = $about['author']['name'];

			if(isset($about['author']['website']))
				$link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));

			elseif(isset($about['author']['email']))
				$link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);
							
			foreach($about as $key => $value) {
				
				$fieldset = NULL;
				
				switch($key) {
					case 'author':
						$fieldset = new XMLElement('fieldset');
						$fieldset->appendChild(new XMLElement('legend', __('Author')));
						$fieldset->appendChild(new XMLElement('p', $link->generate(false)));
						break;
					
					case 'version':
						$fieldset = new XMLElement('fieldset');
						$fieldset->appendChild(new XMLElement('legend', __('Version')));
						$fieldset->appendChild(new XMLElement('p', $value . ', ' . __('released on') . ' ' . DateTimeObj::get(__SYM_DATE_FORMAT__, strtotime($about['release-date']))));
						break;
						
					case 'description':
						$fieldset = new XMLElement('fieldset');
						$fieldset->appendChild(new XMLElement('legend', __('Description')));
						$fieldset->appendChild((is_object($about['description']) ? $about['description'] : new XMLElement('p', $about['description'])));
					
					case 'example':
						if (is_callable(array($datasource, 'example'))) {
							$fieldset = new XMLElement('fieldset');
							$fieldset->appendChild(new XMLElement('legend', __('Example XML')));

							$example = $datasource->example();

							if(is_object($example)) {
								 $fieldset->appendChild($example);
							} else {
								$p = new XMLElement('p');
								$p->appendChild(new XMLElement('pre', '<code>' . str_replace('<', '&lt;', $example) . '</code>'));
								$fieldset->appendChild($p);
							}
						}
						break;
				}
				
				if ($fieldset) {
					$fieldset->setAttribute('class', 'settings');				
					$this->Form->appendChild($fieldset);
				}
				
			}
			
			/*
			$dl->appendChild(new XMLElement('dt', __('URL Parameters')));
			if(!is_array($about['recognised-url-param']) || empty($about['recognised-url-param'])){
				$dl->appendChild(new XMLElement('dd', '<code>'.__('None').'</code>'));
			}			
			else{
				$dd = new XMLElement('dd');
				$ul = new XMLElement('ul');
				
				foreach($about['recognised-url-param'] as $f) $ul->appendChild(new XMLElement('li', '<code>' . $f . '</code>'));

				$dd->appendChild($ul);
				$dl->appendChild($dd);
			}			
			$fieldset->appendChild($dl);
			*/
	
		}
		
		function __actionEdit(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
			elseif(array_key_exists('delete', $_POST['action'])){
				
				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting the datasource file. Target file path is provided.
				#$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array("file" => DATASOURCES . "/data." . $_REQUEST['file'] . ".php"));

		    	if(!General::deleteFile(DATASOURCES . '/data.' . $this->_context[1] . '.php')){
					$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($this->_context[1])), Alert::ERROR);
				}
				else{

					$pages = Symphony::Database()->fetch("SELECT * FROM `tbl_pages` WHERE `data_sources` REGEXP '[[:<:]]".$this->_context[1]."[[:>:]]' ");

					if(is_array($pages) && !empty($pages)){
						foreach($pages as $page){
							
							$data_sources = preg_split('/\s*,\s*/', $page['data_sources'], -1, PREG_SPLIT_NO_EMPTY);
							$data_sources = array_flip($data_sources);
							unset($data_sources[$this->_context[1]]);
							
							$page['data_sources'] = implode(',', array_flip($data_sources));
							
							Symphony::Database()->update($page, 'tbl_pages', "`id` = '".$page['id']."'");
						}
					}
					redirect(URL . '/symphony/blueprints/components/');
				}
			} 
		}
		
		function __actionNew(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
		}
		
		private static function __isValidPageString($string){
			return (bool)preg_match('/^(?:\{\$[\w-]+(?::\$[\w-]+)*(?::\d+)?}|\d+)$/', $string);
			
		}
		
		function __formAction(){
				
			$fields = $_POST['fields'];
			
			$this->_errors = array();
			
			if(trim($fields['name']) == '') $this->_errors['name'] = __('This is a required field');
			
			if($fields['source'] == 'static_xml'){

				if(trim($fields['static_xml']) == '') $this->_errors['static_xml'] = __('This is a required field');
				else{
					$xml_errors = NULL;
					
					include_once(TOOLKIT . '/class.xsltprocess.php');
					
					General::validateXML($fields['static_xml'], $xml_errors, false, new XsltProcess());

					if(!empty($xml_errors)) $this->_errors['static_xml'] = __('XML is invalid');
				}
			}
			
			elseif($fields['source'] == 'dynamic_xml'){
				
				if(trim($fields['dynamic_xml']['url']) == '') $this->_errors['dynamic_xml']['url'] = __('This is a required field');
				
				if(trim($fields['dynamic_xml']['xpath']) == '') $this->_errors['dynamic_xml']['xpath'] = __('This is a required field');
				
				if(!is_numeric($fields['dynamic_xml']['cache'])) $this->_errors['dynamic_xml']['cache'] = __('Must be a valid number');
				elseif($fields['dynamic_xml']['cache'] < 1) $this->_errors['dynamic_xml']['cache'] = __('Must be greater than zero');
				
			}
			
			else{
							
				if($fields['source'] != 'navigation'){
					
					if(strlen(trim($fields['max_records'])) == 0 || (is_numeric($fields['max_records']) && $fields['max_records'] < 1)){
						$this->_errors['max_records'] = __('A result limit must be set');
					}
					elseif(!self::__isValidPageString($fields['max_records'])){
						$this->_errors['max_records'] = __('Must be a valid number or parameter');
					}


					if(strlen(trim($fields['page_number'])) == 0 || (is_numeric($fields['page_number']) && $fields['page_number'] < 1)){
						$this->_errors['page_number'] = __('A page number must be set');
					}
					elseif(!self::__isValidPageString($fields['page_number'])){
						$this->_errors['page_number'] = __('Must be a valid number or parameter');
					}
				}
				
			}

			$classname = Lang::createHandle($fields['name'], NULL, '_', false, true, array('@^[^a-z]+@i' => '', '/[^\w-\.]/i' => ''));
			$rootelement = str_replace('_', '-', $classname);
			
			$file = DATASOURCES . '/data.' . $classname . '.php';
			
			$isDuplicate = false;
			$queueForDeletion = NULL;
			
			if($this->_context[0] == 'new' && is_file($file)) $isDuplicate = true;
			elseif($this->_context[0] == 'edit'){
				$existing_handle = $this->_context[1];
				if($classname != $existing_handle && is_file($file)) $isDuplicate = true;
				elseif($classname != $existing_handle) $queueForDeletion = DATASOURCES . '/data.' . $existing_handle . '.php';			
			}
			
			##Duplicate
			if($isDuplicate) $this->_errors['name'] = __('A Data source with the name <code>%s</code> name already exists', array($classname));
			
			if(empty($this->_errors)){
				
				$dsShell = file_get_contents(TEMPLATE . '/datasource.tpl');
				
				//$oDate = $this->_Parent->getDateObj();
			
				$params = array(
					'rootelement' => $rootelement,
				);
				
				$about = array(
					'name' => $fields['name'],
					'version' => '1.0',
					'release date' => DateTimeObj::getGMT('c'), //date('Y-m-d', $oDate->get(true, false)),
					'author name' => $this->_Parent->Author->getFullName(),
					'author website' => URL,
					'author email' => $this->_Parent->Author->get('email')
				);

				$source = $fields['source'];
				
				$filter = NULL;
				$elements = NULL;
							
				switch($source){
					
					case 'authors':
					
						$filters = $fields['filter']['author'];
						
						$elements = $fields['xml_elements'];
						
						$params['order'] = $fields['order'];
						$params['limit'] = $fields['max_records'];						
						$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
						$params['requiredparam'] = trim($fields['required_url_param']);
						$params['paramoutput'] = $fields['param'];
						$params['sort'] = $fields['sort'];
						$params['startpage'] = $fields['page_number'];
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.author.php');", $dsShell);
						
						break;
						
					case 'navigation':
					
						$filters = $fields['filter']['navigation'];
					
						$params['order'] = $fields['order'];
						$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
						$params['requiredparam'] = trim($fields['required_url_param']);			
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.navigation.php');", $dsShell);
						
						break;
						
					case 'dynamic_xml':
					
						$namespaces = $fields['dynamic_xml']['namespace'];
						
						$filters = array();
						
						for($ii = 0; $ii < count($namespaces['name']); $ii++){
							$filters[$namespaces['name'][$ii]] = $namespaces['uri'][$ii];
						}
						
						$params['url'] = $fields['dynamic_xml']['url'];
						$params['xpath'] = $fields['dynamic_xml']['xpath'];
						$params['cache'] = $fields['dynamic_xml']['cache'];
						$params['timeout'] = (isset($fields['dynamic_xml']['timeout']) ? (int)$fields['dynamic_xml']['timeout'] : '6');
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.dynamic_xml.php');", $dsShell);
						
						break;
						
					case 'static_xml':
						
						$fields['static_xml'] = trim($fields['static_xml']);
						
						if(preg_match('/^<\?xml/i', $fields['static_xml']) == true){
							// Need to remove any XML declaration
							$fields['static_xml'] = preg_replace('/^<\?xml[^>]+>/i', NULL, $fields['static_xml']);
						}
						
						$value = sprintf(
							'$result = "%s";',
							addslashes(trim($fields['static_xml']))
						);
						$dsShell = str_replace('<!-- GRAB -->', $value, $dsShell);
						break;
						
					default:

						$elements = $fields['xml_elements'];

						if(is_array($fields['filter']) && !empty($fields['filter'])){
							$filters = array();
							
							foreach($fields['filter'] as $f){
								foreach($f as $key => $val) $filters[$key] = $val;
							}
						}
						
						$params['order'] = $fields['order'];
						$params['group'] = $fields['group'];
						$params['limit'] = $fields['max_records'];
						$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
						$params['requiredparam'] = trim($fields['required_url_param']);
						$params['paramoutput'] = $fields['param'];
						$params['sort'] = $fields['sort'];
						$params['startpage'] = $fields['page_number'];
						$params['htmlencode'] = $fields['html_encode'];
						$params['associatedentrycounts'] = $fields['associated_entry_counts'];
						
						if ($params['associatedentrycounts'] == NULL) $params['associatedentrycounts'] = 'no';
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.section.php');", $dsShell);
						
						break;
											
				}
				
				$this->__injectVarList($dsShell, $params);
				$this->__injectAboutInformation($dsShell, $about);
				$this->__injectIncludedElements($dsShell, $elements);
				$this->__injectFilters($dsShell, $filters);
				
				$dsShell = str_replace('<!-- CLASS NAME -->', $classname, $dsShell);
				$dsShell = str_replace('<!-- SOURCE -->', $source, $dsShell);
				
				if(preg_match_all('@(\$ds-[-_0-9a-z]+)@i', $dsShell, $matches)){
					$dependancies = General::array_remove_duplicates($matches[1]);
					$dsShell = str_replace('<!-- DS DEPENDANCY LIST -->', "'" . implode("', '", $dependancies) . "'", $dsShell);
				}
								
				## Remove left over placeholders
				$dsShell = preg_replace(array('/<!--[\w ]++-->/', '/(\r\n){2,}/', '/(\t+[\r\n]){2,}/'), '', $dsShell);	

				##Write the file
				if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $dsShell, Symphony::Configuration()->get('write_mode', 'file')))
					$this->pageAlert(__('Failed to write Data source to <code>%s</code>. Please check permissions.', array(DATASOURCES)), Alert::ERROR);

				##Write Successful, add record to the database
				else{
					
					if($queueForDeletion){
						
						General::deleteFile($queueForDeletion);
						
						## Update pages that use this DS
				
						$sql = "SELECT * FROM `tbl_pages` WHERE `data_sources` REGEXP '[[:<:]]".$existing_handle."[[:>:]]' ";
						$pages = Symphony::Database()->fetch($sql);

						if(is_array($pages) && !empty($pages)){
							foreach($pages as $page){
								
								$page['data_sources'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['data_sources']);
								
								Symphony::Database()->update($page, 'tbl_pages', "`id` = '".$page['id']."'");
							}
						}
					}
					
					### TODO: Fix me
					###
					# Delegate: Create
					# Description: After saving the datasource, the file path is provided and an array 
					#              of variables set by the editor
					#$ExtensionManager->notifyMembers('Create', getCurrentPage(), array('file' => $file, 'defines' => $defines, 'var' => $var));

	                redirect(URL . '/symphony/blueprints/datasources/edit/'.$classname.'/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');

				}
			}
		}
		
		function __injectIncludedElements(&$shell, $elements){
			if(!is_array($elements) || empty($elements)) return;
			
			$shell = str_replace('<!-- INCLUDED ELEMENTS -->', "public \$dsParamINCLUDEDELEMENTS = array(" . self::CRLF . "\t\t\t\t'" . implode("'," . self::CRLF . "\t\t\t\t'", $elements) . "'" . self::CRLF . '		);' . self::CRLF, $shell);
			
		}
		
		function __injectFilters(&$shell, $filters){
			if(!is_array($filters) || empty($filters)) return;
			
			$string = 'public $dsParamFILTERS = array(' . self::CRLF;
			           							
			foreach($filters as $key => $val){
				if(trim($val) == '') continue;
				$string .= "\t\t\t\t'$key' => '" . addslashes($val) . "'," . self::CRLF;
			}
			
			$string .= '		);' . self::CRLF;
			
			$shell = str_replace('<!-- FILTERS -->', trim($string), $shell);
			
		}
		
		function __injectAboutInformation(&$shell, $details){
			if(!is_array($details) || empty($details)) return;
			
			foreach($details as $key => $val) $shell = str_replace('<!-- ' . strtoupper($key) . ' -->', addslashes($val), $shell);
		}
		
		function __injectVarList(&$shell, $vars){
			if(!is_array($vars) || empty($vars)) return;
			
			$var_list = NULL;
			foreach($vars as $key => $val){
				if(trim($val) == '') continue;
				$var_list .= '		public $dsParam' . strtoupper($key) . " = '" . addslashes($val) . "';" . self::CRLF;
			}
			
			$shell = str_replace('<!-- VAR LIST -->', trim($var_list), $shell);
			
		}
			
		function __appendAuthorFilter(&$wrapper, $h4_label, $name, $value=NULL, $templateOnly=true){
						
			if(!$templateOnly){				
			
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', $h4_label));		
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][author]['.$name.']', General::sanitize($value)));
				$li->appendChild($label);
			
			 	$wrapper->appendChild($li);	
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', $h4_label));		
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter][author]['.$name.']'));
			$li->appendChild($label);
		
		 	$wrapper->appendChild($li);
						
		}
	
	}
	
