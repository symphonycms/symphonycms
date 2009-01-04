<?php
	
	require_once(TOOLKIT . '/class.htmlpage.php');
	
	Class AdministrationPage extends HTMLPage{
		
		const PAGE_ALERT_NOTICE = 1;
		const PAGE_ALERT_ERROR = 2;
		
		var $_navigation;
		var $_Parent;
		var $_alert;
		var $_context;
		
		function __construct(&$parent){
			parent::__construct();
			
			$this->Html->setElementStyle('html');
			
			$this->_Parent = $parent;
			$this->_navigation = array();
			$this->_alert = NULL;
			
		}
		
		function setPageType($type){
			$this->addStylesheetToHead(URL . '/symphony/assets/' . ($type == 'table' ? 'tables' : 'forms') . '.css', 'screen', 30);
		}
		
		function setTitle($val, $position=10){
			return $this->addElementToHead(new XMLElement('title', $val), $position);
		}
		
		function build($context = NULL){
			
			$this->_context = $context;
			
			if(!$this->canAccessPage()){
				$this->_Parent->customError(E_USER_ERROR, 'Access Denied', 'You are not authorised to access this page.');
				exit();
			}
			
			$this->Html->setDTD('<!DOCTYPE html>');
			$this->Html->setAttribute('lang', __LANG__);
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addElementToHead(new XMLElement('link', NULL, array('rel' => 'icon', 'href' => URL.'/symphony/assets/images/bookmark.png', 'type' => 'image/png')), 20); 
			$this->addElementToHead(new XMLElement('!--[if IE]><link rel="stylesheet" href="'.URL.'/symphony/assets/legacy.css" type="text/css"><![endif]--'), 40);			
			$this->addScriptToHead(URL . '/symphony/assets/admin.js', 50);
			
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
			$h1->appendChild(Widget::Anchor($this->_Parent->Configuration->get('sitename', 'general'), rtrim(URL, '/') . '/'));
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

		function pageAlert($message=NULL, $type=self::PAGE_ALERT_NOTICE, $dynamic_elements=NULL){

			if($this->_alert == NULL || ($this->_alert != NULL && $this->_alert['type'] == self::PAGE_ALERT_NOTICE && $type == self::PAGE_ALERT_ERROR)){
				
				if(!$message) $message = 'There was a problem rendering this page. Please check the activity log for more details.';
				
				$message = __($message);
				
				if(is_array($dynamic_elements) && !empty($dynamic_elements)){
					
					foreach($dynamic_elements as $index => $value) $message = str_replace('{'.($index + 1).'}', $value, $message);
					
				}
				
				if(trim(strip_tags($message)) != '') $this->_alert = array('type' => $type, 'message' => $message);					
			}
		}
		
		function appendAlert(){
			
			###
			# Delegate: AppendPageAlert
			# Description: Allows for appending of alerts. $context['alert'] is way to tell what is currently in the system
			$this->_Parent->ExtensionManager->notifyMembers('AppendPageAlert', '/backend/', array('alert' => (isset($this->_alert) ? $this->_alert : NULL)));

			if(!isset($this->_alert)) return;
			
			$p = new XMLElement('p', $this->_alert['message']);
			$p->setAttribute('id', 'notice');
			if($this->_alert['type'] == self::PAGE_ALERT_ERROR) $p->setAttribute('class', 'error');
			
			$this->Form->prependChild($p);
		}
		
		function appendFooter(){
						
			$ul = new XMLElement('ul');
			$ul->setAttribute('id', 'usr');

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor($this->_Parent->Author->getFullName(), URL . '/symphony/system/authors/edit/' . $this->_Parent->Author->get('id') . '/'));
			$ul->appendChild($li);
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('Logout', URL . '/symphony/logout/'));
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
			$this->_Parent->ExtensionManager->notifyMembers('NavigationPreRender', '/administration/', array('navigation' => &$nav));

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
											$this->_Parent->customError(E_USER_ERROR, 'Access Denied', 'You are not authorised to access this page.'); 
										}

									}

									elseif($c['type'] == 'section'){

										if($_REQUEST['section'] == $c['section_id'] && preg_match('#^/publish/section/#', $_REQUEST['page'])){
											$this->_Parent->customError(E_USER_ERROR, 'Access Denied', 'You are not authorised to access this section.'); 
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
										
										## Make sure preferences menu only shows if extensions are subscribed to it
										if($c['name'] == 'Preferences' && $n['name'] == 'System'){
											$extensions = $this->_Parent->Database->fetch("
													SELECT * 
													FROM `tbl_extensions_delegates` 
													WHERE `delegate` = 'AddCustomPreferenceFieldsets'"
											);
											
											if(!is_array($extensions) || empty($extensions)){
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
				
				elseif(($page == $item['link']) && isset($item['limit'])){						
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
	
		function __buildNavigation(){

			$nav = array();
		
			$xml = new XmlDoc();
			if(!$xml->parseFile(ASSETS . '/navigation.xml')) 
				$this->_Parent->customError(E_USER_ERROR, 'Failed to load Navigation', 'There was a problem loading the Symphony navigation XML document.'); 
		
			$nodes = $xml->getArray();
			
			$sections_index = 0;
			$extension_index = 0;
			
			foreach($nodes['navigation'] as $n){
				
				$content = $n['group']['attributes'];
				$children = $n['group'][0]['children'];
				$index = $n['group']['attributes']['index'];
				
				if($n['group']['attributes']['sections'] == 'true') $sections_index = $index;
				
				if(isset($nav[$index])){
					do{
						$index++;
					}while(isset($nav[$index]));
				}
				
				if(!empty($content)) $nav[$index] = $content;
			
				if(@is_array($children)){
					foreach($children as $n){
						if(!empty($n['item']['attributes'])) $nav[$index]['children'][] = $n['item']['attributes'];
					}
				}
			}

			$sections = $this->_Parent->Database->fetch("SELECT * FROM `tbl_sections` ORDER BY `sortorder` ASC");

			if(is_array($sections) && !empty($sections)){
				foreach($sections as $s){

					//$visible = ($this->_Parent->Author->isDeveloper() || (!$this->_Parent->Author->isDeveloper() && in_array($s['id'], $this->_Parent->Author->getAuthorAllowableSections())));

					$nav[$sections_index]['children'][] = array('link' => '/publish/' . $s['handle'] . '/', 
															 		'name' => $s['name'], 
															 		'type' => 'section',
																	'section' => array('id' => $s['id'], 'handle' => $s['handle']),
															 		'visible' => ($s['hidden'] == 'no' ? 'yes' : 'no'));
															

				}
			}
			
			$extensions = $this->_Parent->ExtensionManager->listInstalledHandles();
			//print_r($nav); die();
			foreach($extensions as $e){
				$info = $this->_Parent->ExtensionManager->about($e);
				if(isset($info['navigation']) && is_array($info['navigation']) && !empty($info['navigation'])){
					
					foreach($info['navigation'] as $item){
					
						$type = (isset($item['children']) ? Extension::NAV_GROUP : Extension::NAV_CHILD);

						switch($type){
							
							case Extension::NAV_GROUP:
							
								$index = General::array_find_available_index($nav, $item['location']);

								$nav[$index] = array(
									'name' => $item['name'],
									'index' => $index,
									'children' => array()
								);
								
								foreach($item['children'] as $child){
									
									$nav[$index]['children'][] = array(

										'link' => '/extension/' . $e . '/' . ltrim($child['link'], '/'),
										'name' => $child['name'],
										'visible' => ($child['visible'] == 'no' ? 'no' : 'yes'),

									);									
								}
								
								break;
								
							case Extension::NAV_CHILD:
		
								$nav[$item['location']]['children'][] = array(
									
									'link' => '/extension/' . $e . '/' . ltrim($item['link'], '/'),
									'name' => $item['name'],
									'visible' => ($item['visible'] == 'no' ? 'no' : 'yes'),
									
								);
							
								break;
							
						}
						
					}
					
				}
			}
			
			####
			# Delegate: ExtensionsAddToNavigation
			# Description: After building the Navigation properties array. This is specifically for extentions to add their groups to the navigation or items to groups,
			#			   already in the navigation. Note: THIS IS FOR ADDING ONLY! If you need to edit existing navigation elements, use the 'NavigationPreRender' delegate.
			# Global: Yes
			//$this->_Parent->ExtensionManager->notifyMembers('ExtensionsAddToNavigation', '/administration/', array('navigation' => &$nav));
			
			$pageCallback = $this->_Parent->getPageCallback();
			
			$pageRoot = $pageCallback['pageroot'] . (isset($pageCallback['context'][0]) ? $pageCallback['context'][0] . '/' : '');
			$found = $this->__findActiveNavigationGroup($nav, $pageRoot);

			## Normal searches failed. Use a regular expression using the page root. This is less efficent and should never really get invoked
			## unless something weird is going on
			if(!$found) $this->__findActiveNavigationGroup($nav, '/^' . str_replace('/', '\/', $pageCallback['pageroot']) . '/i', true);

			ksort($nav);		
			$this->_navigation = $nav;
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
			
			if(!$types = $this->_Parent->Database->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` ORDER BY `type` ASC")) return $system_types;
			
			return (is_array($types) && !empty($types) ? General::array_remove_duplicates(array_merge($system_types, $types)) : $system_types);

		}
	}

?>