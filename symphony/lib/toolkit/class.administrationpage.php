<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The AdministrationPage class represents a Symphony backend page.
	 * It extends the HTMLPage class and unlike the Frontend, is generated
	 * using a number XMLElement objects. Instances of this class override
	 * the view, switchboard and action functions to construct the page. These
	 * functions act as pseudo MVC, with the switchboard being controller,
	 * and the view/action being the view.
	 */

	require_once(TOOLKIT . '/class.htmlpage.php');
	require_once(TOOLKIT . '/class.alert.php');

	Class AdministrationPage extends HTMLPage{

		/**
		 * An instance of the Administration class
		 * @var Administration
		 * @see core.Administration
		 */
		public $_Parent;

		/**
		 * An instance of the Alert class. Used to display page level
		 * messages to Symphony users.
		 * @var Alert
		 */
		public $Alert = null;

		/**
		 * As the name suggests, a `<div>` that holds the following `$Header`,
		 * `$Contents` and `$Footer`.
		 * @var XMLElement
		 */
		public $Wrapper = null;

		/**
		 * A `<div>` that contains the header of a Symphony backend page, which
		 * typically contains the Site title and the navigation.
		 * @var XMLElement
		 */
		public $Header = null;

		/**
		 * A `<div>` that contains the content of a Symphony backend page.
		 * @var XMLElement
		 */
		public $Contents = null;

		/**
		 * A `<div>` that contains the Symphony footer, typically the version and
		 * the current Author's details.
		 * @var XMLElement
		 */
		public $Footer = null;

		/**
		 * An associative array of the navigation where the key is the group
		 * index, and the value is an associative array of 'name', 'index' and
		 * 'children'. Name is the name of the this group, index is the same as
		 * the key and children is an associative array of navigation items containing
		 * the keys 'link', 'name' and 'visible'. In Symphony, all navigation items
		 * are contained within a group, and the group has no 'default' link, therefore
		 * it is up to the children to provide the link to pages. This link should be
		 * relative to the Symphony path, although it is possible to provide an
		 * absolute link by providing a key, 'relative' with the value false.
		 * @var array
		 */
		public $_navigation = array();

		/**
		 *  An associative array describing this pages context. This
		 *  can include the section handle, the current entry_id, the page
		 *  name and any flags such as 'saved' or 'created'. This variable
		 * often provided in delegates so extensions can manipulate based
		 * off the current context or add new keys.
		 * @var array
		 */
		public $_context = null;

		/**
		 * The class attribute of the `<body>` element for this page. Defaults
		 * to an empty string
		 * @var string
		 */
		private $_body_class = '';

		/**
		 * Constructor takes the Administration instance and sets it
		 * to be the `$this->_Parent`. Calls the parent constructor to set up
		 * the basic HTML, Head and Body XMLElements. This function
		 * also sets the XMLElement type to be HTML, instead of XML
		 *
		 * @param Administration $parent
		 *  The Administration object that this page has been created from
		 *  passed by reference
		 */
		public function __construct(Administration &$parent){
			parent::__construct();

			$this->Html->setElementStyle('html');
			$this->_Parent = $parent;
		}

		/**
		 * Adds either the tables or forms stylesheet to the page. By default
		 * this is forms, but can be override by passing 'table' to the function.
		 * The stylesheets reside in the `ASSETS` folder
		 *
		 * @param string $type
		 *  Either 'form' or 'table'. Defaults to 'form'
		 */
		public function setPageType($type = 'form'){
			$this->setBodyClass($type == 'form' || $type == 'single' ? 'single' : 'index');
		}

		/**
		 * Setter function to set the class attribute on the `<body>` element.
		 * This function will respect any previous classes that have been added
		 * to this `<body>`
		 *
		 * @param string $class
		 *  The string of the classname, multiple classes can be specified by
		 *  uses a space separator
		 */
		public function setBodyClass($class) {
			# Prevents duplicate "index" classes
			if ($this->_context['page'] != 'index' || $class != 'index')
				$this->_body_class .= $class;
		}

		/**
		 * Sets this page's `$Alert` to an instance of the Alert class of a
		 * given Alter type. Unless the Alert is an Error, it is required
		 * the a message be passed to this function.
		 *
		 * @param string $message
		 *  The message to display to users
		 * @param string $type
		 *  An Alert constant, being `Alert::NOTICE`, `Alert::ERROR` or
		 *  `Alert::SUCCESS`. The differing types will show the error
		 *  in a different style in the backend.
		 */
		public function pageAlert($message = null, $type = Alert::NOTICE){

			if(is_null($message) && $type == Alert::ERROR){
				$message = 'There was a problem rendering this page. Please check the activity log for more details.';
			}

			$message = __($message);

			if(strlen(trim($message)) == 0) throw new Exception('A message must be supplied unless flagged as Alert::ERROR');

			if(!($this->Alert instanceof Alert) || ($this->Alert->type == Alert::NOTICE && in_array($type, array(Alert::ERROR, Alert::SUCCESS)))){
				$this->Alert = new Alert($message, $type);
			}
		}

		/**
		 * Appends the heading of this Symphony page to the Form element.
		 * If a link is provided, it will be append to `$value`
		 *
		 * @param string $value
		 *  The heading text
		 * @param XMLElement|string $html
		 *  Some HTML to append to the heading, this can be provided as an
		 *  XMLElement or as a string. traditionally Symphony uses this to append
		 *  a link to the heading
		 */
		public function appendSubheading($value, $html = null){

			if($html && is_object($html)) $value = '<span>' . $value . '</span> ' . $html->generate(false);
			elseif($html) $value = '<span>' . $value . '</span> ' . $html;

			$this->Contents->prependChild(new XMLElement('h2', $value));
		}

		/**
		 * This function initialises a lot of the basic elements that make up a Symphony
		 * backend page such as the default stylesheets and scripts, the navigation and
		 * the footer. Any alerts are also appended by this function. view() is called to
		 * build the actual content of the page. Delegates fire to allow extensions to add
		 * elements to the `<head>` and footer.
		 *
		 * @see view()
		 * @uses InitaliseAdminPageHead
		 * @uses AppendElementBelowView
		 * @param array $context
		 *  An associative array describing this pages context. This
		 *  can include the section handle, the current entry_id, the page
		 *  name and any flags such as 'saved' or 'created'. This list is not exhaustive
		 *  and extensions can add their own keys to the array.
		 */
		public function build(Array $context = array()){
			$this->_context = $context;

			if(!$this->canAccessPage()){
				Administration::instance()->customError(__('Access Denied'), __('You are not authorised to access this page.'));
			}

			$this->Html->setDTD('<!DOCTYPE html>');
			$this->Html->setAttribute('lang', Lang::get());
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/basic.css', 'screen', 40);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/admin.css', 'screen', 41);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/symphony.duplicator.css', 'screen', 70);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/jquery.js', 50);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/jquery.color.js', 51);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/symphony.collapsible.js', 60);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/symphony.orderable.js', 61);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/symphony.selectable.js', 62);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/symphony.duplicator.js', 63);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/symphony.tags.js', 64);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/symphony.pickable.js', 65);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/admin.js', 71);

			$this->addElementToHead(
				new XMLElement(
					'script',
					"Symphony.Context.add('env', " . json_encode($this->_context) . "); Symphony.Context.add('root', '" . URL . "');",
					array('type' => 'text/javascript')
				), 72
			);

			/**
			 * Allows developers to insert items into the page HEAD. Use `$context['parent']->Page`
			 * for access to the page object
			 *
			 * @delegate InitaliseAdminPageHead
			 * @param string $context
			 *  '/backend/'
			 */
			Symphony::ExtensionManager()->notifyMembers('InitaliseAdminPageHead', '/backend/');

			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

			if(isset($_REQUEST['action'])){
				$this->action();
				Administration::instance()->Profiler->sample('Page action run', PROFILE_LAP);
			}

			$this->Wrapper = new XMLElement('div', NULL, array('id' => 'wrapper'));
			$this->Header = new XMLElement('div', NULL, array('id' => 'header'));

			$h1 = new XMLElement('h1');
			$h1->appendChild(Widget::Anchor(Symphony::Configuration()->get('sitename', 'general'), rtrim(URL, '/') . '/'));
			$this->Header->appendChild($h1);

			$this->appendNavigation();

			$this->Contents = new XMLElement('div', NULL, array('id' => 'contents'));

			## Build the form
			$this->Form = Widget::Form(Administration::instance()->getCurrentPageURL(), 'post');

			$this->view();
			$this->Contents->appendChild($this->Form);

			$this->Footer = new XMLElement('div', NULL, array('id' => 'footer'));

			/**
			 * Allows developers to add items just above the page footer. Use `$context['parent']->Page`
			 * for access to the page object
			 *
			 * @delegate AppendElementBelowView
			 * @param string $context
			 *  '/backend/'
			 */
			Symphony::ExtensionManager()->notifyMembers('AppendElementBelowView', '/backend/');

			$this->appendFooter();
			$this->appendAlert();

			Administration::instance()->Profiler->sample('Page content created', PROFILE_LAP);
		}

		/**
		 * Checks the current Symphony Author can access the current page.
		 * This includes the check to ensure that an Author cannot access a
		 * hidden section.
		 *
		 * @return boolean
		 *  True if the Author can access the current page, false otherwise
		 */
		public function canAccessPage(){

			$nav = $this->getNavigationArray();
			$page = '/' . trim(getCurrentPage(), '/') . '/';

			$page_limit = 'author';

			foreach($nav as $item){
				if(General::in_array_multi($page, $item['children'])){

					if(is_array($item['children'])){
						foreach($item['children'] as $c){
							if($c['type'] == 'section' && $c['visible'] == 'no' && preg_match('#^' . $c['link'] . '#', $page)) {
								$page_limit = 'developer';
							}

							if($c['link'] == $page && isset($c['limit'])) {
								$page_limit	= $c['limit'];
							}
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

			elseif($page_limit == 'developer' && Administration::instance()->Author->isDeveloper())
				return true;

			elseif($page_limit == 'primary' && Administration::instance()->Author->isPrimaryAccount())
				return true;

			return false;
		}

		/**
		 * Appends the `$this->Header`, `$this->Contents` and `$this->Footer`
		 * to `$this->Wrapper` before adding the ID and class attributes for
		 * the `<body>` element. After this has completed the parent's generate
		 * function is called which will convert the `XMLElement`'s into strings
		 * ready for output
		 *
		 * @return string
		 */
		public function generate() {
			$this->Wrapper->appendChild($this->Header);
			$this->Wrapper->appendChild($this->Contents);
			$this->Wrapper->appendChild($this->Footer);

			$this->Body->appendChild($this->Wrapper);

			$this->__appendBodyId();
			$this->__appendBodyClass($this->_context);
			return parent::generate();
		}

		/**
		 * Uses this pages PHP classname as the `<body>` ID attribute.
		 * This function removes 'content' from the start of the classname
		 * and converts all uppercase letters to lowercase and prefixes them
		 * with a hyphen.
		 */
		private function __appendBodyId(){
			// trim "content" from beginning of class name
			$body_id = preg_replace("/^content/", '', get_class($this));

			// lowercase any uppercase letters and prefix with a hyphen
			$body_id = trim(preg_replace("/([A-Z])/e", "'-' . strtolower('\\1')", $body_id), '-');

			if (!empty($body_id)) $this->Body->setAttribute('id', trim($body_id));
		}

		/**
		 * Given the context of the current page, loop over all the values
		 * of the array and append them to the page's body class. If an
		 * context value is numeric it will be prepended by 'id-'.
		 *
		 * @param array $context
		 */
		private function __appendBodyClass(array $context = array()){
			foreach($context as $c) {
				if (is_numeric($c)) $c = 'id-' . $c;
				$body_class .= trim($c) . ' ';
			}
			$classes = array_merge(explode(' ', trim($body_class)), explode(' ', trim($this->_body_class)));
			$body_class = trim(implode(' ', $classes));
			if (!empty($body_class)) $this->Body->setAttribute('class', $body_class);
		}

		/**
		 * Called to build the content for the page. This function immediately calls
		 * `__switchboard()` which acts a bit of a controller to show content based on
		 * off a type, such as 'view' or 'action'. `AdministrationPages` can override this
		 * function to just display content if they do not need the switchboard functionality
		 *
		 * @see __switchboard()
		 */
		public function view(){
			$this->__switchboard();
		}

		/**
		 * This function is called when `$_REQUEST` contains a key of 'action'.
		 * Any logic that needs to occur immediately for the action to complete
		 * should be contained within this function. By default this calls the
		 * `__switchboard` with the type set to 'action'.
		 *
		 * @see __switchboard()
		 */
		public function action(){
			$this->__switchboard('action');
		}

		/**
		 * The `__switchboard` function acts as a controller to display content
		 * based off the $type. By default, the `$type` is 'view' but it can be set
		 * also set to 'action'. The `$type` is prepended by __ and the context is
		 * append to the $type to create the name of the function that will provide
		 * that logic. For example, if the $type was action and the context of the
		 * current page was new, the resulting function to be called would be named
		 * `__actionNew()`. If an action function is not provided by the Page, this function
		 * returns nothing, however if a view function is not provided, a 404 page
		 * will be returned.
		 *
		 * @param string $type
		 *  Either 'view' or 'action', by default this will be 'view'
		 */
		public function __switchboard($type='view'){

			if(!isset($this->_context[0]) || trim($this->_context[0]) == '') $context = 'index';
			else $context = $this->_context[0];

			$function = ($type == 'action' ? '__action' : '__view') . ucfirst($context);

			if(!method_exists($this, $function)) {
				// If there is no action function, just return without doing anything
				if($type == 'action') return;

				Administration::instance()->errorPageNotFound();
			}

			$this->$function();
		}

		/**
		 * If `$this->Alert` is set, it will be prepended to the Form of this page.
		 * A delegate is fired here to allow extensions to provide their
		 * their own Alert messages to the page. Note that only one Alert
		 * is allowed per page at any one time.
		 *
		 * @uses AppendPageAlert
		 */
		public function appendAlert(){
			/**
			 * Allows for appending of alerts. Administration::instance()->Page->Alert is way to tell what
			 * is currently in the system
			 *
			 * @delegate AppendPageAlert
			 * @param string $context
			 *  '/backend/'
			 */
			Symphony::ExtensionManager()->notifyMembers('AppendPageAlert', '/backend/');

			if(($this->Alert instanceof Alert)){
				$this->Header->prependChild($this->Alert->asXML());
			}
		}

		/**
		 * This function will append the Navigation to the AdministrationPage.
		 * It fires a delegate, NavigationPreRender, to allow extensions to manipulate
		 * the navigation. Extensions should not use this to add their own navigation,
		 * they should provide the navigation through their fetchNavigation function.
		 * Note with the Section navigation groups, if there is only one section in a group
		 * and that section is set to visible, the group will not appear in the navigation.
		 *
		 * @uses NavigationPreRender
		 * @see getNavigationArray()
		 * @see toolkit.Extension#fetchNavigation()
		 */
		public function appendNavigation(){
			$nav = $this->getNavigationArray();

			/**
			 * Immediately before displaying the admin navigation. Provided with the
			 * navigation array. Manipulating it will alter the navigation for all pages.
			 *
			 * @delegate NavigationPreRender
			 * @param string $context
			 *  '/backend/'
			 * @param array $nav
			 *  An associative array of the current navigation, passed by reference
			 */
			Symphony::ExtensionManager()->notifyMembers('NavigationPreRender', '/backend/', array('navigation' => &$nav));

			$xNav = new XMLElement('ul');
			$xNav->setAttribute('id', 'nav');

			foreach($nav as $n){
				if($n['visible'] == 'no') continue;

				$can_access = false;

				if(!isset($n['limit']) || $n['limit'] == 'author')
					$can_access = true;

				elseif($n['limit'] == 'developer' && Administration::instance()->Author->isDeveloper())
					$can_access = true;

				elseif($n['limit'] == 'primary' && Administration::instance()->Author->isPrimaryAccount())
					$can_access = true;

				if($can_access) {
					$xGroup = new XMLElement('li', $n['name']);
					if(isset($n['class']) && trim($n['name']) != '') $xGroup->setAttribute('class', $n['class']);

					$hasChildren = false;
					$xChildren = new XMLElement('ul');

					if(is_array($n['children']) && !empty($n['children'])){
						foreach($n['children'] as $c){
							if($c['visible'] == 'no') continue;

							$can_access_child = false;

							if(!isset($c['limit']) || $c['limit'] == 'author')
								$can_access_child = true;

							elseif($c['limit'] == 'developer' && Administration::instance()->Author->isDeveloper())
								$can_access_child = true;

							elseif($c['limit'] == 'primary' && Administration::instance()->Author->isPrimaryAccount())
								$can_access_child = true;

							if($can_access_child) {
								$xChild = new XMLElement('li');
								$xChild->appendChild(
									Widget::Anchor($c['name'], URL . '/symphony' . $c['link'])
								);

								$xChildren->appendChild($xChild);
								$hasChildren = true;
							}
						}

						if($hasChildren){
							$xGroup->appendChild($xChildren);
							$xNav->appendChild($xGroup);
						}
					}
				}
			}

			$this->Header->appendChild($xNav);
			Administration::instance()->Profiler->sample('Navigation Built', PROFILE_LAP);
		}

		/**
		 * Returns the `$_navigation` variable of this Page. If it is empty,
		 * it will be built by `__buildNavigation`
		 *
		 * @see __buildNavigation()
		 * @return array
		 */
		public function getNavigationArray(){
			if(empty($this->_navigation)) $this->__buildNavigation();
			return $this->_navigation;
		}

		/**
		 * This function populates the `$_navigation` array with an associative array
		 * of all the navigation groups and their links. Symphony only supports one
		 * level of navigation, so children links cannot have children links. The default
		 * Symphony navigation is found in the `ASSETS/navigation.xml` folder. This is
		 * loaded first, and then the Section navigation is built, followed by the Extension
		 * navigation. Additionally, this function will set the active group of the navigation
		 * by checking the current page against the array of links.
		 *
		 * @link http://github.com/symphonycms/symphony-2/blob/master/symphony/assets/navigation.xml
		 */
		public function __buildNavigation(){
			$nav = array();
			$xml = simplexml_load_file(ASSETS . '/navigation.xml');

			// Loop over the default Symphony navigation file, converting
			// it into an associative array representation
			foreach($xml->xpath('/navigation/group') as $n){

				$index = (string)$n->attributes()->index;
				$children = $n->xpath('children/item');
				$content = $n->attributes();

				// If the index is already set, increment the index and check again.
				// Rinse and repeat until the index is not set.
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
						$item = array(
							'link' => (string)$child->attributes()->link,
							'name' => __(strval($child->attributes()->name)),
							'visible' => ((string)$child->attributes()->visible == 'no' ? 'no' : 'yes'),
						);

						$limit = (string)$child->attributes()->limit;
						if(strlen(trim($limit)) > 0) $item['limit'] = $limit;

						$nav[$index]['children'][] = $item;
					}
				}
			}

			// Build the section navigation, grouped by their navigation groups
			$sections = Symphony::Database()->fetch("SELECT * FROM `tbl_sections` ORDER BY `sortorder` ASC");
			if(is_array($sections) && !empty($sections)){
				foreach($sections as $s){

					$group_index = self::__navigationFindGroupIndex($nav, $s['navigation_group']);

					if($group_index === false){
						$group_index = General::array_find_available_index($nav, 0);

						$nav[$group_index] = array(
							'name' => $s['navigation_group'],
							'index' => $group_index,
							'children' => array()
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

			// Loop over all the installed extensions to add in other navigation items
			$extensions = Symphony::ExtensionManager()->listInstalledHandles();
			foreach($extensions as $e){
				$info = Symphony::ExtensionManager()->about($e);

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
									'limit' => (!is_null($item['limit']) ? $item['limit'] : null)
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
										'limit' => (!is_null($child['limit']) ? $child['limit'] : null)
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
									$group_index = self::__navigationFindGroupIndex($nav, $item['location']);
								} else {
									// is a legacy numeric index
									$group_index = $item['location'];
								}

								$child = array(
									'link' => $link,
									'name' => $item['name'],
									'visible' => ($item['visible'] == 'no' ? 'no' : 'yes'),
									'limit' => (!is_null($item['limit']) ? $item['limit'] : null)
								);

								if ($group_index === false) {
									$group_index = General::array_find_available_index($nav, 0);
									// add new navigation group
									$nav[$group_index] = array(
										'name' => $group_name,
										'index' => $group_index,
										'children' => array($child),
										'limit' => (!is_null($item['limit']) ? $item['limit'] : null)
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

			/**
			 * After building the Navigation properties array. This is specifically
			 * for extensions to add their groups to the navigation or items to groups,
			 * already in the navigation. Note: THIS IS FOR ADDING ONLY! If you need
			 * to edit existing navigation elements, use the `NavigationPreRender` delegate.
			 *
			 * @delegate ExtensionsAddToNavigation
			 * @param string $context
			 * '/backend/'
			 * @param array $navigation
			 */
			Symphony::ExtensionManager()->notifyMembers(
				'ExtensionsAddToNavigation', '/backend/', array('navigation' => &$nav)
			);

			$pageCallback = Administration::instance()->getPageCallback();

			$pageRoot = $pageCallback['pageroot'] . (isset($pageCallback['context'][0]) ? $pageCallback['context'][0] . '/' : '');
			$found = self::__findActiveNavigationGroup($nav, $pageRoot);

			// Normal searches failed. Use a regular expression using the page root. This is less
			// efficient and should never really get invoked unless something weird is going on
			if(!$found) self::__findActiveNavigationGroup($nav, '/^' . str_replace('/', '\/', $pageCallback['pageroot']) . '/i', true);

			ksort($nav);
			$this->_navigation = $nav;
		}

		/**
		 * Given an associative array representing the navigation, and a group,
		 * this function will attempt to return the index of the group in the navigation
		 * array. If it is found, it will return the index, otherwise it will return false.
		 *
		 * @param array $nav
		 *  An associative array of the navigation where the key is the group
		 *  index, and the value is an associative array of 'name', 'index' and
		 *  'children'. Name is the name of the this group, index is the same as
		 *  the key and children is an associative array of navigation items containing
		 *  the keys 'link', 'name' and 'visible'. The 'haystack'.
		 * @param string $group
		 *  The group name to find, the 'needle'.
		 * @return integer|boolean
		 *  If the group is found, the index will be returned, otherwise false.
		 */
		private static function __navigationFindGroupIndex(Array $nav, $group){
			foreach($nav as $index => $item){
				if($item['name'] == $group) return $index;
			}
			return false;
		}

		/**
		 * Given the navigation array, this function will loop over all the items
		 * to determine which is the 'active' navigation group, or in other words,
		 * what group best represents the current page `$this->Author` is viewing.
		 * This is done by checking the current page's link against all the links
		 * provided in the `$nav`, and then flagging the group of the found link
		 * with an 'active' CSS class. The current page's link omits any flags or
		 * URL parameters and just uses the root page URL.
		 *
		 * @param array $nav
		 *  An associative array of the navigation where the key is the group
		 *  index, and the value is an associative array of 'name', 'index' and
		 *  'children'. Name is the name of the this group, index is the same as
		 *  the key and children is an associative array of navigation items containing
		 *  the keys 'link', 'name' and 'visible'. The 'haystack'. This parameter is passed
		 *  by reference to this function.
		 * @param string $pageroot
		 *  The current page the Author is the viewing, minus any flags or URL
		 *  parameters such as a Symphony object ID. eg. Section ID, Entry ID. This
		 *  parameter is also be a regex, but this is highly unlikely.
		 * @param boolean $pattern
		 *  If set to true, the `$pageroot` represents a regex, and preg_match is
		 *  invoked to determine the active navigation item. Defaults to false
		 * @return boolean
		 *  Returns true if an active link was found, false otherwise. If true, the
		 *  navigation group of the active link will be given the CSS class 'active'
		 */
		private static function __findActiveNavigationGroup(Array &$nav, $pageroot, $pattern=false){
			foreach($nav as $index => $contents){
				if(is_array($contents['children']) && !empty($contents['children'])){
					foreach($contents['children'] as $item) {
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

		/**
		 * Creates the Symphony footer for an Administration page. By default
		 * this includes the installed Symphony version and the currently logged
		 * in Author. A delegate is provided to allow extensions to manipulate the
		 * footer HTML, which is an XMLElement of a `<ul>` element.
		 *
		 * @uses AddElementToFooter
		 */
		public function appendFooter(){

			$version = new XMLElement('p', 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'), array('id' => 'version'));
			$this->Footer->appendChild($version);

			$ul = new XMLElement('ul');
			$ul->setAttribute('id', 'usr');

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor(Administration::instance()->Author->getFullName(), SYMPHONY_URL . '/system/authors/edit/' . Administration::instance()->Author->get('id') . '/'));
			$ul->appendChild($li);

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor(__('Logout'), SYMPHONY_URL . '/logout/', NULL, NULL, NULL, array('accesskey' => 'l')));

			$ul->appendChild($li);

			/**
			 * Add new list elements to the footer
			 *
			 * @delegate AddElementToFooter
			 * @param string $context
			 *  '/backend/'
			 * @param XMLElement $wrapper
			 *  A XMLElement representing the `<ul>` at in the Symphony footer, passed by reference
			 */
			Symphony::ExtensionManager()->notifyMembers('AddElementToFooter', '/backend/', array('wrapper' => &$ul));

			$this->Footer->appendChild($ul);
		}

		/**
		 * Returns all the page types that exist in this Symphony install.
		 * There are 5 default system page types, and new types can be added
		 * by Developers via the Page Editor.
		 *
		 * @return array
		 *  An array of strings of the page types used in this Symphony
		 *  install. At the minimum, this will be an array with the values
		 * 'index', 'XML', 'admin', '404' and '403'.
		 */
		public function __fetchAvailablePageTypes(){
			$system_types = array('index', 'XML', 'admin', '404', '403');

			if(!$types = Symphony::Database()->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` ORDER BY `type` ASC")) return $system_types;

			return (is_array($types) && !empty($types) ? General::array_remove_duplicates(array_merge($system_types, $types)) : $system_types);
		}

		/**
		 * Will wrap a `<div>` around a desired element to trigger the default
		 * Symphony error styling.
		 *
		 * @deprecated This function is deprecated and will be removed in the next
		 *  version of Symphony. This preferred way to wrap an element with an
		 *  error is using `Widget::wrapFormElementWithError`
		 * @see toolkit.Widget::wrapFormElementWithError()
		 * @param XMLElement $element
		 *	The element that should be wrapped with an error
		 * @param string $message
		 *	The text for this error. This will be appended after the `$element`,
		 *  but inside the wrapping `<div>`
		 */
		public function wrapFormElementWithError($element, $message = null){
			return Widget::wrapFormElementWithError($element, $message);
		}

	}
