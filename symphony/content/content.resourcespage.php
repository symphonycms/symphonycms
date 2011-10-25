<?php

	/**
	 * @package content
	 */
	/**
	 * The ResourcesPages is an abstract class that controls the way "Datasource"
	 * and "Events" index pages are displayed in the backend.
	 *
	 * @since Symphony 2.3
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.resourcemanager.php');
	require_once(CONTENT . '/class.sortable.php');

	Abstract Class ResourcesPage extends AdministrationPage{

		public $_errors = array();

		public function pagesFlatView(){
			$pages = PageManager::fetch(false, array('id'));

			foreach($pages as &$p) {
				$p['title'] = PageManager::resolvePageTitle($p['id']);
			}

			return $pages;
		}

		public function __viewIndex($resource_type){
			$this->setPageType('table');

			Sortable::init($this, $resources, $sort, $order);

			$columns = array(
				array(
					'label' => __('Name'),
					'sortable' => true,
					'handle' => 'name'
				),
				array(
					'label' => __('Source'),
					'sortable' => true,
					'handle' => 'source'
				),
				array(
					'label' => __('Pages'),
					'sortable' => false,
				),
				array(
					'label' => __('Release Date'),
					'sortable' => true,
					'handle' => 'release-date'
				),
				array(
					'label' => __('Author'),
					'sortable' => true,
					'handle' => 'author'
				)
			);

			$aTableHead = Sortable::buildTableHeaders(
				$columns, $sort, $order, (isset($_REQUEST['filter']) ? '&amp;filter=' . $_REQUEST['filter'] : '')
			);

			$aTableBody = array();

			if(!is_array($resources) || empty($resources)) {
				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}
			else {
				foreach($resources as $r) {

					// Resource name
					$action = ($r['can_parse'] ? 'edit' : 'info');
					$name = Widget::TableData(
						Widget::Anchor(
							$r['name'],
							SYMPHONY_URL . $_REQUEST['symphony-page'] .  $action . '/' . $r['handle'] . '/',
							$r['handle']
						)
					);

					// Resource type/source
					if(isset($r['source']['id'])) {
						$section = Widget::TableData(
							Widget::Anchor(
								$r['source']['name'],
								SYMPHONY_URL . '/blueprints/sections/edit/' . $r['source']['id'] . '/',
								$r['source']['handle']
							)
						);
					}
					else if(isset($r['source']['name'])){
						$section = Widget::TableData($r['source']['name']);
					}
					else {
						$section = Widget::TableData(__('Unknown'), 'inactive');
					}

					// Attached pages
					$pages = ResourceManager::getAttachedPages($resource_type, $r['handle']);

					$pagelinks = array();
					$i = 0;

					foreach($pages as $p) {
						++$i;
						$pagelinks[] = Widget::Anchor(
							$p['title'],
							SYMPHONY_URL . '/blueprints/pages/edit/' . $p['id'] . '/'
						)->generate() . (count($pages) > $i ? (($i % 10) == 0 ? '<br />' : ', ') : '');
					}

					$pages = implode('', $pagelinks);

					if($pages == ''){
						$pagelinks = Widget::TableData(__('None'), 'inactive');
					}
					else {
						$pagelinks = Widget::TableData($pages);
					}

					// Release date
					$datetimeobj = new DateTimeObj();
					$releasedate = Widget::TableData(Lang::localizeDate(
						$datetimeobj->format($r['release-date'], __SYM_DATETIME_FORMAT__)
					));

					// Authors
					$author = $r['author']['name'];

					if(isset($r['author']['website'])) {
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

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm'),
			);

			$pages = $this->pagesFlatView();

			$group_attach = array('label' => __('Attach to Page'), 'options' => array());
			$group_detach = array('label' => __('Detach from Page'), 'options' => array());

			$group_attach['options'][] = array('attach-all-pages', false, __('All'));
			$group_detach['options'][] = array('detach-all-pages', false, __('All'));

			foreach($pages as $p) {
				$group_attach['options'][] = array('attach-to-page-' . $p['id'], false, $p['title']);
				$group_detach['options'][] = array('detach-from-page-' . $p['id'], false, $p['title']);
			}

			$options[] = $group_attach;
			$options[] = $group_detach;

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);

		}

		public function __actionIndex($resource_type){
			$manager = ResourceManager::getManagerFromType($resource_type);

			if (isset($_POST['action']) && is_array($_POST['action'])) {
				$checked = ($_POST['items']) ? @array_keys($_POST['items']) : NULL;

				if (is_array($checked) && !empty($checked)) {

					if ($_POST['with-selected'] == 'delete') {
						$canProceed = true;

						foreach($checked as $handle) {
							$path = $manager::__getDriverPath($handle);

							if (!General::deleteFile($path)) {
								$folder = str_replace(DOCROOT, '', $path);
								$folder = str_replace('/' . basename($path), '', $folder);

								$this->pageAlert(
									__('Failed to delete %s.', array('<code>' . basename($path) . '</code>'))
									. ' ' . __('Please check permissions on %s', array('<code>' . $folder . '</code>'))
									, Alert::ERROR
								);
								$canProceed = false;
							}
						}

						if ($canProceed) redirect(Administration::instance()->getCurrentPageURL());
					}
					else if(preg_match('/^(at|de)?tach-(to|from)-page-/', $_POST['with-selected'])) {

						if (substr($_POST['with-selected'], 0, 6) == 'detach') {
							$page = str_replace('detach-from-page-', '', $_POST['with-selected']);

							foreach($checked as $handle) {
								ResourceManager::detach($resource_type, $handle, $page);
							}
						}
						else {
							$page = str_replace('attach-to-page-', '', $_POST['with-selected']);

							foreach($checked as $handle) {
								ResourceManager::attach($resource_type, $handle, $page);
							}
						}

						if($canProceed) redirect(Administration::instance()->getCurrentPageURL());
					}
					else if(preg_match('/^(at|de)?tach-all-pages$/', $_POST['with-selected'])) {
						$pages = PageManager::fetch(false, array('id'));

						if (substr($_POST['with-selected'], 0, 6) == 'detach') {
							foreach($checked as $handle) {
								foreach($pages as $page) {
									ResourceManager::detach($resource_type, $handle, $page['id']);
								}
							}
						}
						else {
							foreach($checked as $handle) {
								foreach($pages as $page) {
									ResourceManager::attach($resource_type, $handle, $page['id']);
								}
							}
						}

						redirect(Administration::instance()->getCurrentPageURL());
					}

				}
			}
		}

	}
