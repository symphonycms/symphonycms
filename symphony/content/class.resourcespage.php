<?php

	/**
	 * @package content
	 */
	/**
	 * The Datasource Editor page allows a developer to create new datasources
	 * from the four Symphony types, Section, Authors, Navigation, Dynamic XML,
	 * and Static XML
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.pagemanager.php');
	require_once(CONTENT . '/class.sortable.php');

	Abstract Class ResourcesPage extends AdministrationPage{

		public $_errors = array();

		public abstract function fetchExtensionData($handle);

		public abstract function getResourceFile($handle, $fullpath = true);

		public function attachToPage($resource, $handle, $page_id) {
			$pages = PageManager::fetch(false, array($resource), array(sprintf(
				'`id` = %d', $page_id
			)));

			if (is_array($pages) && count($pages) == 1) {
				$result = $pages[0][$resource];

				if (!in_array($handle, explode(',', $result))) {

					if (strlen($result) > 0) $result .= ',';
					$result .= $handle;

					Symphony::Database()->update(
						array($resource => MySQL::cleanValue($result)),
						'tbl_pages', 
						sprintf('`id` = %d', $page_id)
					);
				}
			}
		}

		public function detachFromPage($resource, $handle, $page_id) {
			$pages = PageManager::fetch(false, array($resource), array(sprintf(
				'`id` = %d', $page_id
			)));

			if (is_array($pages) && count($pages) == 1) {
				$result = $pages[0][$resource];

				$values = explode(',', $result);
				$idx = array_search($handle, $values, false);

				if ($idx !== false) {
					array_splice($values, $idx, 1);
					$result = implode(',', $values);

					Symphony::Database()->update(
						array($resource => MySQL::cleanValue($result)),
						'tbl_pages', 
						sprintf('`id` = %d', $page_id)
					);
				}
			}
		}

		public function getRelatedPages($handle, $resource){
			$pages = PageManager::fetch(false, array('id', 'title'), array(sprintf(
				'`%s` = "%s" OR `%s` REGEXP "%s"',
				$resource, $handle,
				$resource, '^' . $handle . ',|,' . $handle . ',|,' . $handle . '$'
			)));

			return (is_null($pages) ? array() : $pages);
		}

		public function pagesFlatView(){
			$pages = PageManager::fetch(false, array('id'));

			foreach($pages as &$p) {
				$p['title'] = PageManager::resolvePageTitle($p['id']);
			}

			return $pages;
		}

		public function __viewIndex(){
			$this->setPageType('table');

			$resources = new Sortable($_REQUEST['symphony-page'], $sort, $order);
			$resources = $resources->sort();

			$columns = array(
				array(
					'label' => __('Name'),
					'sortable' => true
				),
				array(
					'label' => __('Source'),
					'sortable' => true
				),
				array(
					'label' => __('Pages'),
					'sortable' => false
				),
				array(
					'label' => __('Release Date'),
					'sortable' => true
				),
				array(
					'label' => __('Author'),
					'sortable' => true
				)
			);

			$aTableHead = array();

			foreach($columns as $i => $c) {
				if ($c['sortable']) {

					if ($i == $sort) {
						$link = '?sort='.$i.'&amp;order='. ($order == 'desc' ? 'asc' : 'desc') . (isset($_REQUEST['filter']) ? '&amp;filter=' . $_REQUEST['filter'] : '');
						$label = Widget::Anchor(
							$c['label'], $link,
							__('Sort by %1$s %2$s', array(($order == 'desc' ? __('ascending') : __('descending')), strtolower($c['label']))),
							'active'
						);
					}
					else {
						$link = '?sort='.$i.'&amp;order=asc' . (isset($_REQUEST['filter']) ? '&amp;filter=' . $_REQUEST['filter'] : '');
						$label = Widget::Anchor(
							$c['label'], $link,
							__('Sort by %1$s %2$s', array(__('ascending'), strtolower($c['label'])))
						);
					}

				}
				else {
					$label = $c['label'];
				}

				$aTableHead[] = array($label, 'col');
			}

			/* Body */

			$aTableBody = array();

			if (!is_array($resources) || empty($resources)) {
				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}
			else {
				$bOdd = true;

				foreach($resources as $r) {
					if ($r['can_parse']) {
						$name = Widget::TableData(
							Widget::Anchor(
								$r['name'],
								URL . '/symphony' . $_REQUEST['symphony-page'] .  'edit/' . $r['handle'] . '/',
								$r['handle']
							)
						);

						if ($r['source'] > 0) {
							$sectionData = SectionManager::fetch($r['source']);

							if ( $sectionData !== false ) {
								$section = Widget::TableData(
									Widget::Anchor(
										$sectionData->get('name'),
										URL . '/symphony' . $_REQUEST['symphony-page'] .  'edit/' . $sectionData->get('id') . '/',
										$sectionData->get('handle')
									)
								);
							}
							else {
								$section = Widget::TableData(__('Not found'), 'inactive');
							}
						}
						else {
							$section = Widget::TableData($r['source']);
						}
					}
					else {
						$name = Widget::TableData(
							Widget::Anchor(
								$r['name'],
								URL . '/symphony' . $_REQUEST['symphony-page'] .  'info/' . $r['handle'] . '/',
								$r['handle']
							)
						);

						// Resource provided by extension?
						$extension_data = $this->fetchExtensionData($r['handle']);

						if(!empty($extension_data[1])) {
							$extension = Symphony::$ExtensionManager->about($extension_data[1]);
							$section = Widget::TableData(__('Extension') . ': ' . $extension['name']);
						}
						else {
							$section = Widget::TableData(__('None'), 'inactive');
						}
					}

					$pages = $this->getRelatedPages($r['handle']);

					$pagelinks = array();

					$i = 0;
					foreach($pages as $p) {
						++$i;
						$pagelinks[] = Widget::Anchor(
							$p['title'],
							URL . '/symphony/blueprints/pages/edit/' . $p['id']
						)->generate() . (count($pages) > $i ? (($i % 10) == 0 ? '<br />' : ', ') : '');
					}

					$pages = implode('', $pagelinks);

					if ($pages == "")
						$pagelinks = Widget::TableData(__('None'), 'inactive');
					else
						$pagelinks = Widget::TableData($pages);

					$datetimeobj = new DateTimeObj();
					$releasedate = Widget::TableData($datetimeobj->get(
						__SYM_DATETIME_FORMAT__,
						strtotime($r['release-date']))
					);

					$author = $r['author']['name'];

					if (isset($r['author']['website'])) {
						$author = Widget::Anchor($r['author']['name'], General::validateURL($r['author']['website']));
					}
					else if(isset($r['author']['email'])) {
						$author = Widget::Anchor($r['author']['name'], 'mailto:' . $r['author']['email']);
					}

					$author = Widget::TableData($author);
					$author->appendChild(Widget::Input('items[' . $r['handle'] . ']', null, 'checkbox'));

					$aTableBody[] = Widget::TableRow(array($name, $section, $pagelinks, $releasedate, $author), null);

				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead), 
				NULL, 
				Widget::TableBody($aTableBody),
				'selectable'
			);

			$this->Form->appendChild($table);

			/* Actions */

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm'),
			);

			$pages = $this->pagesFlatView();

			$group_link = array('label' => __('Attach Page'), 'options' => array());
			$group_unlink = array('label' => __('Detach Page'), 'options' => array());

			$group_link['options'][] = array('attach-all-pages', false, __('All'));
			$group_unlink['options'][] = array('detach-all-pages', false, __('All'));

			foreach($pages as $p) {
				$group_link['options'][] = array('attach-page-' . $p['id'], false, $p['title']);
				$group_unlink['options'][] = array('detach-page-' . $p['id'], false, $p['title']);
			}

			$options[] = $group_link;
			$options[] = $group_unlink;

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);

		}

		public function __actionIndex(){
			if (isset($_POST['action']) && is_array($_POST['action'])) {
				$checked = ($_POST['items']) ? @array_keys($_POST['items']) : NULL;

				if (is_array($checked) && !empty($checked)) {

					if ($_POST['with-selected'] == 'delete') {
						$canProceed = true;

						foreach($checked as $handle) {
							if (!General::deleteFile($this->getResourceFile($handle))) {
								$this->pageAlert(
									__('Failed to delete <code>%s</code>. Please check permissions.', array($this->getResourceFile($handle), false)),
									Alert::ERROR);
								$canProceed = false;
							}
						}

						if ($canProceed) redirect(Administration::instance()->getCurrentPageURL());
					}
					else if(preg_match('/^(?:at|de)?tach-page-/', $_POST['with-selected'])) {

						if (substr($_POST['with-selected'], 0, 6) == 'detach') {
							$page = str_replace('detach-page-', '', $_POST['with-selected']);

							foreach($checked as $handle) {
								$this->detachFromPage($handle, $page);
							}
						}
						else {
							$page = str_replace('attach-page-', '', $_POST['with-selected']);

							foreach($checked as $handle) {
								$this->attachToPage($handle, $page);
							}
						}

						redirect(Administration::instance()->getCurrentPageURL());
					}
					else if(preg_match('/^(?:at|de)?tach-all-pages$/', $_POST['with-selected'])) {
						$pages = PageManager::fetch(false, array('id'));

						if (substr($_POST['with-selected'], 0, 6) == 'detach') {
							foreach($checked as $handle) {
								foreach($pages as $page) {
									$this->detachFromPage($handle, $page['id']);
								}
							}
						}
						else {
							foreach($checked as $handle) {
								foreach($pages as $page) {
									$this->attachToPage($handle, $page['id']);
								}
							}
						}

						redirect(Administration::instance()->getCurrentPageURL());
					}

				}
			}
		}

	}
