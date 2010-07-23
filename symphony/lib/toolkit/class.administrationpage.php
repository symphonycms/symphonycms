<?php
	
	require_once(TOOLKIT . '/class.htmlpage.php');
	require_once(TOOLKIT . '/class.alert.php');
	
	Class AdministrationPage extends HTMLPage{
		
		public $Alert;
		
		## These are here for Extension backwards compatibility. Will be 
		## removed in a later version.
		const PAGE_ALERT_NOTICE = 'notice';
		const PAGE_ALERT_ERROR = 'error';
		
		var $_navigation;
		var $_Parent;
		var $_context;
		
		function __construct(&$parent){
			parent::__construct();
			
			$this->Html->setElementStyle('html');
			
			$this->_Parent = $parent;
			$this->_navigation = array();
			$this->Alert = NULL;
			
		}
		
		function setPageType($type){
			$this->addStylesheetToHead(URL . '/symphony/assets/' . ($type == 'table' ? 'tables' : 'forms') . '.css', 'screen', 30);
		}
		
		function setTitle($val, $position=null) {
			return $this->addElementToHead(new XMLElement('title', $val), $position);
		}
	
		public function Context(){
			return $this->_context;
		}
		
		function build($context = NULL){
			
			$this->_context = $context;
			
			if(!$this->canAccessPage()){
				$this->_Parent->customError(E_USER_ERROR, __('Access Denied'), __('You are not authorised to access this page.'));
				exit();
			}
			
			$this->Html->setDTD('<!DOCTYPE html>');
			$this->Html->setAttribute('lang', Symphony::lang());
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addStylesheetToHead(URL . '/symphony/assets/symphony.duplicator.css', 'screen', 70);
			$this->addScriptToHead(URL . '/symphony/assets/jquery.js', 50);
			$this->addScriptToHead(URL . '/symphony/assets/symphony.collapsible.js', 60);
			$this->addScriptToHead(URL . '/symphony/assets/symphony.orderable.js', 61);
			$this->addScriptToHead(URL . '/symphony/assets/symphony.duplicator.js', 62);
			$this->addScriptToHead(URL . '/symphony/assets/admin.js', 70);
			
			###
			# Delegate: InitaliseAdminPageHead
			# Description: Allows developers to insert items into the page HEAD. Use $context['parent']->Page
			#			   for access to the page object
			$this->_Parent->ExtensionManager->notifyMembers('InitaliseAdminPageHead', '/backend/');	
			
			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
				
			if(isset($_REQUEST['action'])){
				$this->action();
				$this->_Parent->Profiler->sample('Page action run', PROFILE_LAP);
			}
			
			## Build the form
			$this->Form = Widget::Form($this->_Parent->getCurrentPageURL(), 'post');
			$h1 = new XMLElement('h1');
			$h1->appendChild(Widget::Anchor(Symphony::Configuration()->get('sitename', 'general'), rtrim(URL, '/') . '/'));
			$this->Form->appendChild($h1);
			
			$this->appendNavigation();
			$this->view();
			
			###
			# Delegate: AppendElementBelowView
			# Description: Allows developers to add items just above the page footer. Use $context['parent']->Page
			#			   for access to the page object
			$this->_Parent->ExtensionManager->notifyMembers('AppendElementBelowView', '/backend/');			
						
			$this->appendFooter();
			$this->appendAlert();

			$this->_Parent->Profiler->sample('Page content created', PROFILE_LAP);
		}
		
		function view(){
			$this->__switchboard();
		}
		
		function action(){
			$this->__switchboard('action');
		}

		function __switchboard($type='view'){
			
			if(!isset($this->_context[0]) || trim($this->_context[0]) == '') $context = 'index';
			else $context = $this->_context[0];
			
			$function = ($type == 'action' ? '__action' : '__view') . ucfirst($context);
			
			if(!method_exists($this, $function)) {
				
				## If there is no action function, just return without doing anything
				if($type == 'action') return;
				
				$this->_Parent->errorPageNotFound();
				
			}
			
			$this->$function();

		}

		function pageAlert($message=NULL, $type=Alert::NOTICE){
			
			if(is_null($message) && $type == Alert::ERROR){ 
				$message = 'There was a problem rendering this page. Please check the activity log for more details.';
			}
			
			$message = __($message);
						
			if(strlen(trim($message)) == 0) throw new Exception('A message must be supplied unless flagged as Alert::ERROR');				
						
			if(!($this->Alert instanceof Alert) || ($this->Alert->type == Alert::NOTICE && in_array($type, array(Alert::ERROR, Alert::SUCCESS)))){
				$this->Alert = new Alert($message, $type);
			}
		}
		
		function appendAlert(){
			
			###
			# Delegate: AppendPageAlert
			# Description: Allows for appending of alerts. Administration::instance()->Page->Alert is way to tell what 
			# is currently in the system
			$this->_Parent->ExtensionManager->notifyMembers('AppendPageAlert', '/backend/');

			if(($this->Alert instanceof Alert)){
				$this->Form->prependChild($this->Alert->asXML());
			}
		}
		
		function appendFooter(){
		
			$version = new XMLElement('p', 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'), array('id' => 'version'));
			$this->Form->appendChild($version);
						
			$ul = new XMLElement('ul');
			$ul->setAttribute('id', 'usr');

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor($this->_Parent->Author->getFullName(), URL . '/symphony/system/authors/edit/' . $this->_Parent->Author->get('id') . '/'));
			$ul->appendChild($li);
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor(__('Logout'), URL . '/symphony/logout/'));
			$ul->appendChild($li);
			
			###
			# Delegate: AddElementToFooter
			# Description: Add new list elements to the footer
			$this->_Parent->ExtensionManager->notifyMembers('AddElementToFooter', '/backend/', array('wrapper' => &$ul));
						
			$this->Form->appendChild($ul);
		}
		
		function appendSubheading($string, $link=NULL){
			
			if($link && is_object($link)) $string .= ' ' . $link->generate(false);
			elseif($link) $string .= ' ' . $link;
			
			$this->Form->appendChild(new XMLElement('h2', $string));
		}
		
		function appendNavigation(){

			$nav = $this->getNavigationArray();

			####
			# Delegate: NavigationPreRender
			# Description: Immediately before displaying the admin navigation. Provided with the navigation array
			#              Manipulating it will alter the navigation for all pages.
			# Global: Yes
			$this->_Parent->ExtensionManager->notifyMembers('NavigationPreRender', '/backend/', array('navigation' => &$nav));

			$xNav = new XMLElement('ul');
			$xNav->setAttribute('id', 'nav');

			foreach($nav as $n){
				$n_bits = explode('/', $n['link'], 3);

				$can_access = false;

				if($n['visible'] != 'no'){
					
					if(!isset($n['limit']) || $n['limit'] == 'author')
						$can_access = true;

					elseif($n['limit'] == 'developer' && $this->_Parent->Author->isDeveloper())
						$can_access = true;

					elseif($n['limit'] == 'primary' && $this->_Parent->Author->isPrimaryAccount())
						$can_access = true;
					
					if($can_access) {

						$xGroup = new XMLElement('li', $n['name']);
						if(isset($n['class']) && trim($n['name']) != '') $xGroup->setAttribute('class', $n['class']);

						$xChildren = new XMLElement('ul');

						$hasChildren = false;

						if(is_array($n['children']) && !empty($n['children'])){ 
							foreach($n['children'] as $c){

								$can_access_child = false;	

								## Check if this is a Section or Extension, and die if the user is not allowed to access it	
								if(!$this->_Parent->Author->isDeveloper() && $c['visible'] == 'no'){
									if($c['type'] == 'extension'){

										$bits = preg_split('/\//i', $c['link'], 2, PREG_SPLIT_NO_EMPTY);

										if(!$this->_Parent->Author->isDeveloper() && preg_match('#^/extension/#i'.$bits[2].'/i', $_REQUEST['page'])){
											$this->_Parent->customError(E_USER_ERROR, __('Access Denied'), __('You are not authorised to access this page.'));
										}

									}

									elseif($c['type'] == 'section'){

										if($_REQUEST['section'] == $c['section_id'] && preg_match('#^/publish/section/#', $_REQUEST['page'])){
											$this->_Parent->customError(E_USER_ERROR, __('Access Denied'), __('You are not authorised to access this section.'));
										}

									}
								}

								if($c['visible'] != 'no'){

									if(!isset($c['limit']) || $c['limit'] == 'author')
										$can_access_child = true;

									elseif($c['limit'] == 'developer' && $this->_Parent->Author->isDeveloper())
										$can_access_child = true;		

									elseif($c['limit'] == 'primary' && $this->_Parent->Author->isPrimaryAccount())
										$can_access_child = true;

									if($can_access_child) {
										
										## Make sure preferences menu only shows if multiple languages or extension preferences are available
										if($c['name'] == __('Preferences') && $n['name'] == __('System')){
											$extensions = Symphony::Database()->fetch("
													SELECT * 
													FROM `tbl_extensions_delegates` 
													WHERE `delegate` = 'AddCustomPreferenceFieldsets'"
											);

											$l = Lang::getAvailableLanguages(new ExtensionManager($this->_Parent));
											if(count($l) == 1 && (!is_array($extensions) || empty($extensions))){
												continue;
											}
											
										}
										##
										
										$xChild = new XMLElement('li');
										$xLink = new XMLElement('a', $c['name']);
										$xLink->setAttribute('href', URL . '/symphony' . $c['link']);
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
			$this->_Parent->Profiler->sample('Navigation Built', PROFILE_LAP);	
		}
		
		function getNavigationArray(){
			if(empty($this->_navigation)) $this->__buildNavigation();
			return $this->_navigation;
		}

		function canAccessPage(){
			
			$nav = $this->getNavigationArray();
			
			$page = '/' . trim(getCurrentPage(), '/') . '/';
			
			$page_limit = 'author';					

			foreach($nav as $item){

				if(General::in_array_multi($page, $item['children'])){

		            if(is_array($item['children'])){
		                foreach($item['children'] as $c){
		                    if($c['link'] == $page && isset($c['limit']))
		                        $page_limit	= $c['limit'];	          
		                }
		            }
		
					if(isset($item['limit']) && $page_limit != 'primary'){					
						if($page_limit == 'author' && $item['limit'] == 'developer') $page_limit = 'developer';
					}

				}
				
				elseif(isset($item['link']) && ($page == $item['link']) && isset($item['limit'])){						
					$page_limit	= $item['limit'];	  	
				}
			}

			if($page_limit == 'author')
				return true;

			elseif($page_limit == 'developer' && ($this->_Parent->Author->isDeveloper()))
				return true;		

			elseif($page_limit == 'primary' && $this->_Parent->Author->isPrimaryAccount())
				return true;
				
			return false;			
		}
		
		private static function __navigationFindGroupIndex($nav, $name){
			foreach($nav as $index => $item){
				if($item['name'] == $name) return $index;
			}
			return false;
		}
		
		function __buildNavigation(){

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
			
			$sections = Symphony::Database()->fetch("SELECT * FROM `tbl_sections` ORDER BY `sortorder` ASC");

			if(is_array($sections) && !empty($sections)){
				foreach($sections as $s){
					
					$group_index = self::__navigationFindGroupIndex($nav, $s['navigation_group']);
					
					if($group_index === false){
						$group_index = General::array_find_available_index($nav, 0);

						$nav[$group_index] = array(
							'name' => $s['navigation_group'],
							'index' => $group_index,
							'children' => array(),
							'limit' => NULL
						);
						
					}
									
					$nav[$group_index]['children'][] = array(
						'link' => '/publish/' . $s['handle'] . '/', 
						'name' => $s['name'], 
						'type' => 'section',
						'section' => array('id' => $s['id'], 'handle' => $s['handle']),
						'visible' => ($s['hidden'] == 'no' ? 'yes' : 'no')
					);
													

				}
			}
			
			$extensions = Administration::instance()->ExtensionManager->listInstalledHandles();

			foreach($extensions as $e){
				$info = Administration::instance()->ExtensionManager->about($e);

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
			Administration::instance()->ExtensionManager->notifyMembers(
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
		
		private function __findLocationIndexFromName($nav, $name){
			foreach($nav as $index => $group){
				if($group['name'] == $name){
					return $index;
				}
			}
			
			return false;
		}
		
		function __findActiveNavigationGroup(&$nav, $pageroot, $pattern=false){
			
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
		
		function wrapFormElementWithError($element, $error=NULL){
			$div = new XMLElement('div');
			$div->setAttribute('class', 'invalid');
			$div->appendChild($element);
			if($error) $div->appendChild(new XMLElement('p', $error));
			
			return $div;
		}

		function __fetchAvailablePageTypes(){
			
			$system_types = array('index', 'XML', 'admin', '404', '403');
			
			if(!$types = Symphony::Database()->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` ORDER BY `type` ASC")) return $system_types;
			
			return (is_array($types) && !empty($types) ? General::array_remove_duplicates(array_merge($system_types, $types)) : $system_types);

		}
	}

