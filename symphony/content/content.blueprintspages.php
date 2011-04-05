<?php

	/**
	 * @package content
	 */

	/**
	 * Developers can create new Frontend pages from this class. It provides
	 * an index view of all the pages in this Symphony install as well as the
	 * forms for the creation/editing of a Page
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');

	class contentBlueprintsPages extends AdministrationPage {
		protected $_errors;
		protected $_hilights = array();

		public function __viewIndex() {
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Pages'))));

			$nesting = (Symphony::Configuration()->get('pages_table_nest_children', 'symphony') == 'yes');

			$heading = NULL;
			if($nesting == true && isset($_GET['parent']) && is_numeric($_GET['parent'])){
				$parent = (int)$_GET['parent'];
				$heading = ' &mdash; ' . self::__buildParentBreadcrumb($parent);
			}

			$this->appendSubheading(__('Pages') . $heading, Widget::Anchor(
				__('Create New'), Administration::instance()->getCurrentPageURL() . 'new/' . ($nesting == true && isset($parent) ? "?parent={$parent}" : NULL),
				__('Create a new page'), 'create button', NULL, array('accesskey' => 'c')
			));

			$aTableHead = array(
				array(__('Title'), 'col'),
				array(__('Template'), 'col'),
				array(__('<acronym title="Universal Resource Locator">URL</acronym>'), 'col'),
				array(__('<acronym title="Universal Resource Locator">URL</acronym> Parameters'), 'col'),
				array(__('Type'), 'col')
			);

			$sql = "SELECT p.*
				FROM `tbl_pages` AS p
				%s
				ORDER BY p.sortorder ASC";

			if($nesting == true){
				$aTableHead[] = array(__('Children'), 'col');
				$sql = sprintf($sql, ' WHERE p.parent ' . (isset($parent) ? " = {$parent} " : ' IS NULL '));
			}

			else{
				$sql = sprintf($sql, NULL);
			}

			$pages = Symphony::Database()->fetch($sql);

			$aTableBody = array();

			if(!is_array($pages) or empty($pages)) {
				$aTableBody = array(Widget::TableRow(array(
					Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead))
				), 'odd'));

			}
			else{
				foreach ($pages as $page) {
					$class = array();

					$page_title = ($nesting == true ? $page['title'] : Administration::instance()->resolvePageTitle($page['id']));
					$page_url = URL . '/' . Administration::instance()->resolvePagePath($page['id']) . '/';
					$page_edit_url = Administration::instance()->getCurrentPageURL() . 'edit/' . $page['id'] . '/';
					$page_template = $this->__createHandle($page['path'], $page['handle']);
					$page_template_url = Administration::instance()->getCurrentPageURL() . 'template/' . $page_template . '/';
					$page_types = Symphony::Database()->fetchCol('type', "
						SELECT
							t.type
						FROM
							`tbl_pages_types` AS t
						WHERE
							t.page_id = '".$page['id']."'
						ORDER BY
							t.type ASC
					");

					$col_title = Widget::TableData(Widget::Anchor(
						$page_title, $page_edit_url, $page['handle']
					));
					$col_title->appendChild(Widget::Input("items[{$page['id']}]", null, 'checkbox'));

					$col_template = Widget::TableData(Widget::Anchor(
						$page_template . '.xsl',
						$page_template_url
					));

					$col_url = Widget::TableData(Widget::Anchor($page_url, $page_url));

					if($page['params']) {
						$col_params = Widget::TableData(trim($page['params'], '/'));

					} else {
						$col_params = Widget::TableData(__('None'), 'inactive');
					}

					if(!empty($page_types)) {
						$col_types = Widget::TableData(implode(', ', $page_types));

					} else {
						$col_types = Widget::TableData(__('None'), 'inactive');
					}

					if(in_array($page['id'], $this->_hilights)) $class[] = 'failed';

					$columns = array($col_title, $col_template, $col_url, $col_params, $col_types);

					if($nesting == true){
						if($this->__hasChildren($page['id'])){
							$col_children = Widget::TableData(
								Widget::Anchor(self::__countChildren($page['id']) . ' &rarr;',
								SYMPHONY_URL . '/blueprints/pages/?parent=' . $page['id'])
							);
						}
						else{
							$col_children = Widget::TableData(__('None'), 'inactive');
						}

						$columns[] = $col_children;
					}

					$aTableBody[] = Widget::TableRow(
						$columns,
						implode(' ', $class)
					);
				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead), null,
				Widget::TableBody($aTableBody), 'orderable selectable'
			);

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(null, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm', null, array(
					'data-message' => __('Are you sure you want to delete the selected pages?')
				))
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function __viewTemplate() {
			$this->setPageType('form');
			$this->Form->setAttribute('action', SYMPHONY_URL . '/blueprints/pages/template/' . $this->_context[1] . '/');

			$filename = $this->_context[1] . '.xsl';
			$file_abs = PAGES . '/' . $filename;

			$is_child = strrpos($this->_context[1],'_');
			$pagename = ($is_child != false ? substr($this->_context[1], $is_child + 1) : $this->_context[1]);

			$pagedata = Symphony::Database()->fetchRow(0, "
					SELECT
						p.*
					FROM
						`tbl_pages` AS p
					WHERE
						p.handle = '{$pagename}'
					LIMIT 1
				");

			if(!is_file($file_abs)) redirect(SYMPHONY_URL . '/blueprints/pages/');

			$fields['body'] = @file_get_contents($file_abs);

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			// Status message:
			if(isset($this->_context[2])) {
				$this->pageAlert(
					__(
						'Page updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Pages</a>', 
						array(
							DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
							SYMPHONY_URL . '/blueprints/pages/new/' . $link_suffix,
							SYMPHONY_URL . '/blueprints/pages/' . $link_suffix,
						)
					),
					Alert::SUCCESS
				);
			}

			$this->setTitle(__(
				($filename ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'),
				array(
					__('Symphony'),
					__('Pages'),
					$filename
				)
			));

			$this->appendSubheading(__($filename ? $filename : __('Untitled')), Widget::Anchor(__('Edit Configuration'), SYMPHONY_URL . '/blueprints/pages/edit/' . $pagedata['id'], __('Edit Page Confguration'), 'button', NULL, array('accesskey' => 't')));

			if(!empty($_POST)) $fields = $_POST['fields'];

			$fields['body'] = General::sanitize($fields['body']);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'primary');

			$label = Widget::Label(__('Body'));
			$label->appendChild(Widget::Textarea(
				'fields[body]', 30, 80, $fields['body'],
				array(
					'class'	=> 'code'
				)
			));

			if(isset($this->_errors['body'])) {
				$label = $this->wrapFormElementWithError($label, $this->_errors['body']);
			}

			$fieldset->appendChild($label);
			$this->Form->appendChild($fieldset);

			$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utilities = $utilities['filelist'];

			if(is_array($utilities) && !empty($utilities)) {
				$div = new XMLElement('div');
				$div->setAttribute('class', 'secondary');

				$p = new XMLElement('p', __('Utilities'));
				$p->setAttribute('class', 'label');
				$div->appendChild($p);

				$ul = new XMLElement('ul');
				$ul->setAttribute('id', 'utilities');

				foreach ($utilities as $index => $util) {
					$li = new XMLElement('li');

					if($index % 2 != 1) $li->setAttribute('class', 'odd');

					$li->appendChild(Widget::Anchor($util, SYMPHONY_URL . '/blueprints/utilities/edit/' . str_replace('.xsl', '', $util) . '/', NULL));
					$ul->appendChild($li);
				}

				$div->appendChild($ul);
				$this->Form->appendChild($div);
			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input(
				'action[save]', __('Save Changes'),
				'submit', array('accesskey' => 's')
			));

			$this->Form->appendChild($div);
		}		
		
		public function __viewNew() {
			$this->__viewEdit();
		}

		public function __viewEdit() {
			$this->setPageType('form');
			$fields = array();

			$nesting = (Symphony::Configuration()->get('pages_table_nest_children', 'symphony') == 'yes');

			// Verify page exists:
			if($this->_context[0] == 'edit') {
				if(!$page_id = $this->_context[1]) redirect(SYMPHONY_URL . '/blueprints/pages/');

				$existing = Symphony::Database()->fetchRow(0, "
					SELECT
						p.*
					FROM
						`tbl_pages` AS p
					WHERE
						p.id = '{$page_id}'
					LIMIT 1
				");

				if(!$existing) {
					Administration::instance()->errorPageNotFound();
				}
			}

			// Status message:
			$flag = $this->_context[2];
			if(isset($flag)){
				if(isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])){
					$link_suffix = "?parent=" . $_REQUEST['parent'];
				}

				elseif($nesting == true && isset($existing) && !is_null($existing['parent'])){
					$link_suffix = '?parent=' . $existing['parent'];
				}

				switch($flag){

					case 'saved':

						$this->pageAlert(
							__(
								'Page updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Pages</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . '/blueprints/pages/new/' . $link_suffix,
									SYMPHONY_URL . '/blueprints/pages/' . $link_suffix,
								)
							),
							Alert::SUCCESS);

						break;

					case 'created':

						$this->pageAlert(
							__(
								'Page created at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Pages</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . '/blueprints/pages/new/' . $link_suffix,
									SYMPHONY_URL . '/blueprints/pages/' . $link_suffix,
								)
							),
							Alert::SUCCESS);

						break;

				}
			}

			// Find values:
			if(isset($_POST['fields'])) {
				$fields = $_POST['fields'];
			}

			elseif($this->_context[0] == 'edit') {
				$fields = $existing;
				$types = Symphony::Database()->fetchCol('type', "
					SELECT
						p.type
					FROM
						`tbl_pages_types` AS p
					WHERE
						p.page_id = '{$page_id}'
					ORDER BY
						p.type ASC
				");

				$fields['type'] = @implode(', ', $types);
				$fields['data_sources'] = preg_split('/,/i', $fields['data_sources'], -1, PREG_SPLIT_NO_EMPTY);
				$fields['events'] = preg_split('/,/i', $fields['events'], -1, PREG_SPLIT_NO_EMPTY);
			}

			elseif(isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])){
				$fields['parent'] = $_REQUEST['parent'];
			}

			$title = $fields['title'];
			if(trim($title) == '') $title = $existing['title'];

			$this->setTitle(__(
				($title ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'),
				array(
					__('Symphony'),
					__('Pages'),
					$title
				)
			));
			if($existing) {
				$template_name = $fields['handle'];
				if($existing['parent']){
					$parents = $this->__getParent($existing['parent']);
					$template_name = $parents . '_' . $fields['handle'];
				}
				$this->appendSubheading(__($title ? $title : __('Untitled')), Widget::Anchor(__('Edit Template'), SYMPHONY_URL . '/blueprints/pages/template/' . $template_name, __('Edit Page Template'), 'button', NULL, array('accesskey' => 't')));
			}
			else {
				$this->appendSubheading(($title ? $title : __('Untitled')));
			}

		// Title --------------------------------------------------------------

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Page Settings')));

			$label = Widget::Label(__('Title'));
			$label->appendChild(Widget::Input(
				'fields[title]', General::sanitize($fields['title'])
			));

			if(isset($this->_errors['title'])) {
				$label = $this->wrapFormElementWithError($label, $this->_errors['title']);
			}

			$fieldset->appendChild($label);

		// Handle -------------------------------------------------------------

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			$column = new XMLElement('div');

			$label = Widget::Label(__('URL Handle'));
			$label->appendChild(Widget::Input(
				'fields[handle]', $fields['handle']
			));

			if(isset($this->_errors['handle'])) {
				$label = $this->wrapFormElementWithError($label, $this->_errors['handle']);
			}

			$column->appendChild($label);

		// Parent ---------------------------------------------------------

			$label = Widget::Label(__('Parent Page'));

			$pages = Symphony::Database()->fetch("
				SELECT
					p.*
				FROM
					`tbl_pages` AS p
				WHERE
					p.id != '{$page_id}'
				ORDER BY
					p.title ASC
			");

			$options = array(
				array('', false, '/')
			);

			if(is_array($pages) && !empty($pages)) {
				if(!function_exists('__compare_pages')) {
					function __compare_pages($a, $b) {
						return strnatcasecmp($a[2], $b[2]);
					}
				}

				foreach ($pages as $page) {
					$options[] = array(
						$page['id'], $fields['parent'] == $page['id'],
						'/' . Administration::instance()->resolvePagePath($page['id'])
					);
				}

				usort($options, '__compare_pages');
			}

			$label->appendChild(Widget::Select(
				'fields[parent]', $options
			));
			$column->appendChild($label);
			$group->appendChild($column);

		// Parameters ---------------------------------------------------------

			$column = new XMLElement('div');
			$label = Widget::Label(__('URL Parameters'));
			$label->appendChild(Widget::Input(
				'fields[params]', $fields['params']
			));
			$column->appendChild($label);

		// Type -----------------------------------------------------------

			$label = Widget::Label(__('Page Type'));
			$label->appendChild(Widget::Input('fields[type]', $fields['type']));

			if(isset($this->_errors['type'])) {
				$label = $this->wrapFormElementWithError($label, $this->_errors['type']);
			}

			$column->appendChild($label);

			$tags = new XMLElement('ul');
			$tags->setAttribute('class', 'tags');

			if($types = $this->__fetchAvailablePageTypes()) {
				foreach($types as $type) $tags->appendChild(new XMLElement('li', $type));
			}
			$column->appendChild($tags);
			$group->appendChild($column);
			$fieldset->appendChild($group);
			$this->Form->appendChild($fieldset);

		// Events -------------------------------------------------------------

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Page Resources')));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$label = Widget::Label(__('Events'));

			$manager = new EventManager($this->_Parent);
			$events = $manager->listAll();

			$options = array();

			if(is_array($events) && !empty($events)) {
				if(!is_array($fields['events'])) $fields['events'] = array();
				foreach ($events as $name => $about) $options[] = array(
					$name, in_array($name, $fields['events']), $about['name']
				);
			}

			$label->appendChild(Widget::Select('fields[events][]', $options, array('multiple' => 'multiple')));
			$group->appendChild($label);

		// Data Sources -------------------------------------------------------

			$label = Widget::Label(__('Data Sources'));

			$manager = new DatasourceManager($this->_Parent);
			$datasources = $manager->listAll();

			$options = array();

			if(is_array($datasources) && !empty($datasources)) {
				if(!is_array($fields['data_sources'])) $fields['data_sources'] = array();
				foreach ($datasources as $name => $about) $options[] = array(
					$name, in_array($name, $fields['data_sources']), $about['name']
				);
			}

			$label->appendChild(Widget::Select('fields[data_sources][]', $options, array('multiple' => 'multiple')));
			$group->appendChild($label);
			$fieldset->appendChild($group);
			$this->Form->appendChild($fieldset);

		// Controls -----------------------------------------------------------

			/**
			 * After all Page related Fields have been added to the DOM, just before the
			 * actions.
			 *
			 * @delegate AppendPageContent
			 * @param string $context
			 *  '/blueprints/pages/'
			 * @param XMLElement $form
			 * @param array $fields
			 * @param array $errors
			 */
			Symphony::ExtensionManager()->notifyMembers(
				'AppendPageContent',
				'/blueprints/pages/',
				array(
					'form'		=> &$this->Form,
					'fields'	=> &$fields,
					'errors'	=> $this->_errors
				)
			);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input(
				'action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create Page')),
				'submit', array('accesskey' => 's')
			));

			if($this->_context[0] == 'edit'){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this page'), 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this page?')));
				$div->appendChild($button);
			}

			$this->Form->appendChild($div);

			if(isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])){
				$this->Form->appendChild(new XMLElement('input', NULL, array('type' => 'hidden', 'name' => 'parent', 'value' => $_REQUEST['parent'])));
			}
		}

		public function __actionIndex() {
			$checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

			if(is_array($checked) && !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						$this->__actionDelete($checked, SYMPHONY_URL . '/blueprints/pages/');
						break;
				}
			}
		}

		public function __actionTemplate() {
			$filename = $this->_context[1] . '.xsl';
			$file_abs = PAGES . '/' . $filename;
			$fields = $_POST['fields'];
			$this->_errors = array();

			if(!isset($fields['body']) || trim($fields['body']) == '') {
				$this->_errors['body'] = __('Body is a required field.');

			} elseif(!General::validateXML($fields['body'], $errors, false, new XSLTProcess())) {
				$this->_errors['body'] = __('This document is not well formed. The following error was returned: <code>%s</code>', array($errors[0]['message']));
			}

			if(empty($this->_errors)) {
				if(!$write = General::writeFile($file_abs, $fields['body'], Symphony::Configuration()->get('write_mode', 'file'))) {
					$this->pageAlert(__('Utility could not be written to disk. Please check permissions on <code>/workspace/utilities</code>.'), Alert::ERROR);

				} else {
					redirect(SYMPHONY_URL . '/blueprints/pages/template/' . $this->_context[1] . '/saved/');
				}
			}
		}

		public function __actionNew() {
			$this->__actionEdit();
		}

		public function __actionEdit() {
			if($this->_context[0] != 'new' && !$page_id = (integer)$this->_context[1]) {
				redirect(SYMPHONY_URL . '/blueprints/pages/');
			}

			$parent_link_suffix = NULL;
			if(isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])){
				$parent_link_suffix = '?parent=' . $_REQUEST['parent'];
			}

			if(@array_key_exists('delete', $_POST['action'])) {
				$this->__actionDelete($page_id, SYMPHONY_URL  . '/blueprints/pages/' . $parent_link_suffix);
			}

			if(@array_key_exists('save', $_POST['action'])) {

				$fields = $_POST['fields'];
				$this->_errors = array();

				$current = Symphony::Database()->fetchRow(0, "
					SELECT
						p.*
					FROM
						`tbl_pages` AS p
					WHERE
						p.id = '{$page_id}'
					LIMIT 1
				");

				if(!isset($fields['title']) || trim($fields['title']) == '') {
					$this->_errors['title'] = __('Title is a required field');
				}

				if(trim($fields['type']) != '' && preg_match('/(index|404|403)/i', $fields['type'])) {
					$types = preg_split('/\s*,\s*/', strtolower($fields['type']), -1, PREG_SPLIT_NO_EMPTY);

					if(in_array('index', $types) && $this->typeUsed($page_id, 'index')) {
						$this->_errors['type'] = __('An index type page already exists.');
					}

					elseif(in_array('404', $types) && $this->typeUsed($page_id, '404')) {
						$this->_errors['type'] = __('A 404 type page already exists.');
					}

					elseif(in_array('403', $types) && $this->typeUsed($page_id, '403')) {
						$this->_errors['type'] = __('A 403 type page already exists.');
					}
				}

				/**
				 * Just after the Symphony validation has run, allows Developers
				 * to run custom validation logic on a Page
				 *
				 * @delegate PagePostValidate
				 * @since Symphony 2.2
				 * @param string $context
				 * '/blueprints/pages/'
				 * @param array $fields
				 *  The `$_POST['fields']` array. This should be read-only and not changed
				 *  through this delegate.
				 * @param array $errors
				 *  An associative array of errors, with the key matching a key in the
				 *  `$fields` array, and the value being the string of the error. `$errors`
				 *  is passed by reference.
				 */
				Symphony::ExtensionManager()->notifyMembers('PagePostValidate', '/blueprints/pages/', array('fields' => $fields, 'errors' => &$errors));

				if(empty($this->_errors)) {
					$autogenerated_handle = false;

					if(empty($current)) {
						$fields['sortorder'] = Symphony::Database()->fetchVar('next', 0, "
							SELECT
								MAX(p.sortorder) + 1 AS `next`
							FROM
								`tbl_pages` AS p
							LIMIT 1
						");

						if(empty($fields['sortorder']) || !is_numeric($fields['sortorder'])) {
							$fields['sortorder'] = 1;
						}
					}

					if(trim($fields['handle'] ) == '') {
						$fields['handle'] = $fields['title'];
						$autogenerated_handle = true;
					}

					$fields['handle'] = Lang::createHandle($fields['handle']);

					if($fields['params']) {
						$fields['params'] = trim(preg_replace('@\/{2,}@', '/', $fields['params']), '/');
					}

					// Clean up type list
					$types = preg_split('/\s*,\s*/', $fields['type'], -1, PREG_SPLIT_NO_EMPTY);
					$types = @array_map('trim', $types);
					unset($fields['type']);

					$fields['parent'] = ($fields['parent'] != __('None') ? $fields['parent'] : null);
					$fields['data_sources'] = is_array($fields['data_sources']) ? implode(',', $fields['data_sources']) : NULL;
					$fields['events'] = is_array($fields['events']) ? implode(',', $fields['events']) : NULL;
					$fields['path'] = null;

					if($fields['parent']) {
						$fields['path'] = Administration::instance()->resolvePagePath((integer)$fields['parent']);
					}

					// Check for duplicates:
					$duplicate = Symphony::Database()->fetchRow(0, "
						SELECT
							p.*
						FROM
							`tbl_pages` AS p
						WHERE
							p.id != '{$page_id}'
							AND p.handle = '" . $fields['handle'] . "'
							AND p.path " . ($fields['path'] ? " = '" . $fields['path'] . "'" : ' IS NULL') .  "
						LIMIT 1
					");

					// Create or move files:
					if(!$duplicate){
						if(empty($current)) {
							$file_created = $this->__updatePageFiles(
								$fields['path'], $fields['handle']
							);

						} else {
							$file_created = $this->__updatePageFiles(
								$fields['path'], $fields['handle'],
								$current['path'], $current['handle']
							);
						}

						if(!$file_created) {
							$redirect = null;
							$this->pageAlert(
								__('Page could not be written to disk. Please check permissions on <code>/workspace/pages</code>.'),
								Alert::ERROR
							);
						}
					}

					if($duplicate) {
						if($autogenerated_handle) {
							$this->_errors['title'] = __('A page with that title already exists');

						} else {
							$this->_errors['handle'] = __('A page with that handle already exists');
						}

					// Insert the new data:
					} elseif(empty($current)) {

						/**
						 * Just prior to creating a new Page record in `tbl_pages`, provided
						 * with the `$fields` associative array. Use with caution, as no
						 * duplicate page checks are run after this delegate has fired
						 *
						 * @delegate PagePreCreate
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/pages/'
						 * @param array $fields
						 *  The `$_POST['fields']` array passed by reference
						 */
						Symphony::ExtensionManager()->notifyMembers('PagePreCreate', '/blueprints/pages/', array('fields' => &$fields));

						if(!Symphony::Database()->insert($fields, 'tbl_pages')) {
							$this->pageAlert(
								__(
									'Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.',
									array(
										SYMPHONY_URL . '/system/log/'
									)
								),
								Alert::ERROR
							);

						} else {
							$page_id = Symphony::Database()->getInsertID();

							/**
							 * Just after the creation of a new page in `tbl_pages`
							 *
							 * @delegate PagePostCreate
							 * @since Symphony 2.2
							 * @param string $context
							 * '/blueprints/pages/'
							 * @param integer $page_id
							 *  The ID of the newly created Page
							 * @param array $fields
							 *  An associative array of data that was just saved for this page
							 */
							Symphony::ExtensionManager()->notifyMembers('PagePostCreate', '/blueprints/pages/', array('page_id' => $page_id, 'fields' => &$fields));

							$redirect = "/blueprints/pages/edit/{$page_id}/created/{$parent_link_suffix}";
						}

					// Update existing:
					} else {

						/**
						 * Just prior to updating a Page record in `tbl_pages`, provided
						 * with the `$fields` associative array. Use with caution, as no
						 * duplicate page checks are run after this delegate has fired
						 *
						 * @delegate PagePreEdit
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/pages/'
						 * @param integer $page_id
						 *  The ID of the Page that is about to be updated
						 * @param array $fields
						 *  The `$_POST['fields']` array passed by reference
						 */
						Symphony::ExtensionManager()->notifyMembers('PagePreEdit', '/blueprints/pages/', array('page_id' => $page_id, 'fields' => &$fields));

						if(!Symphony::Database()->update($fields, 'tbl_pages', "`id` = '$page_id'")) {
							$this->pageAlert(
								__(
									'Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.',
									array(
										SYMPHONY_URL . '/system/log/'
									)
								),
								Alert::ERROR
							);

						} else {
							Symphony::Database()->delete('tbl_pages_types', " `page_id` = '$page_id'");

							/**
							 * Just after updating a page in `tbl_pages`
							 *
							 * @delegate PagePostEdit
							 * @since Symphony 2.2
							 * @param string $context
							 * '/blueprints/pages/'
							 * @param integer $page_id
							 *  The ID of the Page that was just updated
							 * @param array $fields
							 *  An associative array of data that was just saved for this page
							 */
							Symphony::ExtensionManager()->notifyMembers('PagePostEdit', '/blueprints/pages/', array('page_id' => $page_id, 'fields' => $fields));

							$redirect = "/blueprints/pages/edit/{$page_id}/saved/{$parent_link_suffix}";
						}
					}

					/**
					 * Just before the page's types are saved into `tbl_pages_types`.
					 * Use with caution as no further processing is done on the `$types`
					 * array to prevent duplicate `$types` from occurring (ie. two index
					 * page types). Your logic can use the contentBlueprintsPages::typeUsed
					 * function to perform this logic.
					 *
					 * @delegate PageTypePreCreate
					 * @since Symphony 2.2
					 * @see content.contentBlueprintsPages#typeUsed()
					 * @param string $context
					 * '/blueprints/pages/'
					 * @param integer $page_id
					 *  The ID of the Page that was just created or updated
					 * @param array $types
					 *  An associative array of the types for this page passed by reference.
					 */
					Symphony::ExtensionManager()->notifyMembers('PageTypePreCreate', '/blueprints/pages/', array('page_id' => $page_id, 'types' => &$types));

					// Assign page types:
					if(is_array($types) && !empty($types)) {
						foreach ($types as $type) Symphony::Database()->insert(
							array(
								'page_id' => $page_id,
								'type' => $type
							),
							'tbl_pages_types'
						);
					}

					// Find and update children:
					if($this->_context[0] == 'edit') {
						$this->__updatePageChildren($page_id, $fields['path'] . '/' . $fields['handle']);
					}

					if($redirect) redirect(SYMPHONY_URL . $redirect);
				}

				if(is_array($this->_errors) && !empty($this->_errors)) {
					$this->pageAlert(
						__('An error occurred while processing this form. <a href="#error">See below for details.</a>'),
						Alert::ERROR
					);
				}
			}
		}

		public function __actionDelete($pages, $redirect) {
			$success = true;

			if(!is_array($pages)) $pages = array($pages);

			/**
			 * Prior to deleting Pages
			 *
			 * @delegate PagePreDelete
			 * @since Symphony 2.2
			 * @param string $context
			 * '/blueprints/pages/
			 * @param array $page_ids
			 *  An array of Page ID's that are about to be deleted, passed
			 *  by reference
			 * @param string $redirect
			 *  The absolute path that the Developer will be redirected to
			 *  after the Pages are deleted
			 */
			Symphony::ExtensionManager()->notifyMembers('PagePreDelete', '/blueprints/pages/', array('page_ids' => &$pages, 'redirect' => &$redirect));

			foreach ($pages as $page_id) {
				$page = Symphony::Database()->fetchRow(0, "
					SELECT
						p.*
					FROM
						`tbl_pages` AS p
					WHERE
						p.id = '{$page_id}'
					LIMIT 1
				");

				if(empty($page)) {
					$success = false;
					$this->pageAlert(
						__('Page could not be deleted because it does not exist.'),
						Alert::ERROR
					);

					break;
				}

				if($this->__hasChildren($page_id)) {
					$this->_hilights[] = $page['id'];
					$success = false;
					$this->pageAlert(
						__('Page could not be deleted because it has children.'),
						Alert::ERROR
					);

					continue;
				}

				if(!$this->__deletePageFiles($page['path'], $page['handle'])) {
					$this->_hilights[] = $page['id'];
					$success = false;
					$this->pageAlert(
						__('One or more pages could not be deleted. Please check permissions on <code>/workspace/pages</code>.'),
						Alert::ERROR
					);

					continue;
				}

				Symphony::Database()->delete('tbl_pages', " `id` = '{$page_id}'");
				Symphony::Database()->delete('tbl_pages_types', " `page_id` = '{$page_id}'");
				Symphony::Database()->query("
					UPDATE
						tbl_pages
					SET
						`sortorder` = (`sortorder` + 1)
					WHERE
						`sortorder` < '$page_id'
				");
			}

			if($success) redirect($redirect);
		}

		private static function __countChildren($id){
			$children = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_pages` WHERE `parent` = {$id}");
			$count = count($children);

			if(count($children) > 0){
				foreach($children as $c){
					$count += self::__countChildren($c);
				}
			}

			return $count;
		}

		private static function __buildParentBreadcrumb($id, $last=true){
			$page = Symphony::Database()->fetchRow(0, "SELECT `title`, `id`, `parent` FROM `tbl_pages` WHERE `id` = {$id}");

			if(!is_array($page) || empty($page)) return NULL;

			if($last != true){
				$anchor = Widget::Anchor(
					$page['title'], Administration::instance()->getCurrentPageURL() . '?parent=' . $page['id']
				);
			}

			$result = (!is_null($page['parent']) ? self::__buildParentBreadcrumb($page['parent'], false) . ' &gt; ' : NULL) . ($anchor instanceof XMLElement ? $anchor->generate() : $page['title']);

			return $result;

		}

		protected function __getParent($page_id) {
			$parent = Symphony::Database()->fetchRow(0, "
					SELECT
						p.*
					FROM
						`tbl_pages` AS p
					WHERE
						p.id = '{$page_id}'
					LIMIT 1
				");
			$handle = $parent['handle'];
			if($parent['parent']){
				$ancestor = $this->__getParent($parent['parent']);
				$handle = $ancestor . '_' . $handle;
			}
			return $handle;
		}

		/**
		 * Returns boolean if a the given `$type` is set for
		 * the given `$page_id`.
		 *
		 * @param integer $page_id
		 *  The ID of the Page to check
		 * @param string $type
		 * @return boolean
		 *  True if the type is used, false otherwise
		 */
		public static function typeUsed($page_id, $type) {
			$row = Symphony::Database()->fetchRow(0, "
				SELECT
					p.*
				FROM
					`tbl_pages_types` AS p
				WHERE
					p.page_id != '{$page_id}'
					AND p.type = '{$type}'
				LIMIT 1
			");

			return ($row ? true : false);
		}

		protected function __updatePageChildren($page_id, $page_path, &$success = true) {
			$page_path = trim($page_path, '/');
			$children = Symphony::Database()->fetch("
				SELECT
					p.id, p.path, p.handle
				FROM
					`tbl_pages` AS p
				WHERE
					p.id != '{$page_id}'
					AND p.parent = '{$page_id}'
			");

			foreach ($children as $child) {
				$child_id = $child['id'];
				$fields = array(
					'path'	=> $page_path
				);

				if(!$this->__updatePageFiles($page_path, $child['handle'], $child['path'], $child['handle'])) {
					$success = false;
				}

				if(!Symphony::Database()->update($fields, 'tbl_pages', "`id` = '$child_id'")) {
					$success = false;
				}

				$this->__updatePageChildren($child_id, $page_path . '/' . $child['handle']);
			}

			return $success;
		}

		protected function __createHandle($path, $handle) {
			return trim(str_replace('/', '_', $path . '_' . $handle), '_');
		}

		protected function __updatePageFiles($new_path, $new_handle, $old_path = null, $old_handle = null) {
			$new = PAGES . '/' . $this->__createHandle($new_path, $new_handle) . '.xsl';
			$old = PAGES . '/' . $this->__createHandle($old_path, $old_handle) . '.xsl';
			$data = null;

			// Nothing to do:
			if(file_exists($new) && $new == $old) return true;

			// Old file doesn't exist, use template:
			if(!file_exists($old)) {
				$data = file_get_contents(TEMPLATE . '/page.xsl');

			}
			else{
				$data = file_get_contents($old); @unlink($old);
			}

			return General::writeFile($new, $data, Symphony::Configuration()->get('write_mode', 'file'));
		}

		protected function __deletePageFiles($path, $handle) {
			$file = PAGES . '/' . trim(str_replace('/', '_', $path . '_' . $handle), '_') . '.xsl';

			// Nothing to do:
			if(!file_exists($file)) return true;

			// Delete it:
			if(@unlink($file)) return true;

			return false;
		}

		protected function __hasChildren($page_id) {
			return (boolean)Symphony::Database()->fetchVar('id', 0, "
				SELECT
					p.id
				FROM
					`tbl_pages` AS p
				WHERE
					p.parent = '{$page_id}'
				LIMIT 1
			");
		}
	}
