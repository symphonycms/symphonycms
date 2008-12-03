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
			
			if($formHasErrors) $this->pageAlert('An error occurred while processing this form. <a href="#error">See below for details.</a>', AdministrationPage::PAGE_ALERT_ERROR);
			
			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
						$this->pageAlert('{1} updated successfully. <a href="'.URL.'/symphony/{2}">Create another?</a>', AdministrationPage::PAGE_ALERT_NOTICE, array('Data source', 'blueprints/datasources/new/'));
						break;
						
					case 'created':
						$this->pageAlert('{1} created successfully. <a href="'.URL.'/symphony/{2}">Create another?</a>', AdministrationPage::PAGE_ALERT_NOTICE, array('Data source', 'blueprints/datasources/new/'));
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

				$about = $existing->about();
				$fields['name'] = $about['name'];
				$fields['order'] = $existing->dsParamORDER;
				$fields['param'] = $existing->dsParamPARAMOUTPUT;
				$fields['required_url_param'] = $existing->dsParamREQUIREDPARAM;
				$fields['xml_elements'] = $existing->dsParamINCLUDEDELEMENTS;
				$fields['sort'] = $existing->dsParamSORT;
				$fields['page_number'] = $existing->dsParamSTARTPAGE;
				$fields['limit_type'] = $existing->dsParamLIMITTYPE;
				$fields['group'] = $existing->dsParamGROUP;
				$fields['html_encode'] = $existing->dsParamHTMLENCODE;
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
				
				$fields['max_records'] = '20';
				$fields['page_number'] = '1';
				
				$fields['order'] = 'desc';
				$fields['limit_type'] = 'entries';		
				
			}
			
			$this->setPageType('form');	
			$this->setTitle('Symphony &ndash; Data Sources' . ($isEditing ? ' &ndash; ' . $about['name'] : NULL));
			$this->appendSubheading(($isEditing ? $about['name'] : 'Untitled'));
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'Essentials'));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));
			
			if(isset($this->_errors['name'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $div->appendChild($label);
			
			$label = Widget::Label('Source');	
			
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			
			foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);
			
			$options = array(
								
				array('label' => 'System', 'options' => array(
							array('authors', ($fields['source'] == 'authors'), 'Authors'),
							array('navigation', ($fields['source'] == 'navigation'), 'Navigation'),
					)),
							
				array('label' => 'Custom XML', 'options' => array(			
							array('dynamic_xml', ($fields['source'] == 'dynamic_xml'), 'Dynamic XML'),	
							array('static_xml', ($fields['source'] == 'static_xml'), 'Static XML'),
					)),
				
			);
			
			if(is_array($sections) && !empty($sections)){
				array_unshift($options, array('label' => 'Sections', 'options' => array()));
				foreach($sections as $s) $options[0]['options'][] = array($s->get('id'), ($fields['source'] == $s->get('id')), $s->get('name'));
			}
			
			$label->appendChild(Widget::Select('fields[source]', $options, array('id' => 'context')));
			$div->appendChild($label);
			
			$fieldset->appendChild($div);
			$this->Form->appendChild($fieldset);
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual sections authors navigation Sections System');
			$fieldset->appendChild(new XMLElement('legend', 'Filter Results'));
			$p = new XMLElement('p', 'Use <code>{$param}</code> syntax to filter by page parameters.');
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			foreach($field_groups as $section_id => $section_data){	

				$div = new XMLElement('div');
				$div->setAttribute('class', 'subsection contextual ' . $section_data['section']->get('id'));
				
				$div->appendChild(new XMLElement('h3', 'Filter '.$section_data['section']->get('name').' by'));
				
				$ol = new XMLElement('ol');

				if(isset($fields['filter'][$section_data['section']->get('id')]['id'])){
					$li = new XMLElement('li');
					$li->setAttribute('class', 'unique');
					$li->appendChild(new XMLElement('h4', 'System ID'));		
					$label = Widget::Label('Value');
					$label->appendChild(Widget::Input('fields[filter]['.$section_data['section']->get('id').'][id]', General::sanitize($fields['filter'][$section_data['section']->get('id')]['id'])));
					$li->appendChild($label);
					$ol->appendChild($li);				
				}
				
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique template');
				$li->appendChild(new XMLElement('h4', 'System ID'));		
				$label = Widget::Label('Value');
				$label->appendChild(Widget::Input('fields[filter]['.$section_data['section']->get('id').'][id]'));
				$li->appendChild($label);
				$ol->appendChild($li);
				
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
				
				$div->appendChild($ol);			

				$fieldset->appendChild($div);
				
			}
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'subsection contextual authors');

			$div->appendChild(new XMLElement('h3', 'Filter Authors by'));

			$ol = new XMLElement('ol');
			
			$this->__appendAuthorFilter($ol, 'ID', 'id', $fields['filter']['author']['id'], (!isset($fields['filter']['author']['id'])));	
			$this->__appendAuthorFilter($ol, 'Username', 'username', $fields['filter']['author']['username'], (!isset($fields['filter']['author']['username'])));
			$this->__appendAuthorFilter($ol, 'First Name', 'first_name', $fields['filter']['author']['first_name'], (!isset($fields['filter']['author']['first_name'])));
			$this->__appendAuthorFilter($ol, 'Last Name', 'last_name', $fields['filter']['author']['last_name'], (!isset($fields['filter']['author']['last_name'])));
			$this->__appendAuthorFilter($ol, 'Email', 'email', $fields['filter']['author']['email'], (!isset($fields['filter']['author']['email'])));
			$this->__appendAuthorFilter($ol, 'User Type', 'user_type', $fields['filter']['author']['user_type'], (!isset($fields['filter']['author']['user_type'])));
								
			$div->appendChild($ol);			
						
			$fieldset->appendChild($div);


			$div = new XMLElement('div');
			$div->setAttribute('class', 'subsection contextual navigation');

			$div->appendChild(new XMLElement('h3', 'Filter Navigation by'));
			
			$ol = new XMLElement('ol');

			$pages = $this->_Parent->Database->fetch("SELECT * FROM `tbl_pages` ORDER BY `title` ASC");
				
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');
	
			foreach($pages as $page){
				$ul->appendChild(new XMLElement('li', preg_replace('/\/{2,}/i', '/', '/' . $page['path'] . '/' . $page['handle'])));
			}
				
			if(isset($fields['filter']['navigation']['parent'])){
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', 'Parent Page'));		
				$label = Widget::Label('Value');
				$label->appendChild(Widget::Input('fields[filter][navigation][parent]', General::sanitize($fields['filter']['navigation']['parent'])));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', 'Parent Page'));		
			$label = Widget::Label('Value');
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
				$li->appendChild(new XMLElement('h4', 'Page Type'));		
				$label = Widget::Label('Value');
				$label->appendChild(Widget::Input('fields[filter][navigation][type]', General::sanitize($fields['filter']['navigation']['type'])));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', 'Page Type'));		
			$label = Widget::Label('Value');
			$label->appendChild(Widget::Input('fields[filter][navigation][type]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);
			
			$div->appendChild($ol);			
						
			$fieldset->appendChild($div);	
			$this->Form->appendChild($fieldset);


			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual inverse static_xml dynamic_xml');
			$fieldset->appendChild(new XMLElement('legend', 'Sorting and Limiting'));
			
			$p = new XMLElement('p', 'Use <code>{$param}</code> syntax to limit by page parameters.');
			$p->setAttribute('class', 'help contextual inverse navigation');
			$fieldset->appendChild($p);				
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group contextual sections Sections');
			
			$label = Widget::Label('Sort By');
			
			$options = array(
				array('label' => 'Authors', 'options' => array(	
						array('id', ($fields['source'] == 'authors' && $fields['sort'] == 'id'), 'Author ID'),
						array('username', ($fields['source'] == 'authors' && $fields['sort'] == 'username'), 'Username'),
						array('first-name', ($fields['source'] == 'authors' && $fields['sort'] == 'first-name'), 'First Name'),
						array('last-name', ($fields['source'] == 'authors' && $fields['sort'] == 'last-name'), 'Last Name'),
						array('email', ($fields['source'] == 'authors' && $fields['sort'] == 'email'), 'Email'),
						array('status', ($fields['source'] == 'authors' && $fields['sort'] == 'status'), 'Status'),
						)
					),
					
				array('label' => 'Navigation', 'options' => array(	
						array('id', ($fields['source'] == 'navigation' && $fields['sort'] == 'id'), 'Page ID'),
						array('handle', ($fields['source'] == 'navigation' && $fields['sort'] == 'handle'), 'Handle'),
						array('sortorder', ($fields['source'] == 'navigation' && $fields['sort'] == 'sortorder'), 'Sort Order'),

						)
					),									
			);
			
			foreach($field_groups as $section_id => $section_data){	
			
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array(
					array('system:id', ($fields['source'] == $section_data['section']->get('id') && $fields['sort'] == 'system:id'), 'System ID'),
					array('system:date', ($fields['source'] == $section_data['section']->get('id') && $fields['sort'] == 'system:date'), 'System Date'),
				));
			
				foreach($section_data['fields'] as $input){
				
					if(!$input->isSortable()) continue;
				
					$optgroup['options'][] = array($input->get('element_name'), ($fields['source'] == $section_data['section']->get('id') && $input->get('element_name') == $fields['sort']), $input->get('label'));
				}
			
				$options[] = $optgroup;
			}			
			
			$label->appendChild(Widget::Select('fields[sort]', $options, array('class' => 'filtered')));
			$div->appendChild($label);
			

			$label = Widget::Label('Sort Order');
			
			$options = array(
				array('asc', ('asc' == $fields['order']), 'Ascending'),
				array('desc', ('desc' == $fields['order']), 'Descending'),
				array('rand', ('rand' == $fields['order']), 'Random'),
			);
			
			$label->appendChild(Widget::Select('fields[order]', $options));
			$div->appendChild($label);
			
			$fieldset->appendChild($div);
				
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group contextual inverse navigation');

			$label = Widget::Label();
			$input = Widget::Input('fields[max_records]', $fields['max_records'], NULL, array('size' => '6'));
			$label->setValue('Show a maximum of ' . $input->generate(false) . ' results');
			if(isset($this->_errors['max_records'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['max_records']));
			else $div->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('fields[page_number]', $fields['page_number'], NULL, array('size' => '6'));		
			$label->setValue('Show page ' . $input->generate(false) . ' of results');
			if(isset($this->_errors['page_number'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['page_number']));
			else $div->appendChild($label);
			
			$fieldset->appendChild($div);
			
			$label = Widget::Label('Required URL Parameter <i>Optional</i> ');
			$label->appendChild(Widget::Input('fields[required_url_param]', $fields['required_url_param']));
			$fieldset->appendChild($label);
			
			$p = new XMLElement('p', 'An empty result will be returned when this parameter does not have a value.');
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);			

			$label = Widget::Label();
			$input = Widget::Input('fields[redirect_on_empty]', 'yes', 'checkbox', (isset($fields['redirect_on_empty']) ? array('checked' => 'checked') : NULL));
			$label->setValue($input->generate(false) . ' Redirect to 404 page when no results are found');
			$fieldset->appendChild($label);
						
			$this->Form->appendChild($fieldset);			
		
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual inverse navigation static_xml dynamic_xml');
			$fieldset->appendChild(new XMLElement('legend', 'Output Options'));
	
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'group');
			
			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', 'Parameter Output'));
			
			$label = Widget::Label('Use Field');
			$options = array(
				array('', false, 'None'),
				array('label' => 'Authors', 'options' => array(	
						array('id', ($fields['source'] == 'authors' && $fields['param'] == 'id'), 'Author ID'),
						array('username', ($fields['source'] == 'authors' && $fields['param'] == 'username'), 'Username'),
						array('name', ($fields['source'] == 'authors' && $fields['param'] == 'name'), 'Name'),
						array('email', ($fields['source'] == 'authors' && $fields['param'] == 'email'), 'Email'),
						array('user_type', ($fields['source'] == 'authors' && $fields['param'] == 'user_type'), 'User type'),
						)
					),				
			);
			
			foreach($field_groups as $section_id => $section_data){	
			
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array(				
					array('system:id', ($fields['source'] == $section_data['section']->get('id') && $fields['param'] == 'system:id'), 'System ID'),
					array('system:date', ($fields['source'] == $section_data['section']->get('id') && $fields['param'] == 'system:date'), 'System Date'),
					array('system:author', ($fields['source'] == $section_data['section']->get('id') && $fields['param'] == 'system:author'), 'System Author')
				));
			
				$authorOverride = false;
				
				foreach($section_data['fields'] as $input){
				
					if(!$input->allowDatasourceParamOutput()) continue;
				
					$optgroup['options'][] = array($input->get('element_name'), ($fields['source'] == $section_data['section']->get('id') && $fields['param'] == $input->get('element_name')), $input->get('label'));
				}
			
				$options[] = $optgroup;
			}
			
			$label->appendChild(Widget::Select('fields[param]', $options, array('class' => 'filtered')));
			$li->appendChild($label);

			$p = new XMLElement('p', 'The parameter <code id="output-param-name">$ds-'.($this->_context[0] == 'edit' ? $existing->dsParamROOTELEMENT : 'untitled').'</code> will be created with this field\'s value for XSLT or other data sources to use.');
			$p->setAttribute('class', 'help');
			$li->appendChild($p);
			
			$ul->appendChild($li);

			$li = new XMLElement('li');
			$li->appendChild(new XMLElement('h3', 'XML Output'));

			$label = Widget::Label('Group By');
			$options = array(
				array('', NULL, 'None'),
			);
			
			foreach($field_groups as $section_id => $section_data){	
				
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array());
				
				$authorOverride = false;
				
				foreach($section_data['fields'] as $input){
					
					if(!$input->allowDatasourceOutputGrouping()) continue;
					
					if($input->get('element_name') == 'author') $authorOverride = true;
					
					$optgroup['options'][] = array($input->get('id'), ($fields['source'] == $section_data['section']->get('id') && $fields['group'] == $input->get('id')), $input->get('label'));
				}
				
				if(!$authorOverride) $optgroup['options'][] = array('author', ($fields['source'] == $section_data['section']->get('id') && $fields['group'] == 'author'), 'Author');
				
				$options[] = $optgroup;
			}
			
			$label->appendChild(Widget::Select('fields[group]', $options, array('class' => 'filtered')));
			$li->appendChild($label);

			$label = Widget::Label('Included Elements');
			
			$options = array(

				array('label' => 'Authors', 'options' => array(				
						array('username', ($fields['source'] == 'authors' && in_array('username', $fields['xml_elements'])), 'username'),
						array('name', ($fields['source'] == 'authors' && in_array('name', $fields['xml_elements'])), 'name'),
						array('email', ($fields['source'] == 'authors' && in_array('email', $fields['xml_elements'])), 'email'),
						array('author-token', ($fields['source'] == 'authors' && in_array('author-token', $fields['xml_elements'])), 'author-token'),
						array('default-section', ($fields['source'] == 'authors' && in_array('default-section', $fields['xml_elements'])), 'default-section'),
						array('formatting-preference', ($fields['source'] == 'authors' && in_array('formatting-preference', $fields['xml_elements'])), 'formatting-preference'),
						)
					),					
			);
			
			foreach($field_groups as $section_id => $section_data){	
				
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array());
				
				$optgroup['options'][] = array('system:pagination', ($fields['source'] == $section_data['section']->get('id') && @in_array('system:pagination', $fields['xml_elements'])), 'pagination');
				
				foreach($section_data['fields'] as $input){
					$elements = $input->fetchIncludableElements();
					foreach($elements as $e) $optgroup['options'][] = array($e, ($fields['source'] == $section_data['section']->get('id') && @in_array($e, $fields['xml_elements'])), $e);
				}
				
				$options[] = $optgroup;
			}
			
			$label->appendChild(Widget::Select('fields[xml_elements][]', $options, array('multiple' => 'multiple', 'class' => 'filtered')));
			$li->appendChild($label);			
			
			$label = Widget::Label();
			$label->setAttribute('class', 'contextual inverse authors');
			$input = Widget::Input('fields[html_encode]', 'yes', 'checkbox', (isset($fields['html_encode']) ? array('checked' => 'checked') : NULL));
			$label->setValue($input->generate(false) . ' HTML-encode text');
			$li->appendChild($label);
			
			$ul->appendChild($li);

			$fieldset->appendChild($ul);
			$this->Form->appendChild($fieldset);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual dynamic_xml');
			$fieldset->appendChild(new XMLElement('legend', 'Dynamic XML'));	
			$label = Widget::Label('URL');
			$label->appendChild(Widget::Input('fields[dynamic_xml][url]', General::sanitize($fields['dynamic_xml']['url'])));
			if(isset($this->_errors['dynamic_xml']['url'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['url']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', 'Use <code>{$param}</code> syntax to specify dynamic portions of the URL.');
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'subsection');
			$div->appendChild(new XMLElement('h3', 'Namespace Declarations <i>Optional</i>'));
			
			$ol = new XMLElement('ol');
			
			if(is_array($fields['dynamic_xml']['namespace']['name'])){
				
				$namespaces = $fields['dynamic_xml']['namespace']['name'];
				$uri = $fields['dynamic_xml']['namespace']['uri'];
				
				for($ii = 0; $ii < count($namespaces); $ii++){
					
					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('h4', 'Namespace'));

					$group = new XMLElement('div');
					$group->setAttribute('class', 'group');

					$label = Widget::Label('Name ');
					$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][name][]', General::sanitize($namespaces[$ii])));
					$group->appendChild($label);

					$label = Widget::Label('URI ');
					$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][uri][]', General::sanitize($uri[$ii])));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);					
				}
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->appendChild(new XMLElement('h4', 'Namespace'));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label('Name ');
			$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][name][]'));
			$group->appendChild($label);
					
			$label = Widget::Label('URI ');
			$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][uri][]'));
			$group->appendChild($label);
			
			$li->appendChild($group);
			$ol->appendChild($li);
			
			$div->appendChild($ol);
			$fieldset->appendChild($div);
			
			$label = Widget::Label('Included Elements');
			$label->appendChild(Widget::Input('fields[dynamic_xml][xpath]', General::sanitize($fields['dynamic_xml']['xpath'])));	
			if(isset($this->_errors['dynamic_xml']['xpath'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['xpath']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', 'Use an XPath expression to select which elements from the source XML to include.');
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
		
			$label = Widget::Label();
			$input = Widget::Input('fields[dynamic_xml][cache]', max(1, intval($fields['dynamic_xml']['cache'])), NULL, array('size' => '6'));
			$label->setValue('Update cached result every ' . $input->generate(false) . ' minutes');
			if(isset($this->_errors['dynamic_xml']['cache'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['cache']));
			else $fieldset->appendChild($label);		
		
			$this->Form->appendChild($fieldset);
						
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual static_xml');
			$fieldset->appendChild(new XMLElement('legend', 'Static XML'));	
			$label = Widget::Label('Body');
			$label->appendChild(Widget::Textarea('fields[static_xml]', 12, 50, General::sanitize($fields['static_xml']), array('class' => 'code')));
			
			if(isset($this->_errors['static_xml'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['static_xml']));
			else $fieldset->appendChild($label);

			$this->Form->appendChild($fieldset);
			
	
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($isEditing ? 'Save Changes' : 'Create Data Source'), 'submit', array('accesskey' => 's')));
			
			if($isEditing){
				$button = new XMLElement('button', 'Delete');
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => 'Delete this data source'));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);			
				
		}

		function __viewInfo(){
			$this->setPageType('form');	
			
			$DSManager = new DatasourceManager($this->_Parent);
			$datasource = $DSManager->create($this->_context[1]);	
			$about = $datasource->about();

			$this->setTitle('Symphony &ndash; Data source &ndash; ' . $about['name']);
			$this->appendSubheading($about['name']);
			$this->Form->setAttribute('id', 'controller');

			$link = $about['author']['name'];

			if(isset($about['author']['website']))
				$link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));

			elseif(isset($about['author']['email']))
				$link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);
							
			$fieldset = new XMLElement('fieldset');
			$dl = new XMLElement('dl');
			
			$dl->appendChild(new XMLElement('dt', 'Author'));
			$dl->appendChild(new XMLElement('dd', $link->generate(false)));
			
			$dl->appendChild(new XMLElement('dt', 'Version'));
			$dl->appendChild(new XMLElement('dd', $about['version']));	
					
			$dl->appendChild(new XMLElement('dt', 'Release Date'));
			$dl->appendChild(new XMLElement('dd', DateTimeObj::get(__SYM_DATE_FORMAT__, strtotime($about['release-date'])))); //$date->get(true, true, strtotime($about['release-date']))));			
			
			$fieldset->appendChild($dl);
			
			$dl = new XMLElement('dl');
			$dl->setAttribute('class', 'important');
			
			$dl->appendChild(new XMLElement('URL Parameters'));
			if(!is_array($about['recognised-url-param']) || empty($about['recognised-url-param'])){
				$dl->appendChild(new XMLElement('dd', '<code>None</code>'));
			}
			
			else{
				
				$dd = new XMLElement('dd');
				$ul = new XMLElement('ul');
				
				foreach($about['recognised-url-param'] as $f) $ul->appendChild(new XMLElement('li', '<code>' . $f . '</code>'));

				$dd->appendChild($ul);
				$dl->appendChild($dd);
				
			}
			
			$fieldset->appendChild($dl);
			
			$fieldset->appendChild((is_object($about['description']) ? $about['description'] : new XMLElement('p', $about['description'])));


			if(is_callable(array($datasource, 'example'))){
				$fieldset->appendChild(new XMLElement('h3', 'Example XML'));
				
				$example = $datasource->example();
				
				if(is_object($example)) $fieldset->appendChild($example);
				else{
					$p = new XMLElement('p');
					$p->appendChild(new XMLElement('pre', '<code>' . str_replace('<', '&lt;', $datasource->example()) . '</code>'));
					$fieldset->appendChild($p);
				}
			}
			
			$this->Form->appendChild($fieldset);
	
		}
		
		function __actionEdit(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
			elseif(array_key_exists('delete', $_POST['action'])){
				
				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting the datasource file. Target file path is provided.
				#$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array("file" => DATASOURCES . "/data." . $_REQUEST['file'] . ".php"));

		    	if(!General::deleteFile(DATASOURCES . '/data.' . $this->_context[1] . '.php'))
					$this->pageAlert('Failed to delete <code>'.$this->_context[1].'</code>. Please check permissions.', AdministrationPage::PAGE_ALERT_ERROR);

		    	else redirect(URL . '/symphony/blueprints/components/');
						
			} 
		}
		
		function __actionNew(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
		}
		
		function __formAction(){
				
			$fields = $_POST['fields'];
			
			$this->_errors = array();
			
			if(trim($fields['name']) == '') $this->_errors['name'] = 'This is a required field';
			
			if($fields['source'] == 'static_xml'){

				if(trim($fields['static_xml']) == '') $this->_errors['static_xml'] = 'This is a required field';
				else{
					$xml_errors = NULL;
					
					include_once(TOOLKIT . '/class.xsltprocess.php');
					
					General::validateXML($fields['static_xml'], $xml_errors, false, new XsltProcess());

					if(!empty($xml_errors)) $this->_errors['static_xml'] = 'XML is invalid';
				}
			}
			
			elseif($fields['source'] == 'dynamic_xml'){
				
				if(trim($fields['dynamic_xml']['url']) == '') $this->_errors['dynamic_xml']['url'] = 'This is a required field';
				
				if(trim($fields['dynamic_xml']['xpath']) == '') $this->_errors['dynamic_xml']['xpath'] = 'This is a required field';
				
				if(!is_numeric($fields['dynamic_xml']['cache'])) $this->_errors['dynamic_xml']['cache'] = 'Must be a valid number';
				elseif($fields['dynamic_xml']['cache'] < 1) $this->_errors['dynamic_xml']['cache'] = 'Must be greater than zero';					
				
			}
			
			else{
							
				if($fields['source'] != 'navigation'){
					if(!preg_match('@({\$([A-Z0-9_-]++)})@i', $fields['max_records']) && !is_numeric($fields['max_records'])) $this->_errors['max_records'] = 'Must be a valid number or parameter';
					elseif(is_numeric($fields['max_records']) && $fields['max_records'] < 1) $this->_errors['max_records'] = 'A result limit must be set';
							
				}
				
				if($fields['source'] != 'navigation'){
					if(!preg_match('@({\$([A-Z0-9_-]++)})@i', $fields['page_number']) && !is_numeric($fields['page_number'])) $this->_errors['page_number'] = 'Must be a valid number or parameter';
					elseif(is_numeric($fields['page_number']) && $fields['page_number'] < 1) $this->_errors['page_number'] = 'A page number must be set';
				}
				
			}

			$classname = Lang::createHandle($fields['name'], NULL, '_', false, true, array('@^[^a-z]+@i' => '', '/[^\w-\.]/i' => ''));
			$rootelement = str_replace('_', '-', $classname);
			
			$file = DATASOURCES . '/data.' . $classname . '.php';
			
			$isDuplicate = false;
			$queueForDeletion = NULL;
			
			if($this->_context[0] == 'new' && @is_file($file)) $isDuplicate = true;
			elseif($this->_context[0] == 'edit'){
				$existing_handle = $this->_context[1];
				if($classname != $existing_handle && @is_file($file)) $isDuplicate = true;
				elseif($classname != $existing_handle) $queueForDeletion = DATASOURCES . '/data.' . $existing_handle . '.php';			
			}
			
			##Duplicate
			if($isDuplicate) $this->_errors['name'] = 'A Data source with the name <code>'.$classname.'</code> name already exists';
			
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
						$params['requiredparam'] = $fields['required_url_param'];
						$params['paramoutput'] = $fields['param'];
						$params['sort'] = $fields['sort'];
						$params['startpage'] = $fields['page_number'];
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.author.php');", $dsShell);
						
						break;
						
					case 'navigation':
					
						$filters = $fields['filter']['navigation'];
					
						$params['order'] = $fields['order'];
						$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
						$params['requiredparam'] = $fields['required_url_param'];			
						
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
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.dynamic_xml.php');", $dsShell);
						
						break;
						
					case 'static_xml':
												
						$dsShell = str_replace('<!-- GRAB -->', 
						
						'$xml = <<<XML' . self::CRLF .
						'	' . preg_replace('/([\r\n]+)/', '$1	', $fields['static_xml']) . self::CRLF .
						'XML;' . self::CRLF .				
						'			$result = self::CRLF . \'	\' . trim($xml) . self::CRLF;', $dsShell);
					
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
						$params['requiredparam'] = $fields['required_url_param'];
						$params['paramoutput'] = $fields['param'];
						$params['sort'] = $fields['sort'];
						$params['startpage'] = $fields['page_number'];
						$params['htmlencode'] = $fields['html_encode'];
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.section.php');", $dsShell);
						
						break;
											
				}
				
				$this->__injectVarList($dsShell, $params);
				$this->__injectAboutInformation($dsShell, $about);
				$this->__injectIncludedElements($dsShell, $elements);
				$this->__injectFilters($dsShell, $filters);
				
				$dsShell = str_replace('<!-- CLASS NAME -->', $classname, $dsShell);
				$dsShell = str_replace('<!-- SOURCE -->', $source, $dsShell);
				
				if(preg_match_all('@{(\$ds-[^}]+)}@i', $dsShell, $matches)){
					
					$dependancies = array();
					
					foreach($matches[1] as $match){
						if(preg_match_all('/(\$ds-[^:]+)/i', $match, $inner_matches)) $dependancies = array_merge($dependancies, $inner_matches[1]);
					}
					
					$dependancies = General::array_remove_duplicates($dependancies);
					
					$dsShell = str_replace('<!-- DS DEPENDANCY LIST -->', "'" . implode("', '", $dependancies) . "'", $dsShell);
				}
								
				## Remove left over placeholders
				$dsShell = preg_replace(array('/<!--[\w ]++-->/', '/(\r\n){2,}/', '/(\t+[\r\n]){2,}/'), '', $dsShell);	

				##Write the file
				if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $dsShell, $this->_Parent->Configuration->get('write_mode', 'file')))
					$this->pageAlert('Failed to write Data source to <code>'.DATASOURCES.'</code>. Please check permissions.', AdministrationPage::PAGE_ALERT_ERROR);			

				##Write Successful, add record to the database
				else{
					
					if($queueForDeletion){
						
						General::deleteFile($queueForDeletion);
						
						## Update pages that use this DS
				
						$sql = "SELECT * FROM `tbl_pages` WHERE `data_sources` REGEXP '[[:<:]]".$existing_handle."[[:>:]]' ";
						$pages = $this->_Parent->Database->fetch($sql);

						if(is_array($pages) && !empty($pages)){
							foreach($pages as $page){
								
								$page['data_sources'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['data_sources']);
								
								$this->_Parent->Database->update($page, 'tbl_pages', "`id` = '".$page['id']."'");
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
				$label = Widget::Label('Value');
				$label->appendChild(Widget::Input('fields[filter][author]['.$name.']', General::sanitize($value)));
				$li->appendChild($label);
			
			 	$wrapper->appendChild($li);	
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', $h4_label));		
			$label = Widget::Label('Value');
			$label->appendChild(Widget::Input('fields[filter][author]['.$name.']'));
			$li->appendChild($label);
		
		 	$wrapper->appendChild($li);
						
		}
	
	}
	
