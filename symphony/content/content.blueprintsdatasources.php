<?php

	/**
	 * @package content
	 */
	/**
	 * The Datasource Editor page allows a developer to create new datasources
	 * from the four Symphony types, Section, Authors, Navigation, Dynamic XML,
	 * and Static XML
	 */
	require_once(TOOLKIT . '/class.gateway.php');
	require_once(TOOLKIT . '/class.resourcespage.php');
	require_once FACE . '/interface.provider.php';

	Class contentBlueprintsDatasources extends ResourcesPage{

		public $_errors = array();

		public function __viewIndex($resource_type){
			parent::__viewIndex(RESOURCE_TYPE_DS);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Data Sources'), __('Symphony'))));
			$this->appendSubheading(__('Data Sources'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a new data source'), 'create button', NULL, array('accesskey' => 'c')));
		}

		// Both the Edit and New pages need the same form
		public function __viewNew(){
			$this->__form();
		}

		public function __viewEdit(){
			$this->__form();
		}

		public function __form(){
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

			if($formHasErrors) {
				$this->pageAlert(
					__('An error occurred while processing this form. See below for details.')
					, Alert::ERROR
				);
			}
			// These alerts are only valid if the form doesn't have errors
			else if(isset($this->_context[2])) {
				switch($this->_context[2]) {
					case 'saved':
						$this->pageAlert(
							__('Data source updated at %s.', array(DateTimeObj::getTimeAgo()))
							. ' <a href="' . SYMPHONY_URL . '/blueprints/datasources/new/" accesskey="c">'
							. __('Create another?')
							. '</a> <a href="' . SYMPHONY_URL . '/blueprints/datasources/" accesskey="a">'
							. __('View all Data sources')
							. '</a>'
							, Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__('Data source created at %s.', array(DateTimeObj::getTimeAgo()))
							. ' <a href="' . SYMPHONY_URL . '/blueprints/datasources/new/" accesskey="c">'
							. __('Create another?')
							. '</a> <a href="' . SYMPHONY_URL . '/blueprints/datasources/" accesskey="a">'
							. __('View all Data sources')
							. '</a>'
							, Alert::SUCCESS);
						break;
				}
			}

			$providers = Symphony::ExtensionManager()->getProvidersOf(iProvider::DATASOURCE);
			$isEditing = false;
			$about = $handle = null;
			$fields = array('name'=>null, 'source'=>null, 'filter'=>null, 'required_url_param'=>null, 'param'=>null);

			if(isset($_POST['fields'])){
				$fields = $_POST['fields'];
				$fields['paginate_results'] = ($fields['paginate_results'] == 'on') ? 'yes' : 'no';

				if(
					!in_array($fields['source'], array('authors', 'navigation', 'dynamic_xml', 'static_xml'))
					&& !empty($fields['filter']) && is_array($fields['filter'])
				) {
					$filters = array();
					foreach($fields['filter'] as $f){
						foreach($f as $key => $val) $filters[$key] = $val;
					}

					$fields['filter'][$fields['source']] = $filters;
				}

				if(!isset($fields['xml_elements']) || !is_array($fields['xml_elements'])) {
					$fields['xml_elements'] = array();
				}

				if($this->_context[0] == 'edit') {
					$isEditing = true;
				}
			}

			else if($this->_context[0] == 'edit'){
				$isEditing = true;
				$handle = $this->_context[1];
				$existing = DatasourceManager::create($handle, array(), false);
				$order = isset($existing->dsParamORDER) ? $existing->dsParamORDER : 'asc';

				if (!$existing->allowEditorToParse()) redirect(SYMPHONY_URL . '/blueprints/datasources/info/' . $handle . '/');

				$about = $existing->about();
				$fields['name'] = $about['name'];

				$fields['order'] = ($order == 'rand') ? 'random' : $order;
				$fields['param'] = isset($existing->dsParamPARAMOUTPUT) ? $existing->dsParamPARAMOUTPUT : null;
				$fields['required_url_param'] = isset($existing->dsParamREQUIREDPARAM) ? trim($existing->dsParamREQUIREDPARAM) : null;

				if(isset($existing->dsParamINCLUDEDELEMENTS) && is_array($existing->dsParamINCLUDEDELEMENTS)){
					$fields['xml_elements'] = $existing->dsParamINCLUDEDELEMENTS;
				}
				else {
					$fields['xml_elements'] = array();
				}

				$fields['sort'] = isset($existing->dsParamSORT) ? $existing->dsParamSORT : null;
				$fields['paginate_results'] = isset($existing->dsParamPAGINATERESULTS) ? $existing->dsParamPAGINATERESULTS : 'yes';
				$fields['page_number'] = isset($existing->dsParamSTARTPAGE) ? $existing->dsParamSTARTPAGE : '1';
				$fields['group'] = isset($existing->dsParamGROUP) ? $existing->dsParamGROUP : null;
				$fields['html_encode'] = isset($existing->dsParamHTMLENCODE) ? $existing->dsParamHTMLENCODE : 'no';
				$fields['associated_entry_counts'] = isset($existing->dsParamASSOCIATEDENTRYCOUNTS) ? $existing->dsParamASSOCIATEDENTRYCOUNTS : 'no';
				$fields['redirect_on_empty'] = isset($existing->dsParamREDIRECTONEMPTY) ? $existing->dsParamREDIRECTONEMPTY : 'no';

				if(!isset($existing->dsParamFILTERS) || !is_array($existing->dsParamFILTERS)) {
					$existing->dsParamFILTERS = array();
				}

				if(!empty($existing->dsParamFILTERS)) {
					$existing->dsParamFILTERS = array_map('stripslashes', $existing->dsParamFILTERS);
				}

				$fields['source'] = $existing->getSource();

				$provided = false;
				if(!empty($providers)) {
					foreach($providers as $providerClass => $provider) {
						if($fields['source'] == call_user_func(array($providerClass, 'getClass'))) {
							$fields = array_merge($fields, $existing->settings());
							$provided = true;
							break;
						}
					}
				}

				if($provided == false) {
					switch($fields['source']){
						case 'authors':
							$fields['filter']['author'] = $existing->dsParamFILTERS;
							break;

						case 'navigation':
							$fields['filter']['navigation'] = $existing->dsParamFILTERS;
							break;

						case 'dynamic_xml':
							$fields['dynamic_xml']['namespace'] = $existing->dsParamFILTERS;
							$fields['dynamic_xml']['url'] = $existing->dsParamURL;
							$fields['dynamic_xml']['xpath'] = $existing->dsParamXPATH;
							$fields['dynamic_xml']['cache'] = $existing->dsParamCACHE;
							$fields['dynamic_xml']['timeout'] = (isset($existing->dsParamTIMEOUT) ? $existing->dsParamTIMEOUT : 6);
							break;

						case 'static_xml':
							// Symphony 2.3+
							if (isset($existing->dsParamSTATIC)) {
								$fields['static_xml'] = trim($existing->dsParamSTATIC);
							}
							// Handle Symphony 2.2.2 to 2.3 DS's
							else if(isset($existing->dsSTATIC)) {
								$fields['static_xml'] = trim($existing->dsSTATIC);
							}
							// Handle pre Symphony 2.2.1 Static DS's
							else {
								$fields['static_xml'] = trim($existing->grab());
							}
							break;

						default:
							$fields['filter'][$fields['source']] = $existing->dsParamFILTERS;
							$fields['max_records'] = $existing->dsParamLIMIT;
							break;
					}
				}
			}
			else {
				$fields['dynamic_xml']['url'] = '';
				$fields['dynamic_xml']['cache'] = '30';
				$fields['dynamic_xml']['xpath'] = '/';
				$fields['dynamic_xml']['timeout'] = '6';
				$fields['dynamic_xml']['format'] = 'XML';

				$fields['paginate_results'] = 'yes';
				$fields['max_records'] = '20';
				$fields['page_number'] = '1';

				$fields['order'] = 'desc';
				$fields['associated_entry_counts'] = NULL;
			}

			// Handle name on edited changes, or from reading an edited datasource
			if(isset($about['name'])) {
				$name = $about['name'];
			}
			else if(isset($fields['name'])) {
				$name = $fields['name'];
			}

			$this->setPageType('form');
			$this->setTitle(__(($isEditing ? '%1$s &ndash; %2$s &ndash; %3$s' : '%2$s &ndash; %3$s'), array($name, __('Data Sources'), __('Symphony'))));
			$this->appendSubheading(($isEditing ? $name : __('Untitled')));
			$this->insertBreadcrumbs(array(
				Widget::Anchor(__('Data Sources'), SYMPHONY_URL . '/blueprints/datasources/'),
			));

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			$div = new XMLElement('div', NULL, array('class' => 'column'));
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));

			if(isset($this->_errors['name'])) $div->appendChild(Widget::Error($label, $this->_errors['name']));
			else $div->appendChild($label);
			$group->appendChild($div);

			$div = new XMLElement('div', NULL, array('class' => 'column'));
			$label = Widget::Label(__('Source'));

			$sections = SectionManager::fetch(NULL, 'ASC', 'name');

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

			// Loop over the datasource providers
			if(!empty($providers)) {
				$p = array('label' => __('From extensions'), 'data-label' => 'from_extensions', 'options' => array());

				foreach($providers as $providerClass => $provider) {
					$p['options'][] = array(
						$providerClass, ($fields['source'] == $providerClass), $provider
					);
				}

				$options[] = $p;
			}

			// Add Sections
			if(is_array($sections) && !empty($sections)){
				array_unshift($options, array('label' => __('Sections'), 'options' => array()));
				foreach($sections as $s) $options[0]['options'][] = array($s->get('id'), ($fields['source'] == $s->get('id')), General::sanitize($s->get('name')));
			}

			$label->appendChild(Widget::Select('fields[source]', $options, array('id' => 'ds-context')));
			$div->appendChild($label);
			$group->appendChild($div);

			$fieldset->appendChild($group);
			$this->Form->appendChild($fieldset);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual authors navigation ' . __('Sections') . ' ' . __('System'));
			$fieldset->appendChild(new XMLElement('legend', __('Filter Results')));
			$p = new XMLElement('p',
				__('Use %s syntax to filter by page parameters.', array(
					'<code>{' . __('$param') . '}</code>'
				))
			);
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			foreach($field_groups as $section_id => $section_data){
				$div = new XMLElement('div');
				$div->setAttribute('class', 'contextual ' . $section_id);

				$ol = new XMLElement('ol');
				$ol->setAttribute('class', 'filters-duplicator');
				$ol->setAttribute('data-add', __('Add filter'));
				$ol->setAttribute('data-remove', __('Remove filter'));

				// Add system:id filter
				if(
					isset($fields['filter'][$section_id]['system:id'])
					or isset($fields['filter'][$section_id]['id'])
				) {
					$id = isset($fields['filter'][$section_id]['system:id'])
						? $fields['filter'][$section_id]['system:id']
						: $fields['filter'][$section_id]['id'];

					$li = new XMLElement('li');
					$li->setAttribute('class', 'unique');
					$li->setAttribute('data-type', 'system:id');
					$li->appendChild(new XMLElement('header', '<h4>' . __('System ID') . '</h4>'));
					$label = Widget::Label(__('Value'));
					$label->appendChild(Widget::Input('fields[filter]['.$section_id.'][system:id]', General::sanitize($id)));
					$li->appendChild($label);
					$ol->appendChild($li);
				}

				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique template');
				$li->setAttribute('data-type', 'system:id');
				$li->appendChild(new XMLElement('header', '<h4>' . __('System ID') . '</h4>'));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter]['.$section_id.'][system:id]'));
				$li->appendChild($label);
				$ol->appendChild($li);

				// Add system:date filter
				if(
					isset($fields['filter'][$section_id]['system:creation-date'])
					or isset($fields['filter'][$section_id]['system:date'])
				) {
					$creation_date = isset($fields['filter'][$section_id]['system:creation-date'])
						? $fields['filter'][$section_id]['system:creation-date']
						: $fields['filter'][$section_id]['system:date'];

					$li = new XMLElement('li');
					$li->setAttribute('class', 'unique');
					$li->setAttribute('data-type', 'system:creation-date');
					$li->appendChild(new XMLElement('header', '<h4>' . __('System Creation Date') . '</h4>'));
					$label = Widget::Label(__('Value'));
					$label->appendChild(
						Widget::Input('fields[filter]['.$section_id.'][system:creation-date]', General::sanitize($creation_date))
					);
					$li->appendChild($label);
					$ol->appendChild($li);
				}

				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique template');
				$li->setAttribute('data-type', 'system:creation-date');
				$li->appendChild(new XMLElement('header', '<h4>' . __('System Creation Date') . '</h4>'));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter]['.$section_id.'][system:creation-date]'));
				$li->appendChild($label);
				$ol->appendChild($li);

				if(isset($fields['filter'][$section_id]['system:modification-date'])){
					$li = new XMLElement('li');
					$li->setAttribute('class', 'unique');
					$li->setAttribute('data-type', 'system:modification-date');
					$li->appendChild(new XMLElement('header', '<h4>' . __('System Modified Date') . '</h4>'));
					$label = Widget::Label(__('Value'));
					$label->appendChild(Widget::Input('fields[filter]['.$section_id.'][system:modification-date]', General::sanitize($fields['filter'][$section_id]['system:modification-date'])));
					$li->appendChild($label);
					$ol->appendChild($li);
				}

				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique template');
				$li->setAttribute('data-type', 'system:modification-date');
				$li->appendChild(new XMLElement('header', '<h4>' . __('System Modified Date') . '</h4>'));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter]['.$section_id.'][system:modification-date]'));
				$li->appendChild($label);
				$ol->appendChild($li);

				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $input){

						if(!$input->canFilter()) continue;

						if(isset($fields['filter'][$section_id], $fields['filter'][$section_id][$input->get('id')])) {
							$wrapper = new XMLElement('li');
							$wrapper->setAttribute('class', 'unique');
							$wrapper->setAttribute('data-type', $input->get('element_name'));
							$errors = isset($this->_errors[$input->get('id')])
								? $this->_errors[$input->get('id')]
								: array();

							$input->displayDatasourceFilterPanel($wrapper, $fields['filter'][$section_id][$input->get('id')], $errors, $section_id);
							$ol->appendChild($wrapper);
						}

						$wrapper = new XMLElement('li');
						$wrapper->setAttribute('class', 'unique template');
						$wrapper->setAttribute('data-type', $input->get('element_name'));
						$input->displayDatasourceFilterPanel($wrapper, NULL, NULL, $section_id);
						$ol->appendChild($wrapper);

					}
				}

				$div->appendChild($ol);

				$fieldset->appendChild($div);
			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'contextual authors');

			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			$ol->setAttribute('data-add', __('Add filter'));
			$ol->setAttribute('data-remove', __('Remove filter'));

			if(!isset($fields['filter']['author'])) {
				$fields['filter']['author'] = array(
					'id' => null,
					'username' => null,
					'first_name' => null,
					'last_name' => null,
					'email' => null,
					'user_type' => null
				);
			}

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

			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			$ol->setAttribute('data-add', __('Add filter'));
			$ol->setAttribute('data-remove', __('Remove filter'));

			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');

			$pages = PageManager::fetch(false, array('*'), array(), 'title ASC');

			foreach($pages as $page){
				$ul->appendChild(new XMLElement('li', preg_replace('/\/{2,}/i', '/', '/' . $page['path'] . '/' . $page['handle'])));
			}

			if(isset($fields['filter']['navigation']['parent'])){
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->setAttribute('data-type', 'parent');
				$li->appendChild(new XMLElement('header', '<h4>' . __('Parent Page') . '</h4>'));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][navigation][parent]', General::sanitize($fields['filter']['navigation']['parent'])));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->setAttribute('data-type', 'parent');
			$li->appendChild(new XMLElement('header', '<h4>' . __('Parent Page') . '</h4>'));
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter][navigation][parent]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);

			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');
			if($types = PageManager::fetchAvailablePageTypes()) {
				foreach($types as $type) {
					$ul->appendChild(new XMLElement('li', $type));
				}
			}

			if(isset($fields['filter']['navigation']['type'])){
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->setAttribute('data-type', 'type');
				$li->appendChild(new XMLElement('header', '<h4>' . __('Page Type') . '</h4>'));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][navigation][type]', General::sanitize($fields['filter']['navigation']['type'])));
				$li->appendChild($label);
				$li->appendChild($ul);
				$ol->appendChild($li);
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('header', '<h4>' . __('Page Type') . '</h4>'));
			$li->setAttribute('data-type', 'type');
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter][navigation][type]'));
			$li->appendChild($label);
			$li->appendChild($ul);
			$ol->appendChild($li);

			$div->appendChild($ol);

			$fieldset->appendChild($div);
			$this->Form->appendChild($fieldset);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual inverse navigation authors static_xml dynamic_xml from_extensions');
			$fieldset->appendChild(new XMLElement('legend', __('Sorting and Limiting')));

			$p = new XMLElement('p',
				__('Use %s syntax to limit by page parameters.', array(
					'<code>{' . __('$param') . '}</code>'
				))
			);
			$p->setAttribute('class', 'help contextual inverse navigation');
			$fieldset->appendChild($p);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'two columns contextual sections ' . __('Sections'));

			$label = Widget::Label(__('Sort By'), NULL, 'column');

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
				$optgroup = array('label' => General::sanitize($section_data['section']->get('name')), 'options' => array(
					array('system:id', ($fields['source'] == $section_id && $fields['sort'] == 'system:id'), __('System ID')),
					array('system:creation-date', ($fields['source'] == $section_id && ($fields['sort'] == 'system:creation-date' || $fields['sort'] == 'system:date')), __('System Creation Date')),
					array('system:modification-date', ($fields['source'] == $section_id && $fields['sort'] == 'system:modification-date'), __('System Modification Date')),
				));

				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $input){

						if(!$input->isSortable()) continue;

						$optgroup['options'][] = array(
							$input->get('element_name'),
							($fields['source'] == $section_id && $input->get('element_name') == $fields['sort']),
							$input->get('label')
						);
					}
				}

				$options[] = $optgroup;
			}

			$label->appendChild(Widget::Select('fields[sort]', $options, array('class' => 'filtered')));
			$div->appendChild($label);

			$label = Widget::Label(__('Sort Order'), NULL, 'column');

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

			$label = Widget::Label();
			$input = array(
				Widget::Input('fields[paginate_results]', NULL, 'checkbox', ($fields['paginate_results'] == 'yes' ? array('checked' => 'checked') : NULL)),
				Widget::Input('fields[max_records]', isset($fields['max_records']) ? $fields['max_records'] : '10', 'text', array('size' => '6')),
				Widget::Input('fields[page_number]', $fields['page_number'], 'text', array('size' => '6'))
			);
			$label->setValue(__('%1$s Paginate results, limiting to %2$s entries per page. Return page %3$s', array($input[0]->generate(false), $input[1]->generate(false), $input[2]->generate(false))));

			if(isset($this->_errors['max_records'])) $fieldset->appendChild(Widget::Error($label, $this->_errors['max_records']));
			else if(isset($this->_errors['page_number'])) $fieldset->appendChild(Widget::Error($label, $this->_errors['page_number']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', __('Failing to paginate may degrade performance if the number of entries returned is very high.'), array('class' => 'help'));
			$fieldset->appendChild($p);

			$this->Form->appendChild($fieldset);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual inverse navigation static_xml dynamic_xml from_extensions');
			$fieldset->appendChild(new XMLElement('legend', __('Output Options')));

			$label = Widget::Label(__('Required URL Parameter'));
			$label->appendChild(new XMLElement('i', __('Optional')));
			$label->appendChild(Widget::Input('fields[required_url_param]', trim($fields['required_url_param']), 'text', array('placeholder' => __('$param'))));
			$fieldset->appendChild($label);

			$p = new XMLElement('p', __('An empty result will be returned when this parameter does not have a value.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			$label = Widget::Label();
			$input = Widget::Input('fields[redirect_on_empty]', 'yes', 'checkbox', (isset($fields['redirect_on_empty']) && $fields['redirect_on_empty'] == 'yes') ? array('checked' => 'checked') : NULL);
			$label->setValue(__('%s Redirect to 404 page when no results are found', array($input->generate(false))));
			$fieldset->appendChild($label);

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));

			$subfieldset = new XMLElement('fieldset', NULL, array('class' => 'column'));
			$subfieldset->appendChild(new XMLElement('legend', __('Output Parameters')));

			// Support multiple parameters
			if(!isset($fields['param'])) {
				$fields['param'] = array();
			}
			else if(!is_array($fields['param'])) {
				$fields['param'] = array($fields['param']);
			}

			$label = Widget::Label(__('Use Fields'));
			$prefix = '$ds-' . (isset($this->_context[1]) ? Lang::createHandle($fields['name']) : __('unnamed')) . '.';

			$options = array(
				array('label' => __('Authors'), 'options' => array())
			);

			foreach(array('id', 'username', 'name', 'email', 'user_type') as $p){
				$options[0]['options'][] = array(
					$p,
					($fields['source'] == 'authors' && in_array($p, $fields['param'])),
					$prefix . $p,
					null,
					null,
					array(
						'data-handle' => $p
					)
				);
			}

			foreach($field_groups as $section_id => $section_data){
				$optgroup = array('label' => $section_data['section']->get('name'), 'options' => array());

				foreach(array('id', 'creation-date', 'modification-date', 'author') as $p){
					$option = array(
						'system:' . $p,
						($fields['source'] == $section_id && in_array('system:' . $p, $fields['param'])),
						$prefix . 'system-' . $p,
						null,
						null,
						array(
							'data-handle' => 'system-' . $p
						)
					);

					// Handle 'system:date' as an output paramater (backwards compatibility)
					if($p === 'creation-date') {
						if($fields['source'] == $section_id && in_array('system:date', $fields['param'])) {
							$option[1] = true;
						}
					}

					$optgroup['options'][] = $option;
				}

				$authorOverride = false;

				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $input){

						if(!$input->allowDatasourceParamOutput()) continue;

						$optgroup['options'][] = array(
							$input->get('element_name'),
							($fields['source'] == $section_id && in_array($input->get('element_name'), $fields['param'])),
							$prefix . $input->get('element_name'),
							null,
							null,
							array(
								'data-handle' => $input->get('element_name')
							)
						);
					}
				}

				$options[] = $optgroup;
			}

			$label->appendChild(Widget::Select('fields[param][]', $options, array('class' => 'filtered', 'multiple' => 'multiple')));
			$subfieldset->appendChild($label);

			$div->appendChild($subfieldset);

			$subfieldset = new XMLElement('fieldset', NULL, array('class' => 'column'));
			$subfieldset->appendChild(new XMLElement('legend', __('XML Output')));

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

						$optgroup['options'][] = array($input->get('id'), ($fields['source'] == $section_id && $fields['group'] == $input->get('id')), $input->get('label'));
					}
				}

				if(!$authorOverride) $optgroup['options'][] = array('author', ($fields['source'] == $section_id && $fields['group'] == 'author'), __('Author'));

				$options[] = $optgroup;
			}

			$label->appendChild(Widget::Select('fields[group]', $options, array('class' => 'filtered')));
			$subfieldset->appendChild($label);

			$label = Widget::Label(__('Included Elements'));

			$options = array(
				array('label' => __('Authors'), 'options' => array(
						array('username', ($fields['source'] == 'authors' && in_array('username', $fields['xml_elements'])), 'username'),
						array('name', ($fields['source'] == 'authors' && in_array('name', $fields['xml_elements'])), 'name'),
						array('email', ($fields['source'] == 'authors' && in_array('email', $fields['xml_elements'])), 'email'),
						array('author-token', ($fields['source'] == 'authors' && in_array('author-token', $fields['xml_elements'])), 'author-token'),
						array('default-area', ($fields['source'] == 'authors' && in_array('default-area', $fields['xml_elements'])), 'default-area'),
				)),
			);

			foreach($field_groups as $section_id => $section_data){
				$optgroup = array(
					'label' => General::sanitize($section_data['section']->get('name')),
					'options' => array(
						array(
							'system:pagination',
							($fields['source'] == $section_id && in_array('system:pagination', $fields['xml_elements'])),
							'system: pagination'
						),
						array(
							'system:date',
							($fields['source'] == $section_id && in_array('system:date', $fields['xml_elements'])),
							'system: date'
						)
					)
				);

				if(is_array($section_data['fields']) && !empty($section_data['fields'])){
					foreach($section_data['fields'] as $field){
						$elements = $field->fetchIncludableElements();

						if(is_array($elements) && !empty($elements)){
							foreach($elements as $name){
								$selected = false;

								if($fields['source'] == $section_id && in_array($name, $fields['xml_elements'])){
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
			$subfieldset->appendChild($label);

			$label = Widget::Label();
			$label->setAttribute('class', 'contextual inverse authors');
			$input = Widget::Input('fields[associated_entry_counts]', 'yes', 'checkbox', ((isset($fields['associated_entry_counts']) && $fields['associated_entry_counts'] == 'yes') ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Include a count of entries in associated sections', array($input->generate(false))));
			$subfieldset->appendChild($label);

			$label = Widget::Label();
			$label->setAttribute('class', 'contextual inverse authors');
			$input = Widget::Input('fields[html_encode]', 'yes', 'checkbox', (isset($fields['html_encode']) && $fields['html_encode'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s HTML-encode text', array($input->generate(false))));
			$subfieldset->appendChild($label);

			$div->appendChild($subfieldset);

			$fieldset->appendChild($div);
			$this->Form->appendChild($fieldset);

		// Dynamic XML
			if(!isset($fields['dynamic_xml'])) {
				$fields['dynamic_xml'] = array('url'=>null, 'xpath'=>null, 'namespace'=>null, 'cache'=>null, 'timeout'=>null);
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual dynamic_xml');
			$fieldset->appendChild(new XMLElement('legend', __('Dynamic XML')));

			$label = Widget::Label(__('URL'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][url]', General::sanitize($fields['dynamic_xml']['url'])));
			if(isset($this->_errors['dynamic_xml']['url'])) $fieldset->appendChild(Widget::Error($label, $this->_errors['dynamic_xml']['url']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p',
				__('Use %s syntax to specify dynamic portions of the URL.', array(
					'<code>{' . __('$param') . '}</code>'
				))
			);
			$p->setAttribute('class', 'help');
			$label->appendChild($p);

			$div = new XMLElement('div');
			$p = new XMLElement('p', __('Namespace Declarations'));
			$p->appendChild(new XMLElement('i', __('Optional')));
			$p->setAttribute('class', 'label');
			$div->appendChild($p);

			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			$ol->setAttribute('data-add', __('Add namespace'));
			$ol->setAttribute('data-remove', __('Remove namespace'));

			if(is_array($fields['dynamic_xml']['namespace'])){
				$i = 0;
				foreach($fields['dynamic_xml']['namespace'] as $name => $uri){
					// Namespaces get saved to the file as $name => $uri, however in
					// the $_POST they are represented as $index => array. This loop
					// patches the difference.
					if(is_array($uri)) {
						$name = $uri['name'];
						$uri = $uri['uri'];
					}

					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('header', '<h4>' . __('Namespace') . '</h4>'));

					$group = new XMLElement('div');
					$group->setAttribute('class', 'group');

					$label = Widget::Label(__('Name'));
					$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][' . $i .'][name]', General::sanitize($name)));
					$group->appendChild($label);

					$label = Widget::Label(__('URI'));
					$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][' . $i .'][uri]', General::sanitize($uri)));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);
					$i++;
				}
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->setAttribute('data-type', 'namespace');
			$li->appendChild(new XMLElement('header', '<h4>' . __('Namespace') . '</h4>'));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][-1][name]'));
			$group->appendChild($label);

			$label = Widget::Label(__('URI'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][-1][uri]'));
			$group->appendChild($label);

			$li->appendChild($group);
			$ol->appendChild($li);

			$div->appendChild($ol);
			$fieldset->appendChild($div);

			$label = Widget::Label(__('Included Elements'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][xpath]', General::sanitize($fields['dynamic_xml']['xpath'])));
			if(isset($this->_errors['dynamic_xml']['xpath'])) $fieldset->appendChild(Widget::Error($label, $this->_errors['dynamic_xml']['xpath']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', __('Use an XPath expression to select which elements from the source XML to include.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			$label = Widget::Label();
			$input = Widget::Input('fields[dynamic_xml][cache]', (string)max(1, intval($fields['dynamic_xml']['cache'])), 'text', array('size' => '6'));
			$label->setValue(__('Update cached result every %s minutes', array($input->generate(false))));
			if(isset($this->_errors['dynamic_xml']['cache'])) $fieldset->appendChild(Widget::Error($label, $this->_errors['dynamic_xml']['cache']));
			else $fieldset->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('fields[dynamic_xml][timeout]', (string)max(1, intval($fields['dynamic_xml']['timeout'])), 'text', array('type' => 'hidden'));
			$label->appendChild($input);
			$fieldset->appendChild($label);

			$this->Form->appendChild($fieldset);

		// Static XML
			if(!isset($fields['static_xml'])) {
				$fields['static_xml'] = null;
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual static_xml');
			$fieldset->appendChild(new XMLElement('legend', __('Static XML')));
			$label = Widget::Label(__('Body'));
			$label->appendChild(Widget::Textarea('fields[static_xml]', 12, 50, General::sanitize(stripslashes($fields['static_xml'])), array('class' => 'code')));

			if(isset($this->_errors['static_xml'])) $fieldset->appendChild(Widget::Error($label, $this->_errors['static_xml']));
			else $fieldset->appendChild($label);

			$this->Form->appendChild($fieldset);

		// Call the provided datasources to let them inject their filters
		// @todo Ideally when a new Datasource is chosen an AJAX request will fire
		// to get the HTML from the extension. This is hardcoded for now into
		// creating a 'big' page and then hiding the fields with JS
			if(!empty($providers)) {
				foreach($providers as $providerClass => $provider) {
					call_user_func_array(array($providerClass, 'buildEditor'), array($this->Form, &$this->_errors, $fields, $handle));
				}
			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($isEditing ? __('Save Changes') : __('Create Data Source')), 'submit', array('accesskey' => 's')));

			if($isEditing){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this data source'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this data source?')));
				$div->appendChild($button);
			}

			$this->Form->appendChild($div);

		}

		public function __viewInfo(){
			$this->setPageType('form');

			$datasource = DatasourceManager::create($this->_context[1], array(), false);
			$about = $datasource->about();

			$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array($about['name'], __('Data Source'), __('Symphony'))));
			$this->appendSubheading(($isEditing ? $about['name'] : __('Untitled')));
			$this->insertBreadcrumbs(array(
				Widget::Anchor(__('Data Sources'), SYMPHONY_URL . '/blueprints/datasources/'),
			));
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
						if($link) {
							$fieldset = new XMLElement('fieldset');
							$fieldset->appendChild(new XMLElement('legend', __('Author')));
							$fieldset->appendChild(new XMLElement('p', $link->generate(false)));
						}
						break;

					case 'version':
						$fieldset = new XMLElement('fieldset');
						$fieldset->appendChild(new XMLElement('legend', __('Version')));
						$release_date = array_key_exists('release-date', $about) ? $about['release-date'] : filemtime(DatasourceManager::__getDriverPath($this->_context[1]));

						if(preg_match('/^\d+(\.\d+)*$/', $value)) {
							$fieldset->appendChild(new XMLElement('p', __('%1$s released on %2$s', array($value, DateTimeObj::format($release_date, __SYM_DATE_FORMAT__)))));
						}
						else {
							$fieldset->appendChild(new XMLElement('p', __('Created by %1$s at %2$s', array($value, DateTimeObj::format($release_date, __SYM_DATE_FORMAT__)))));
						}
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

		}

		public function __actionIndex($resource_type){
			return parent::__actionIndex(RESOURCE_TYPE_DS);
		}

		public function __actionEdit(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
			elseif(array_key_exists('delete', $_POST['action'])){

				/**
				 * Prior to deleting the Datasource file. Target file path is provided.
				 *
				 * @delegate DatasourcePreDelete
				 * @since Symphony 2.2
				 * @param string $context
				 * '/blueprints/datasources/'
				 * @param string $file
				 *  The path to the Datasource file
				 */
				Symphony::ExtensionManager()->notifyMembers('DatasourcePreDelete', '/blueprints/datasources/', array('file' => DATASOURCES . "/data." . $this->_context[1] . ".php"));

				if(!General::deleteFile(DATASOURCES . '/data.' . $this->_context[1] . '.php')){
					$this->pageAlert(
						__('Failed to delete %s.', array('<code>' . $this->_context[1] . '</code>'))
						. ' ' . __('Please check permissions on %s.', array('<code>/workspace/data-sources</code>'))
						, Alert::ERROR
					);
				}
				else {
					$pages = ResourceManager::getAttachedPages(RESOURCE_TYPE_DS, $this->_context[1]);
					foreach($pages as $page) {
						ResourceManager::detach(RESOURCE_TYPE_DS, $this->_context[1], $page['id']);
					}

					redirect(SYMPHONY_URL . '/blueprints/datasources/');
				}
			}
		}

		public function __actionNew(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
		}

		public function __formAction(){
			$fields = $_POST['fields'];
			$this->_errors = array();
			$providers = Symphony::ExtensionManager()->getProvidersOf(iProvider::DATASOURCE);
			$providerClass = null;

			if(trim($fields['name']) == '') $this->_errors['name'] = __('This is a required field');

			if($fields['source'] == 'static_xml'){

				if(trim($fields['static_xml']) == '') $this->_errors['static_xml'] = __('This is a required field');
				else{
					$xml_errors = NULL;

					include_once(TOOLKIT . '/class.xsltprocess.php');

					General::validateXML($fields['static_xml'], $xml_errors, false, new XsltProcess());

					if(!empty($xml_errors)) $this->_errors['static_xml'] = __('XML is invalid.');
				}
			}

			elseif($fields['source'] == 'dynamic_xml'){
				if(trim($fields['dynamic_xml']['url']) == '') $this->_errors['dynamic_xml']['url'] = __('This is a required field');

				// Use the TIMEOUT that was specified by the user for a real world indication
				$timeout = (isset($fields['dynamic_xml']['timeout']) ? (int)$fields['dynamic_xml']['timeout'] : 6);

				// If there is a parameter in the URL, we can't validate the existence of the URL
				// as we don't have the environment details of where this datasource is going
				// to be executed.
				if(!preg_match('@{([^}]+)}@i', $fields['dynamic_xml']['url'])) {
					$valid_url = self::__isValidURL($fields['dynamic_xml']['url'], $timeout, $error);

					if($valid_url) {
						$data = $valid_url['data'];
					}
					else {
						$this->_errors['dynamic_xml']['url'] = $error;
					}
				}

				if(trim($fields['dynamic_xml']['xpath']) == '') $this->_errors['dynamic_xml']['xpath'] = __('This is a required field');

				if(!is_numeric($fields['dynamic_xml']['cache'])) $this->_errors['dynamic_xml']['cache'] = __('Must be a valid number');
				elseif($fields['dynamic_xml']['cache'] < 1) $this->_errors['dynamic_xml']['cache'] = __('Must be greater than zero');

			}

			elseif(is_numeric($fields['source'])) {

				if(strlen(trim($fields['max_records'])) == 0 || (is_numeric($fields['max_records']) && $fields['max_records'] < 1)){
					if (isset($fields['paginate_results'])) $this->_errors['max_records'] = __('A result limit must be set');
				}
				else if(!self::__isValidPageString($fields['max_records'])){
					$this->_errors['max_records'] = __('Must be a valid number or parameter');
				}

				if(strlen(trim($fields['page_number'])) == 0 || (is_numeric($fields['page_number']) && $fields['page_number'] < 1)){
					if (isset($fields['paginate_results'])) $this->_errors['page_number'] = __('A page number must be set');
				}
				else if(!self::__isValidPageString($fields['page_number'])){
					$this->_errors['page_number'] = __('Must be a valid number or parameter');
				}
			}

			// See if a Provided Datasource is saved
			elseif (!empty($providers)) {
				foreach($providers as $providerClass => $provider) {
					if($fields['source'] == call_user_func(array($providerClass, 'getSource'))) {
						call_user_func_array(array($providerClass, 'validate'), array(&$fields, &$this->_errors));
						break;
					}

					unset($providerClass);
				}
			}

			$classname = Lang::createHandle($fields['name'], 255, '_', false, true, array('@^[^a-z\d]+@i' => '', '/[^\w-\.]/i' => ''));
			$rootelement = str_replace('_', '-', $classname);

			// Check to make sure the classname is not empty after handlisation.
			if(empty($classname) && !isset($this->_errors['name'])) $this->_errors['name'] = __('Please ensure name contains at least one Latin-based character.', array($classname));

			$file = DATASOURCES . '/data.' . $classname . '.php';

			$isDuplicate = false;
			$queueForDeletion = NULL;

			if($this->_context[0] == 'new' && is_file($file)) $isDuplicate = true;
			elseif($this->_context[0] == 'edit'){
				$existing_handle = $this->_context[1];
				if($classname != $existing_handle && is_file($file)) $isDuplicate = true;
				elseif($classname != $existing_handle) $queueForDeletion = DATASOURCES . '/data.' . $existing_handle . '.php';
			}

			// Duplicate
			if($isDuplicate) $this->_errors['name'] = __('A Data source with the name %s already exists', array('<code>' . $classname . '</code>'));

			if(empty($this->_errors)){
				$filters = array();
				$elements = NULL;
				$placeholder = '<!-- GRAB -->';
				$source = $fields['source'];
				$params = array(
					'rootelement' => $rootelement,
				);

				$about = array(
					'name' => $fields['name'],
					'version' => 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'),
					'release date' => DateTimeObj::getGMT('c'),
					'author name' => Administration::instance()->Author->getFullName(),
					'author website' => URL,
					'author email' => Administration::instance()->Author->get('email')
				);

				// If there is a provider, get their template
				if($providerClass) {
					$dsShell = file_get_contents(call_user_func(array($providerClass, 'getTemplate')));
				}
				else {
					$dsShell = file_get_contents($this->getTemplate('blueprints.datasource'));
				}

				// Author metadata
				self::injectAboutInformation($dsShell, $about);

				// Do dependencies, the template file must have <!-- CLASS NAME -->
				$dsShell = str_replace('<!-- CLASS NAME -->', $classname, $dsShell);

				// If there is a provider, let them do the prepartion work
				if($providerClass) {
					$dsShell = call_user_func(array($providerClass, 'prepare'), $fields, $params, $dsShell);
				}
				else {
					switch($source){
						case 'authors':
							$extends = 'AuthorDatasource';
							if(isset($fields['filter']['author'])) {
								$filters = $fields['filter']['author'];
							}

							$elements = $fields['xml_elements'];

							$params['order'] = $fields['order'];
							$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
							$params['requiredparam'] = trim($fields['required_url_param']);
							$params['paramoutput'] = $fields['param'];
							$params['sort'] = $fields['sort'];

							break;

						case 'navigation':
							$extends = 'NavigationDatasource';
							if(isset($fields['filter']['navigation'])) {
								$filters = $fields['filter']['navigation'];
							}

							$params['order'] = $fields['order'];
							$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
							$params['requiredparam'] = trim($fields['required_url_param']);

							break;

						case 'dynamic_xml':
							$extends = 'DynamicXMLDatasource';

							// Automatically detect namespaces
							if(isset($data)) {
								preg_match_all('/xmlns:([a-z][a-z-0-9\-]*)="([^\"]+)"/i', $data, $matches);

								if(!is_array($fields['dynamic_xml']['namespace'])) {
									$fields['dynamic_xml']['namespace'] = array();
								}

								if (isset($matches[2][0])) {
									$detected_namespaces = array();

									foreach ($fields['dynamic_xml']['namespace'] as $name => $uri) {
										$detected_namespaces[] = $name;
										$detected_namespaces[] = $uri;
									}

									foreach ($matches[2] as $index => $uri) {
										$name = $matches[1][$index];

										if (in_array($name, $detected_namespaces) or in_array($uri, $detected_namespaces)) continue;

										$detected_namespaces[] = $name;
										$detected_namespaces[] = $uri;

										$fields['dynamic_xml']['namespace'][] = array(
											'name' => $name,
											'uri' => $uri
										);
									}
								}
							}

							$filters = array();
							if(is_array($fields['dynamic_xml']['namespace'])) foreach($fields['dynamic_xml']['namespace'] as $index => $data) {
								$filters[$data['name']] = $data['uri'];
							}

							$params['url'] = $fields['dynamic_xml']['url'];
							$params['xpath'] = $fields['dynamic_xml']['xpath'];
							$params['cache'] = $fields['dynamic_xml']['cache'];
							$params['format'] = $fields['dynamic_xml']['format'];
							$params['timeout'] = (isset($fields['dynamic_xml']['timeout']) ? (int)$fields['dynamic_xml']['timeout'] : '6');

							break;

						case 'static_xml':
							$extends = 'StaticXMLDatasource';
							$fields['static_xml'] = trim($fields['static_xml']);

							if(preg_match('/^<\?xml/i', $fields['static_xml']) == true){
								// Need to remove any XML declaration
								$fields['static_xml'] = preg_replace('/^<\?xml[^>]+>/i', NULL, $fields['static_xml']);
							}

							$params['static'] = sprintf(
								'%s',
								trim($fields['static_xml'])
							);
							break;

						default:
							$extends = 'SectionDatasource';
							$elements = $fields['xml_elements'];

							if(is_array($fields['filter']) && !empty($fields['filter'])){
								$filters = array();

								foreach($fields['filter'] as $f){
									foreach($f as $key => $val) $filters[$key] = $val;
								}
							}

							$params['order'] = $fields['order'];
							$params['group'] = $fields['group'];
							$params['paginateresults'] = (isset($fields['paginate_results']) ? 'yes' : 'no');
							$params['limit'] = $fields['max_records'];
							$params['startpage'] = $fields['page_number'];
							$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
							$params['requiredparam'] = trim($fields['required_url_param']);
							$params['paramoutput'] = $fields['param'];
							$params['sort'] = $fields['sort'];
							$params['htmlencode'] = $fields['html_encode'];
							$params['associatedentrycounts'] = $fields['associated_entry_counts'];

							if ($params['associatedentrycounts'] == NULL) $params['associatedentrycounts'] = 'no';

							break;
					}

					$this->__injectVarList($dsShell, $params);
					$this->__injectIncludedElements($dsShell, $elements);
					self::injectFilters($dsShell, $filters);

					if(preg_match_all('@(\$ds-[0-9a-z_\.\-]+)@i', $dsShell, $matches)){
						$dependencies = General::array_remove_duplicates($matches[1]);
						$dsShell = str_replace('<!-- DS DEPENDENCY LIST -->', "'" . implode("', '", $dependencies) . "'", $dsShell);
					}

					$dsShell = str_replace('<!-- CLASS EXTENDS -->', $extends, $dsShell);
					$dsShell = str_replace('<!-- SOURCE -->', $source, $dsShell);
				}

				if($this->_context[0] == 'new') {
					/**
					 * Prior to creating the Datasource, the file path where it will be written to
					 * is provided and well as the contents of that file.
					 *
					 * @delegate DatasourcePreCreate
					 * @since Symphony 2.2
					 * @param string $context
					 * '/blueprints/datasources/'
					 * @param string $file
					 *  The path to the Datasource file
					 * @param string $contents
					 *  The contents for this Datasource as a string passed by reference
					 * @param array $params
					 *  An array of all the `$dsParam*` values
					 * @param array $elements
					 *  An array of all the elements included in this datasource
					 * @param array $filters
					 *  An associative array of all the filters for this datasource with the key
					 *  being the `field_id` and the value the filter.
					 * @param array $dependencies
					 *  An array of dependencies that this datasource has
					 */
					Symphony::ExtensionManager()->notifyMembers('DatasourcePreCreate', '/blueprints/datasources/', array(
						'file' => $file,
						'contents' => &$dsShell,
						'params' => $params,
						'elements' => $elements,
						'filters' => $filters,
						'dependencies' => $dependencies
					));
				}
				else {
					/**
					 * Prior to editing a Datasource, the file path where it will be written to
					 * is provided and well as the contents of that file.
					 *
					 * @delegate DatasourcePreEdit
					 * @since Symphony 2.2
					 * @param string $context
					 * '/blueprints/datasources/'
					 * @param string $file
					 *  The path to the Datasource file
					 * @param string $contents
					 *  The contents for this Datasource as a string passed by reference
					 * @param array $dependencies
					 *  An array of dependencies that this datasource has
					 * @param array $params
					 *  An array of all the `$dsParam*` values
					 * @param array $elements
					 *  An array of all the elements included in this datasource
					 * @param array $filters
					 *  An associative array of all the filters for this datasource with the key
					 *  being the `field_id` and the value the filter.
					 */
					Symphony::ExtensionManager()->notifyMembers('DatasourcePreEdit', '/blueprints/datasources/', array(
						'file' => $file,
						'contents' => &$dsShell,
						'dependencies' => $dependencies,
						'params' => $params,
						'elements' => $elements,
						'filters' => $filters
					));
				}

				// Remove left over placeholders
				$dsShell = preg_replace(array('/<!--[\w ]++-->/', '/(\r\n){2,}/', '/(\t+[\r\n]){2,}/'), '', $dsShell);

				// Write the file
				if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $dsShell, Symphony::Configuration()->get('write_mode', 'file'))) {
					$this->pageAlert(
						__('Failed to write Data source to disk.')
						. ' ' . __('Please check permissions on %s.', array('<code>/workspace/data-sources</code>'))
						, Alert::ERROR
					);
				}
				// Write Successful, add record to the database
				else {

					if($queueForDeletion){
						General::deleteFile($queueForDeletion);

						// Update pages that use this DS
						$pages = PageManager::fetch(false, array('data_sources', 'id'), array("
							`data_sources` REGEXP '[[:<:]]" . $existing_handle . "[[:>:]]'
						"));

						if(is_array($pages) && !empty($pages)){
							foreach($pages as $page) {
								$page['data_sources'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['data_sources']);

								PageManager::edit($page['id'], $page);
							}
						}
					}

					if($this->_context[0] == 'new') {
						/**
						 * After creating the Datasource, the path to the Datasource file is provided
						 *
						 * @delegate DatasourcePostCreate
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/datasources/'
						 * @param string $file
						 *  The path to the Datasource file
						 */
						Symphony::ExtensionManager()->notifyMembers('DatasourcePostCreate', '/blueprints/datasources/', array(
							'file' => $file
						));
					}
					else {
						/**
						 * After editing the Datasource, the path to the Datasource file is provided
						 *
						 * @delegate DatasourcePostEdit
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/datasources/'
						 * @param string $file
						 *  The path to the Datasource file
						 * @param string $previous_file
						 *  The path of the previous Datasource file in the case where a Datasource may
						 *  have been renamed. To get the handle from this value, see
						 *  `DatasourceManager::__getHandleFromFilename`
						 */
						Symphony::ExtensionManager()->notifyMembers('DatasourcePostEdit', '/blueprints/datasources/', array(
							'file' => $file,
							'previous_file' => ($queueForDeletion) ? $queueForDeletion : null
						));
					}

					redirect(SYMPHONY_URL . '/blueprints/datasources/edit/'.$classname.'/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');
				}
			}
		}

		public static function injectFilters(&$shell, array $filters){
			if(empty($filters)) return;

			$placeholder = '<!-- FILTERS -->';
			$string = 'public $dsParamFILTERS = array(' . PHP_EOL;

			foreach($filters as $key => $val){
				if(trim($val) == '') continue;
				$string .= "\t\t\t\t'$key' => '" . addslashes($val) . "'," . PHP_EOL;
			}

			$string .= "\t\t);" . PHP_EOL . "\t\t" . $placeholder;

			$shell = str_replace($placeholder, trim($string), $shell);
		}

		public static function injectAboutInformation(&$shell, array $details){
			if(empty($details)) return;

			foreach($details as $key => $val) {
				$shell = str_replace('<!-- ' . strtoupper($key) . ' -->', addslashes($val), $shell);
			}
		}

		public function __injectIncludedElements(&$shell, $elements){
			if(!is_array($elements) || empty($elements)) return;

			$placeholder = '<!-- INCLUDED ELEMENTS -->';
			$shell = str_replace($placeholder, "public \$dsParamINCLUDEDELEMENTS = array(" . PHP_EOL . "\t\t\t\t'" . implode("'," . PHP_EOL . "\t\t\t\t'", $elements) . "'" . PHP_EOL . '		);' . PHP_EOL . "\t\t" . $placeholder, $shell);
		}

		public function __injectVarList(&$shell, $vars){
			if(!is_array($vars) || empty($vars)) return;

			$var_list = NULL;
			foreach($vars as $key => $val){
				if(is_array($val)) {
					$val = "array(" . PHP_EOL . "\t\t\t\t'" . implode("'," . PHP_EOL . "\t\t\t\t'", $val) . "'" . PHP_EOL . '		);';
					$var_list .= '		public $dsParam' . strtoupper($key) . ' = ' . $val . PHP_EOL;
				}
				else if(trim($val) !== '') {
					$var_list .= '		public $dsParam' . strtoupper($key) . " = '" . addslashes($val) . "';" . PHP_EOL;
				}
			}

			$placeholder = '<!-- VAR LIST -->';
			$shell = str_replace($placeholder, trim($var_list) . PHP_EOL . "\t\t" . $placeholder, $shell);
		}

		public function __appendAuthorFilter(&$wrapper, $h4_label, $name, $value=NULL, $templateOnly=true){

			if(!$templateOnly){

				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->setAttribute('data-type', $name);
				$li->appendChild(new XMLElement('header', '<h4>' . $h4_label . '</h4>'));
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][author]['.$name.']', General::sanitize($value)));
				$li->appendChild($label);

			 	$wrapper->appendChild($li);
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->setAttribute('data-type', $name);
			$li->appendChild(new XMLElement('header', '<h4>' . $h4_label . '</h4>'));
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter][author]['.$name.']'));
			$li->appendChild($label);

		 	$wrapper->appendChild($li);

		}

		private static function __isValidPageString($string){
			return (bool)preg_match('/^(?:\{\$[\w-]+(?::\$[\w-]+)*(?::\d+)?}|\d+)$/', $string);
		}

		/**
		 * Given a `$url` and `$timeout`, this function will use the `Gateway`
		 * class to determine that it is a valid URL and returns successfully
		 * before the `$timeout`. If it does not, an error message will be
		 * returned, otherwise true.
		 *
		 * @since Symphony 2.3
		 * @param string $url
		 * @param integer $timeout
		 *  If not provided, this will default to 6 seconds
		 * @param string $error
		 *  If this function returns false, this variable will be populated with the
		 *  error message.
		 * @return array|boolean
		 *  Returns an array with the 'data' if it is a valid URL, otherwise a string
		 *  containing an error message.
		 */
		public static function __isValidURL($url, $timeout = 6, &$error) {
			if(!filter_var($url, FILTER_VALIDATE_URL)) {
				$error = __('Invalid URL');
				return false;
			}

			// Check that URL was provided
			$gateway = new Gateway;
			$gateway->init($url);
			$gateway->setopt('TIMEOUT', $timeout);
			$data = $gateway->exec();

			$info = $gateway->getInfoLast();

			// 28 is CURLE_OPERATION_TIMEOUTED
			if($info['curl_error'] == 28) {
				$error = __('Request timed out. %d second limit reached.', array($timeout));
				return false;
			}
			else if($data === false || $info['http_code'] != 200) {
				$error = __('Failed to load URL, status code %d was returned.', array($info['http_code']));
				return false;
			}

			return array('data' => $data);
		}

	}
