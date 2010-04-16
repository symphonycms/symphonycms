<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	//require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.messagestack.php');
	require_once(TOOLKIT . '/class.xslproc.php');
	require_once(TOOLKIT . '/class.utility.php');

	class contentBlueprintsViews extends AdministrationPage {
		protected $_errors;

		/*
		TODO: Remove this?

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
		*/

		public function __viewIndex() {
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Views'))));

			$nesting = (Symphony::Configuration()->get('pages_table_nest_children', 'symphony') == 'yes');

			$heading = NULL;
			if($nesting == true && isset($_GET['parent']) && is_numeric($_GET['parent'])){
				$parent = (int)$_GET['parent'];
				$heading = ' &mdash; ' . self::__buildParentBreadcrumb($parent);
			}

			$this->appendSubheading(__('Views') . $heading, Widget::Anchor(
				__('Create New'), Administration::instance()->getCurrentPageURL() . 'new/' . ($nesting == true && isset($parent) ? "?parent={$parent}" : NULL), array(
					'title' => __('Create a new view'),
					'class' => 'create button'
				)
			));

			$iterator = new ViewIterator;

			$aTableHead = array(
				array(__('Title'), 'col'),
				array(Widget::Acronym('URL', array('title' => __('Universal Resource Locator'))), 'col'),
				array(Widget::Acronym('URL', array('title' => __('Universal Resource Locator')), __(' Parameters')), 'col'),
				array(__('Type'), 'col')
			);

			$aTableBody = array();
			$colspan = count($aTableHead);

			if($iterator->length() <= 0) {
				$aTableBody = array(Widget::TableRow(
					array(
						Widget::TableData(__('None found.'), array(
								'class' => 'inactive',
								'colspan' => $colspan
							)
						)
					), array(
						'class' => 'odd'
					)
				));
			}

			else{
				foreach ($iterator as $view) {
					$class = array();

					$page_title = $view->title;

					$page_url = sprintf('%s/%s/', URL, $view->path);
					$page_edit_url = sprintf('%sedit/%s/', Administration::instance()->getCurrentPageURL(), $view->path);

					$page_types = $view->types;

					$link = Widget::Anchor($page_title, $page_edit_url, array('title' => $view->handle));

					$col_title = Widget::TableData($link);
					$col_title->appendChild(Widget::Input("items[{$view->path}]", null, 'checkbox'));

					$col_url = Widget::TableData(Widget::Anchor(substr($page_url, strlen(URL)), $page_url));

					if(is_array($view->{'url-parameters'}) && count($view->{'url-parameters'}) > 0){
						$col_params = Widget::TableData(implode('/', $view->{'url-parameters'}));

					} else {
						$col_params = Widget::TableData(__('None'), array('class' => 'inactive'));
					}

					if(!empty($page_types)) {
						$col_types = Widget::TableData(implode(', ', $page_types));

					} else {
						$col_types = Widget::TableData(__('None'), array('class' => 'inactive'));
					}

					$col_toggle = Widget::TableData('', array('class' => 'toggle'));

					$columns = array($col_title, $col_url, $col_params, $col_types);

					$row = Widget::TableRow($columns);
					$next = $view->parent();
					$class = '';

					while (!is_null($next)) {
						$class .= ' view-' . $next->guid;

						$next = $next->parent();
					}

					//if (is_null($view->parent())) {
						$row->setAttribute('id', 'view-' . $view->guid);
					//}

					if (trim($class)) {
						$row->setAttribute('class', trim($class));
					}

					$aTableBody[] = $row;
				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead), null,
				Widget::TableBody($aTableBody), array(
					'id' => 'views-list'
				)
			);

			$this->Form->appendChild($table);

			$tableActions = $this->createElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(null, false, __('With Selected...')),
				array('delete', false, __('Delete'))
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function __viewTemplate() {

			$callback = Administration::instance()->getPageCallback();

			$context = $this->_context;
			array_shift($context);
			$view_pathname = implode('/', $context);

			$view = View::loadFromPath($view_pathname);

			$this->Form->setAttribute('action', ADMIN_URL . '/blueprints/views/template/' . $view->path . '/');

			$filename = $view->handle . '.xsl';

			$formHasErrors = ($this->_errors instanceof MessageStack && $this->_errors->length() > 0);
			if($formHasErrors){
				$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			}

			// Status message:
			if(!is_null($callback['flag']) && $callback['flag'] == 'saved') {
				$this->pageAlert(
					__(
						'View updated at %s. <a href="%s">View all Views</a>',
						array(
							DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
							ADMIN_URL . '/blueprints/views/'
						)
					),
					Alert::SUCCESS
				);
			}

			$this->setTitle(__(
				($filename ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'),
				array(
					__('Symphony'),
					__('Views'),
					$filename
				)
			));
			$this->appendSubheading(__($filename ? $filename : __('Untitled')));

			$viewoptions = array(
				'subnav'	=> array(
					__('Configuration')			=>	ADMIN_URL . '/blueprints/views/edit/' . $view_pathname . '/',
					__('Template')				=>	Administration::instance()->getCurrentPageURL()
				)
			);

			$this->appendViewOptions($viewoptions);

			if(!empty($_POST)){
				$view->template = $_POST['fields']['template'];
			}

			$fieldset = $this->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');

			$group = $this->createElement('div');
			$group->setAttribute('class', 'group');

			$div = $this->createElement('div');

			$label = Widget::Label(__('Template'));
			$label->appendChild(
				Widget::Textarea('fields[template]', General::sanitize($view->template), array(
					'rows' => 30,
					'cols' => 80,
					'class'	=> 'code'
				)
			));

			if(isset($this->_errors->template)) {
				$label = Widget::wrapFormElementWithError($label, $this->_errors->template);
			}

			$div->appendChild($label);
			$group->appendChild($div);
			$fieldset->appendChild($group);

			$this->Form->appendChild($fieldset);

			$utilities = new UtilityIterator;

			if($utilities->length() > 0){
				$div = $this->createElement('div');
				$div->setAttribute('class', 'small');

				$div->appendChild(
					$this->createElement('h3', __('Utilities'), array('class' => 'label'))
				);

				$ul = $this->createElement('ul');
				$ul->setAttribute('id', 'utilities');

				foreach ($utilities as $u) {
					$li = $this->createElement('li');
					$li->appendChild(Widget::Anchor(
						$u->name, ADMIN_URL . '/blueprints/utilities/edit/' . str_replace('.xsl', NULL, $u->name) . '/'
					));
					$ul->appendChild($li);
				}

				$div->appendChild($ul);
				$group->appendChild($div);
			}

			$div = $this->createElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input(
				'action[save]', __('Save Changes'),
				'submit', array('accesskey' => 's')
			));

			$this->Form->appendChild($div);
		}

		public function __actionTemplate() {

			$context = $this->_context;
			array_shift($context);

			$view = self::__loadExistingView(implode('/', $context));

			$view->template = $_POST['fields']['template'];

			$this->_errors = new MessageStack;

			try{
				View::save($view, $this->_errors);
				redirect(ADMIN_URL . '/blueprints/views/template/' . $view->path . '/:saved/');
			}
			catch(ViewException $e){
				switch($e->getCode()){
					case View::ERROR_MISSING_OR_INVALID_FIELDS:
						$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
						break;

					case View::ERROR_FAILED_TO_WRITE:
						$this->pageAlert($e->getMessage(), Alert::ERROR);
						break;
				}
			}
			catch(Exception $e){
				// Errors!!
				// Not sure what happened!!
				$this->pageAlert(__("An unknown error has occurred. %s", $e->getMessage()), Alert::ERROR);
			}

		}

		public function __viewNew() {
			$this->__form();
		}

		public function __viewEdit() {
			$this->__form();
		}

		private static function __loadExistingView($path){
			try{
				$existing = View::loadFromPath($path);
				return $existing;
			}
			catch(ViewException $e){

				switch($e->getCode()){
					case View::ERROR_VIEW_NOT_FOUND:
						throw new SymphonyErrorPage(
							__('The view you requested to edit does not exist.'),
							__('View not found'), NULL,
							array('header' => 'HTTP/1.0 404 Not Found')
						);
						break;

					default:
					case View::ERROR_FAILED_TO_LOAD:
						throw new SymphonyErrorPage(
							__('The view you requested could not be loaded. Please check it is readable and the XML is valid.'),
							__('Failed to load view')
						);
						break;
				}
			}
			catch(Exception $e){
				throw new SymphonyErrorPage(
					sprintf(__("An unknown error has occurred. %s"), $e->getMessage()),
					__('Unknown Error'), NULL,
					array('header' => 'HTTP/1.0 500 Internal Server Error')
				);
			}
		}

		public function __form() {
			$layout = new Layout(3, '1:1:1');

			$fields = array();

			// Verify view exists:
			if($this->_context[0] == 'edit') {

				if(!isset($this->_context[1]) || strlen(trim($this->_context[1])) == 0){
					redirect(ADMIN_URL . '/blueprints/views/');
				}

				$context = $this->_context;
				array_shift($context);
				$view_pathname = implode('/', $context);

				$existing = self::__loadExistingView($view_pathname);

			}

			// Status message:
			$callback = Administration::instance()->getPageCallback();
			if(isset($callback['flag']) && !is_null($callback['flag'])){

				switch($callback['flag']){

					case 'saved':

						$this->pageAlert(
							__(
								'View updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Views</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									ADMIN_URL . '/blueprints/views/new/',
									ADMIN_URL . '/blueprints/views/',
								)
							),
							Alert::SUCCESS);

						break;

					case 'created':

						$this->pageAlert(
							__(
								'View created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Views</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									ADMIN_URL . '/blueprints/views/new/',
									ADMIN_URL . '/blueprints/views/',
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
				$fields = (array)$existing->about();
				$fields['types'] = @implode(', ', $fields['types']); //Flatten the types array
				$fields['url-parameters'] = @implode('/', $fields['url-parameters']); //Flatten the url-parameters array
				$fields['parent'] = ($existing->parent() instanceof View ? $existing->parent()->path : NULL);
				$fields['handle'] = $existing->handle;
			}

			$title = $fields['title'];
			if(strlen(trim($title)) == 0){
				$title = ($existing instanceof View ? $existing->title : 'Untitled');
			}

			$this->setTitle(__(
				($title ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'),
				array(
					__('Symphony'),
					__('Views'),
					$title
				)
			));

			if($existing instanceof View){
				$template_name = $fields['handle'];
				$this->appendSubheading(
					__($title ? $title : __('Untitled'))
				);
				$viewoptions = array(
					'subnav'	=> array(
						__('Configuration')		=>	Administration::instance()->getCurrentPageURL(),
						__('Template')			=>	sprintf('%s/blueprints/views/template/%s/', ADMIN_URL, $view_pathname)
				)
			);

			$this->appendViewOptions($viewoptions);
			}
			else {
				$this->appendSubheading(($title ? $title : __('Untitled')));
			}

		// Fieldset -----------------------------------------------------------

			$fieldset = Widget::Fieldset(__('Essentials'));

		// Title --------------------------------------------------------------

			$label = Widget::Label(__('Title'));
			$label->appendChild(Widget::Input(
				'fields[title]', General::sanitize($fields['title'])
			));

			if(isset($this->_errors->title)) {
				$label = Widget::wrapFormElementWithError($label, $this->_errors->title);
			}

			$fieldset->appendChild($label);

		// Type ---------------------------------------------------------------

			$container = $this->createElement('div');

			$label = Widget::Label(__('View Type'));
			$label->appendChild(Widget::Input('fields[types]', $fields['types']));

			if(isset($this->_errors->types)) {
				$label = Widget::wrapFormElementWithError($label, $this->_errors->types);
			}

			$tags = $this->createElement('ul');
			$tags->setAttribute('class', 'tags');

			foreach(self::__fetchAvailableViewTypes() as $t){
				$tags->appendChild($this->createElement('li', $t));
			}

			$container->appendChild($label);
			$container->appendChild($tags);
			$fieldset->appendChild($container);

			$layout->appendToCol($fieldset, 1);

		// Fieldset -----------------------------------------------------------

			$fieldset = Widget::Fieldset(__('URL Settings'));

		// Parent -------------------------------------------------------------

			$label = Widget::Label(__('Parent'));

			$options = array(
				array(NULL, false, '/')
			);

			foreach(new ViewIterator as $v){
				// Make sure the current view cannot be set as either a child of itself, or a child of
				// another view that is already at child of the current view.
				if(isset($existing) && $existing instanceof View && ($v->isChildOf($existing) || $v->guid == $existing->guid)) continue;

				$options[] = array(
					$v->path, $fields['parent'] == $v->path, "/{$v->path}"
				);
			}

			$label->appendChild(Widget::Select(
				'fields[parent]', $options
			));

			$fieldset->appendChild($label);

		// Handle -------------------------------------------------------------

			$label = Widget::Label(__('Handle'));
			$label->appendChild(Widget::Input(
				'fields[handle]', $fields['handle']
			));

			if(isset($this->_errors->handle)) {
				$label = Widget::wrapFormElementWithError($label, $this->_errors->handle);
			}

			$fieldset->appendChild($label);

		// Parameters ---------------------------------------------------------

			$label = Widget::Label(__('Parameters'));
			$label->appendChild(Widget::Input(
				'fields[url-parameters]', $fields['url-parameters']
			));

			$fieldset->appendChild($label);
			$layout->appendToCol($fieldset, 2);

		// Fieldset -----------------------------------------------------------

			$fieldset = Widget::Fieldset(__('View Resources'));

			$label = Widget::Label(__('Events'));

			$events = EventManager::instance()->listAll();

			$options = array();

			if(is_array($events) && !empty($events)) {
				foreach ($events as $name => $about) $options[] = array(
					$name, @in_array($name, $fields['events']), $about['name']
				);
			}

			$label->appendChild(Widget::Select('fields[events][]', $options, array('multiple' => 'multiple')));
			$fieldset->appendChild($label);

		// Data Sources -------------------------------------------------------

			$label = Widget::Label(__('Data Sources'));

			$options = array();

			$iterator = new DataSourceIterator;
			if($iterator->length() > 0){
				foreach ($iterator as $pathname){
					$ds = DataSource::load($pathname);
					$handle = DataSource::getHandleFromFilename($pathname);
					$options[] = array(
						$handle, in_array($handle, (array)$fields['data-sources']), $ds->about()->name
					);
				}
			}

			$label->appendChild(Widget::Select('fields[data-sources][]', $options, array('multiple' => 'multiple')));
			$fieldset->appendChild($label);
			$layout->appendToCol($fieldset, 3);

			$this->Form->appendChild($layout->generate());

		// Controls -----------------------------------------------------------

			$div = $this->createElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input(
				'action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create View')),
				'submit', array('accesskey' => 's')
			));

			if($this->_context[0] == 'edit'){
				$div->appendChild(
					$this->createElement('button', __('Delete'), array(
						'name' => 'action[delete]',
						'class' => 'confirm delete',
						'title' => __('Delete this view')
					))
				);
			}

			$this->Form->appendChild($div);

			//if(isset($_REQUEST['parent']) && is_numeric($_REQUEST['parent'])){
			//	$this->Form->appendChild(new XMLElement('input', NULL, array('type' => 'hidden', 'name' => 'parent', 'value' => $_REQUEST['parent'])));
			//}
		}

		/*protected function __getParent($page_id) {
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
		}*/

		/*protected function __typeUsed($page_id, $type) {
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
		}*/

		public function __actionNew() {
			$this->__actionEdit();
		}

		public function __actionEdit() {

			if($this->_context[0] != 'new' && strlen(trim($this->_context[1])) == 0){
				redirect(ADMIN_URL . '/blueprints/views/');
			}

			$context = $this->_context;
			array_shift($context);

			$view_pathname = implode('/', $context);

			if(array_key_exists('delete', $_POST['action'])) {
				$this->__actionDelete(array($view_pathname), ADMIN_URL . '/blueprints/views/');
			}

			elseif(array_key_exists('save', $_POST['action'])) {

				$fields = $_POST['fields'];

				$fields['types'] = preg_split('/\s*,\s*/', $fields['types'], -1, PREG_SPLIT_NO_EMPTY);

				if(strlen(trim($fields['url-parameters'])) > 0){
					$fields['url-parameters'] = preg_split('/\/+/', trim($fields['url-parameters'], '/'), -1, PREG_SPLIT_NO_EMPTY);
				}

				if(strlen(trim($fields['handle'])) == 0){
					$fields['handle'] = Lang::createHandle($fields['title']);
				}

				$path = trim($fields['parent'] . '/' . $fields['handle'], '/');

				if($this->_context[0] == 'edit'){
					$view = self::__loadExistingView($view_pathname);

					$view->types = $fields['types'];
					$view->title = $fields['title'];
					$view->{'data-sources'} = $fields['data-sources'];
					$view->events = $fields['events'];
					$view->{'url-parameters'} = $fields['url-parameters'];

					// Path has changed - Need to move the existing one, then save it
					if($view->path != $path){

						$this->_errors = new MessageStack;

						try{
							// Before moving or renaming, simulate saving to check for potential errors
							View::save($view, $this->_errors, true);
							View::move($view, $path);
						}
						catch(Exception $e){
							// Saving failed, catch it further down
						}
					}

				}
				else{
					$view = View::loadFromFieldsArray($fields);
					$view->template = file_get_contents(TEMPLATES . '/template.view.txt');
					$view->handle = $fields['handle'];
					$view->path = $path;
				}

				$this->_errors = new MessageStack;

				try{
					View::save($view, $this->_errors);
					redirect(ADMIN_URL . '/blueprints/views/edit/' . $view->path . '/:saved/');
				}
				catch(ViewException $e){
					switch($e->getCode()){
						case View::ERROR_MISSING_OR_INVALID_FIELDS:
							$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
							break;

						case View::ERROR_FAILED_TO_WRITE:
							$this->pageAlert($e->getMessage(), Alert::ERROR);
							break;
					}
				}
				catch(Exception $e){
					// Errors!!
					// Not sure what happened!!
					$this->pageAlert(__("An unknown error has occurred. %s", $e->getMessage()), Alert::ERROR);
				}

				//print "<pre>";
				//print htmlspecialchars((string)$view); die();

			/*	print "<pre>";
				print_r($this->_errors);
//				print_r($view);
//				print_r($fields);
				die();



				if(!isset($fields['title']) || trim($fields['title']) == '') {
					$this->_errors['title'] = __('Title is a required field');
				}

				if(trim($fields['type']) != '' && preg_match('/(index|404|403)/i', $fields['type'])) {
					$types = preg_split('~\s*,\s*~', strtolower($fields['type']), -1, PREG_SPLIT_NO_EMPTY);

					if(in_array('index', $types) && $this->__typeUsed($page_id, 'index')) {
						$this->_errors['type'] = __('An index type view already exists.');
					}

					elseif(in_array('404', $types) && $this->__typeUsed($page_id, '404')) {
						$this->_errors['type'] = __('A 404 type view already exists.');
					}

					elseif(in_array('403', $types) && $this->__typeUsed($page_id, '403')) {
						$this->_errors['type'] = __('A 403 type view already exists.');
					}
				}

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
					$types = preg_split('~\s*,\s*~', $fields['type'], -1, PREG_SPLIT_NO_EMPTY);
					$types = @array_map('trim', $types);
					unset($fields['type']);

					$fields['parent'] = ($fields['parent'] != __('None') ? $fields['parent'] : null);
					$fields['data_sources'] = @implode(',', $fields['data_sources']);
					$fields['events'] = @implode(',', $fields['events']);
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
							__('View could not be written to disk. Please check permissions on <code>/workspace/views</code>.'),
							Alert::ERROR
						);
					}

					if($duplicate) {
						if($autogenerated_handle) {
							$this->_errors['title'] = __('A view with that title already exists');

						} else {
							$this->_errors['handle'] = __('A view with that handle already exists');
						}

					// Insert the new data:
					} elseif(empty($current)) {
						if(!Symphony::Database()->insert($fields, 'tbl_pages')) {
							$this->pageAlert(
								__(
									'Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.',
									array(
										ADMIN_URL . '/system/log/'
									)
								),
								Alert::ERROR
							);

						} else {
							$page_id = Symphony::Database()->getInsertID();
							$redirect = "/symphony/blueprints/views/edit/{$page_id}/created/{$parent_link_suffix}/";
						}

					// Update existing:
					} else {
						if(!Symphony::Database()->update($fields, 'tbl_pages', "`id` = '$page_id'")) {
							$this->pageAlert(
								__(
									'Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.',
									array(
										ADMIN_URL . '/system/log/'
									)
								),
								Alert::ERROR
							);

						} else {
							Symphony::Database()->delete('tbl_pages_types', " `page_id` = '$page_id'");
							$redirect = "/symphony/blueprints/views/edit/{$page_id}/saved/{$parent_link_suffix}/";
						}
					}

					// Assign view types:
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

					if($redirect) redirect(URL . $redirect);
				}

				if(is_array($this->_errors) && !empty($this->_errors)) {
					$this->pageAlert(
						__('An error occurred while processing this form. <a href="#error">See below for details.</a>'),
						Alert::ERROR
					);
				}*/
			}
		}

		private static function __fetchAvailableViewTypes(){

			// TODO: Delegate here so extensions can add custom view types?

			$types = array('index', 'XML', 'admin', '404', '403');

			foreach(View::fetchUsedTypes() as $t){
				$types[] = $t;
			}


			//if(!$types = Symphony::Database()->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` ORDER BY `type` ASC")) return $system_types;

			return General::array_remove_duplicates($types);

		}

		protected function __actionDelete(array $views, $redirect) {

			rsort($views);

			$success = true;

			foreach($views as $path){
				try{
					View::delete($path);
				}
				catch(ViewException $e){
					die($e->getMessage() . 'DOH!!1');
				}
				catch(Exception $e){
					die($e->getMessage() . 'DOH!!2');
				}
			}

			if($success == true) redirect($redirect);
		}

		public function __actionIndex() {
			$checked = array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						$this->__actionDelete($checked, ADMIN_URL . '/blueprints/views/');
						break;
				}
			}
		}
	}
