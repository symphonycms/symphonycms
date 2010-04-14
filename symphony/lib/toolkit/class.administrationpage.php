<?php

	require_once(TOOLKIT . '/class.htmlpage.php');
	require_once(TOOLKIT . '/class.alert.php');
	require_once(TOOLKIT . '/class.section.php');
	require_once(TOOLKIT . '/class.layout.php');

	Class AdministrationPage extends HTMLPage{

		public $Alert;

		## These are here for Extension backwards compatibility. Will be
		## removed in a later version.
		const PAGE_ALERT_NOTICE = 'notice';
		const PAGE_ALERT_ERROR = 'error';

		var $_navigation;
		var $_Parent;
		var $_context;

		### By CZ: Should be checked and/or rewritten
		var $_layout;

		public function __construct(){
			parent::__construct();

			$this->Html->setElementStyle('html');

			$this->_navigation = array();
			$this->Alert = NULL;

		}

		public function setPageType($type){
			//$this->addStylesheetToHead(ADMIN_URL . '/assets/css/' . ($type == 'table' ? 'tables' : 'forms') . '.css', 'screen', 30);
		}

		public function setTitle($val, $position=null) {
			return $this->addElementToHead(new XMLElement('title', $val), $position);
		}

		public function Context(){
			return $this->_context;
		}

		public function build($context = NULL){

			$this->_context = $context;

			$this->Html->setDTD('<!DOCTYPE html>');
			$this->Html->setAttribute('lang', Symphony::lang());
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);

			$this->addScriptToHead(ADMIN_URL . '/assets/js/jquery.js', 49);
			$this->addScriptToHead(ADMIN_URL . '/assets/js/jquery-ui.js', 50);
			$this->addScriptToHead(ADMIN_URL . '/assets/js/symphony.collapsible.js', 51);
			$this->addScriptToHead(ADMIN_URL . '/assets/js/symphony.orderable.js', 52);
			$this->addScriptToHead(ADMIN_URL . '/assets/js/symphony.duplicator.js', 53);
			$this->addScriptToHead(ADMIN_URL . '/assets/js/admin.js', 54);
			$this->addScriptToHead(ADMIN_URL . '/assets/js/symphony.js', 55);
			$this->addScriptToHead(ADMIN_URL . '/assets/js/symphony.layout.js', 56);

			$this->addStylesheetToHead(ADMIN_URL . '/assets/css/symphony.css', 'screen', 60);
			$this->addStylesheetToHead(ADMIN_URL . '/assets/css/symphony.duplicator.css', 'screen', 70);
			$this->addStylesheetToHead(ADMIN_URL . '/assets/css/symphony.layout.css', 'screen', 80);

			###
			# Delegate: InitaliseAdminPageHead
			# Description: Allows developers to insert items into the page HEAD. Use $context['parent']->Page
			#			   for access to the page object
			ExtensionManager::instance()->notifyMembers('InitaliseAdminPageHead', '/backend/');

			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

			$this->prepare();

			if(isset($_REQUEST['action'])){
				$this->action();
				Administration::instance()->Profiler->sample('Page action run', PROFILE_LAP);
			}

			## Build the form
			$this->Form = Widget::Form(Administration::instance()->getCurrentPageURL(), 'post');
			$h1 = new XMLElement('h1');
			$h1->appendChild(Widget::Anchor(Symphony::Configuration()->get('sitename', 'symphony'), rtrim(URL, '/') . '/'));
			$this->Form->appendChild($h1);
			$this->appendSession();
			$this->appendNavigation();
			$this->view();

			###
			# Delegate: AppendElementBelowView
			# Description: Allows developers to add items just above the page footer. Use $context['parent']->Page
			#			   for access to the page object
			ExtensionManager::instance()->notifyMembers('AppendElementBelowView', '/backend/');

			$this->appendAlert();

			Administration::instance()->Profiler->sample('Page content created', PROFILE_LAP);
		}

		public function view(){
			$this->__switchboard();
		}

		public function action(){
			$this->__switchboard('action');
		}

		public function prepare(){
			$this->__switchboard('prepare');
		}

		public function __switchboard($type='view'){

			if(!isset($this->_context[0]) || trim($this->_context[0]) == '') $context = 'index';
			else $context = $this->_context[0];

			$function = '__' . $type . ucfirst($context);

			// If there is no view function, throw an error
			if (!is_callable(array($this, $function))){

				if ($type == 'view'){
					throw new AdministrationPageNotFoundException;
				}

				return false;
			}
			$this->$function();
		}

		public function pageAlert($message=NULL, $type=Alert::NOTICE){

			if(is_null($message) && $type == Alert::ERROR){
				$message = 'There was a problem rendering this page. Please check the activity log for more details.';
			}

			$message = __($message);

			if(strlen(trim($message)) == 0) throw new Exception('A message must be supplied unless flagged as Alert::ERROR');

			if(!($this->Alert instanceof Alert) || ($this->Alert->type == Alert::NOTICE && in_array($type, array(Alert::ERROR, Alert::SUCCESS)))){
				$this->Alert = new Alert($message, $type);
			}
		}

		public function appendAlert(){

			###
			# Delegate: AppendPageAlert
			# Description: Allows for appending of alerts. Administration::instance()->Page->Alert is way to tell what
			# is currently in the system
			ExtensionManager::instance()->notifyMembers('AppendPageAlert', '/backend/');

			if(($this->Alert instanceof Alert)){
				$this->Form->prependChild($this->Alert->asXML());
			}
		}

		public function appendSession(){

			$ul = new XMLElement('ul');
			$ul->setAttribute('id', 'session');

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor(Administration::instance()->User->getFullName(), ADMIN_URL . '/system/users/edit/' . Administration::instance()->User->id . '/'));
			$ul->appendChild($li);

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor(__('Logout'), ADMIN_URL . '/logout/'));
			$ul->appendChild($li);

			###
			# Delegate: AddElementToFooter
			# Description: Add new list elements to the footer
			ExtensionManager::instance()->notifyMembers('AddElementToFooter', '/backend/', array('wrapper' => &$ul));

			$this->Form->appendChild($ul);
		}

		public function appendSubheading($string, $link=NULL){

			if($link && is_object($link)) $string .= ' ' . $link->generate(false);
			elseif($link) $string .= ' ' . $link;

			$this->Form->appendChild(new XMLElement('h2', $string));
		}

		public function appendNavigation(){

			$nav = $this->getNavigationArray();

			####
			# Delegate: NavigationPreRender
			# Description: Immediately before displaying the admin navigation. Provided with the navigation array
			#              Manipulating it will alter the navigation for all pages.
			# Global: Yes
			ExtensionManager::instance()->notifyMembers('NavigationPreRender', '/backend/', array('navigation' => &$nav));

			$xNav = new XMLElement('ul');
			$xNav->setAttribute('id', 'nav');

			foreach($nav as $n){
				$n_bits = explode('/', $n['link'], 3);

				$can_access = true;

				if($n['visible'] != 'no'){

					if($can_access == true) {

						$xGroup = new XMLElement('li', $n['name']);
						$xGroup->setAttribute('id', 'nav-' . Lang::createHandle($n['name']));

						if(isset($n['class']) && trim($n['name']) != '') $xGroup->setAttribute('class', $n['class']);

						$xChildren = new XMLElement('ul');

						$hasChildren = false;

						if(is_array($n['children']) && !empty($n['children'])){
							foreach($n['children'] as $c){

								$can_access_child = true;

								if($c['visible'] != 'no'){

									if($can_access_child == true) {

										## Make sure preferences menu only shows if multiple languages or extension preferences are available
										if($c['name'] == __('Preferences') && $n['name'] == __('System')){
											$extensions = Symphony::Database()->query("
													SELECT
														COUNT(id)
													FROM
														`tbl_extensions_delegates`
													WHERE
														`delegate` = 'AddCustomToolFieldsets'
											");

											$l = Lang::getAvailableLanguages(true);
											if(count($l) == 1 && (!$result->valid())){
												continue;
											}

										}
										##

										$xChild = new XMLElement('li');
										$xLink = new XMLElement('a', $c['name']);
										$xLink->setAttribute('href', ADMIN_URL . '' . $c['link']);
										$xChild->appendChild($xLink);

										$xChildren->appendChild($xChild);
										$hasChildren = true;

									}
								}

							}

							if($hasChildren){
								$xGroup->appendChild($xChildren);
								$xNav->appendChild($xGroup);
							}
						}
					}
				}
			}

			$this->Form->appendChild($xNav);
			Administration::instance()->Profiler->sample('Navigation Built', PROFILE_LAP);
		}

		public function getNavigationArray(){
			if(empty($this->_navigation)) $this->__buildNavigation();
			return $this->_navigation;
		}

		private static function __navigationFindGroupIndex($nav, $name){
			foreach($nav as $index => $item){
				if($item['name'] == $name) return $index;
			}
			return false;
		}

		protected function __buildNavigation(){

			$nav = array();

			$xml = simplexml_load_file(ASSETS . '/navigation.xml');

			foreach($xml->xpath('/navigation/group') as $n){

				$index = (string)$n->attributes()->index;
				$children = $n->xpath('children/item');
				$content = $n->attributes();

				if(isset($nav[$index])){
					do{
						$index++;
					}while(isset($nav[$index]));
				}

				$nav[$index] = array(
					'name' => __(strval($content->name)),
					'index' => $index,
					'children' => array()
				);

				if(strlen(trim((string)$content->limit)) > 0){
					$nav[$index]['limit'] = (string)$content->limit;
				}

				if(count($children) > 0){
					foreach($children as $child){
						$limit = (string)$child->attributes()->limit;

						$item = array(
							'link' => (string)$child->attributes()->link,
							'name' => __(strval($child->attributes()->name)),
							'visible' => ((string)$child->attributes()->visible == 'no' ? 'no' : 'yes'),
						);

						if(strlen(trim($limit)) > 0) $item['limit'] = $limit;

						$nav[$index]['children'][] = $item;
					}
				}
			}

			foreach(new SectionIterator as $s){

				$group_index = self::__navigationFindGroupIndex($nav, $s->{'navigation-group'});

				if($group_index === false){
					$group_index = General::array_find_available_index($nav, 0);

					$nav[$group_index] = array(
						'name' => $s->{'navigation-group'},
						'index' => $group_index,
						'children' => array(),
						'limit' => NULL
					);
				}

				$nav[$group_index]['children'][] = array(
					'link' => '/publish/' . $s->handle . '/',
					'name' => $s->name,
					'type' => 'section',
					'section' => array('id' => $s->guid, 'handle' => $s->handle),
					'visible' => ($s->{'hidden-from-publish-menu'} == 'no' ? 'yes' : 'no')
				);
			}

			$extensions = ExtensionManager::instance()->listInstalledHandles();

			foreach($extensions as $e){
				$info = ExtensionManager::instance()->about($e);

				if(isset($info['navigation']) && is_array($info['navigation']) && !empty($info['navigation'])){

					foreach($info['navigation'] as $item){

						$type = (isset($item['children']) ? Extension::NAV_GROUP : Extension::NAV_CHILD);

						switch($type){

							case Extension::NAV_GROUP:

								$index = General::array_find_available_index($nav, $item['location']);

								$nav[$index] = array(
									'name' => $item['name'],
									'index' => $index,
									'children' => array(),
									'limit' => (!is_null($item['limit']) ? $item['limit'] : NULL)
								);

								foreach($item['children'] as $child){

									if(!isset($child['relative']) || $child['relative'] == true){
										$link = '/extension/' . $e . '/' . ltrim($child['link'], '/');
									}
									else{
										$link = '/' . ltrim($child['link'], '/');
									}

									$nav[$index]['children'][] = array(

										'link' => $link,
										'name' => $child['name'],
										'visible' => ($child['visible'] == 'no' ? 'no' : 'yes'),
										'limit' => (!is_null($child['limit']) ? $child['limit'] : NULL)
									);
								}

								break;

							case Extension::NAV_CHILD:

								if(!isset($item['relative']) || $item['relative'] == true){
									$link = '/extension/' . $e . '/' . ltrim($item['link'], '/');
								}
								else{
									$link = '/' . ltrim($item['link'], '/');
								}

								if(!is_numeric($item['location'])){
									// is a navigation group
									$group_name = $item['location'];
									$group_index = $this->__findLocationIndexFromName($nav, $item['location']);
								} else {
									// is a legacy numeric index
									$group_index = $item['location'];
								}

								$child = array(
									'link' => $link,
									'name' => $item['name'],
									'visible' => ($item['visible'] == 'no' ? 'no' : 'yes'),
									'limit' => (!is_null($item['limit']) ? $item['limit'] : NULL)
								);

								if ($group_index === false) {
									// add new navigation group
									$nav[] = array(
										'name' => $group_name,
										'index' => $group_index,
										'children' => array($child),
										'limit' => (!is_null($item['limit']) ? $item['limit'] : NULL)
									);
								} else {
									// add new location by index
									$nav[$group_index]['children'][] = $child;
								}


								break;

						}

					}

				}

			}

			####
			# Delegate: ExtensionsAddToNavigation
			# Description: After building the Navigation properties array. This is specifically
			# 			for extentions to add their groups to the navigation or items to groups,
			# 			already in the navigation. Note: THIS IS FOR ADDING ONLY! If you need
			#			to edit existing navigation elements, use the 'NavigationPreRender' delegate.
			# Global: Yes
			ExtensionManager::instance()->notifyMembers(
				'ExtensionsAddToNavigation', '/backend/', array('navigation' => &$nav)
			);

			$pageCallback = Administration::instance()->getPageCallback();

			$pageRoot = $pageCallback['pageroot'] . (isset($pageCallback['context'][0]) ? $pageCallback['context'][0] . '/' : '');
			$found = $this->__findActiveNavigationGroup($nav, $pageRoot);

			## Normal searches failed. Use a regular expression using the page root. This is less
			## efficent and should never really get invoked unless something weird is going on
			if(!$found) $this->__findActiveNavigationGroup($nav, '/^' . str_replace('/', '\/', $pageCallback['pageroot']) . '/i', true);

			ksort($nav);
			$this->_navigation = $nav;

		}

		protected function __findLocationIndexFromName($nav, $name){
			foreach($nav as $index => $group){
				if($group['name'] == $name){
					return $index;
				}
			}

			return false;
		}

		protected function __findActiveNavigationGroup(&$nav, $pageroot, $pattern=false){

			foreach($nav as $index => $contents){
				if(is_array($contents['children']) && !empty($contents['children'])){
					foreach($contents['children'] as $item){

						if($pattern && preg_match($pageroot, $item['link'])){
							$nav[$index]['class'] = 'active';
							return true;
						}

						elseif($item['link'] == $pageroot){
							$nav[$index]['class'] = 'active';
							return true;
						}

					}
				}
			}

			return false;

		}

		public function appendViewOptions(array $options) {
			$div = new XMLElement('div', NULL, array('id' => 'tab'));

			if(array_key_exists('subnav', $options)){
				$ul = new XMLElement('ul');
				foreach($options['subnav'] as $name => $link){
					$li = new XMLElement('li');
					$li->appendChild(Widget::Anchor($name, $link, NULL, (Administration::instance()->getCurrentPageURL() == $link ? 'active' : NULL)));
					$ul->appendChild($li);
				}
				$div->appendChild($ul);
			}

			foreach($options as $item){
				if(is_a($item, 'XMLElement')){
					$div->appendChild($item);
				}
			}

			$this->Form->appendChild($div);
		}

		public function wrapFormElementWithError($element, $error=NULL){
			$div = new XMLElement('div');
			$div->setAttribute('class', 'invalid');
			$div->appendChild($element);
			if($error) $div->appendChild(new XMLElement('p', $error));

			return $div;
		}

	}

