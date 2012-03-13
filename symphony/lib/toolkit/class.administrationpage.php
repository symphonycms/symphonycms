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

	require_once(TOOLKIT . '/class.pagemanager.php');
	require_once(TOOLKIT . '/class.htmlpage.php');
	require_once(TOOLKIT . '/class.alert.php');

	Class AdministrationPage extends HTMLPage {

		/**
		 * An array of `Alert` objects used to display page level
		 * messages to Symphony backend users one by one. Prior to Symphony 2.3
		 * this variable only held a single `Alert` object.
		 * @var array
		 */
		public $Alert = array();

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
		 * A `<div>` that contains the breadcrumbs, the page title and some contextual
		 * actions (e.g. "Create new").
		 * @since Symphony 2.3
		 * @var XMLElement
		 */
		public $Context = null;

		/**
		 * An object that stores the markup for the breadcrumbs and is only used
		 * internally.
		 * @since Symphony 2.3
		 * @var XMLElement
		 */
		private $Breadcrumbs = null;

		/**
		 * An array of Drawer widgets for the current page
		 * @since Symphony 2.3
		 * @var array
		 */
		public $Drawer = array();

		/**
		 * A `<div>` that contains the content of a Symphony backend page.
		 * @var XMLElement
		 */
		public $Contents = null;

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
		 *  often provided in delegates so extensions can manipulate based
		 *  off the current context or add new keys.
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
		 * Constructor calls the parent constructor to set up
		 * the basic HTML, Head and Body `XMLElement`'s. This function
		 * also sets the `XMLElement` element style to be HTML, instead of XML
		 */
		public function __construct(){
			parent::__construct();

			$this->Html->setElementStyle('html');
		}

		/**
		 * Specifies the type of page that being created. This is used to
		 * trigger various styling hooks. If your page is mainly a form,
		 * pass 'form' as the parameter, if it's displaying a single entry,
		 * pass 'single'. If any other parameter is passed, the 'index'
		 * styling will be applied.
		 *
		 * @param string $type
		 *  Accepts 'form' or 'single', any other `$type` will trigger 'index'
		 *  styling.
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
			// Prevents duplicate "index" classes
			if (!isset($this->_context['page']) || $this->_context['page'] != 'index' || $class != 'index') {
				$this->_body_class .= $class;
			}
		}

		/**
		 * Accessor for `$this->_context` which includes contextual information
		 * about the current page such as the class, file location or page root.
		 * This information varies depending on if the page is provided by an
		 * extension, is for the publish area, is the login page or any other page
		 *
		 * @since Symphony 2.3
		 * @return array
		 */
		public function getContext() {
			return $this->_context;
		}

		/**
		 * Given a `$message` and an optional `$type`, this function will
		 * add an Alert instance into this page's `$this->Alert` property.
		 * Since Symphony 2.3, there may be more than one `Alert` per page.
 		 * Unless the Alert is an Error, it is required the `$message` be
		 * passed to this function.
		 *
		 * @param string $message
		 *  The message to display to users
		 * @param string $type
		 *  An Alert constant, being `Alert::NOTICE`, `Alert::ERROR` or
		 *  `Alert::SUCCESS`. The differing types will show the error
		 *  in a different style in the backend. If omitted, this defaults
		 *  to `Alert::NOTICE`.
		 */
		public function pageAlert($message = null, $type = Alert::NOTICE){
			if(is_null($message) && $type == Alert::ERROR){
				$message = 'There was a problem rendering this page. Please check the activity log for more details.';
			}

			$message = __($message);

			if(strlen(trim($message)) == 0) throw new Exception('A message must be supplied unless the alert is of type Alert::ERROR');

			$this->Alert[] = new Alert($message, $type);
		}

		/**
		 * Appends the heading of this Symphony page to the Context element.
		 * Action buttons can be provided (e.g. "Create new") as second parameter.
		 *
		 * @since Symphony 2.3
		 * @param string $value
		 *  The heading text
		 * @param array|XMLElement|string $actions
		 *  Some contextual actions to append to the heading, they can be provided as
		 *  an array of XMLElements or strings. Traditionally Symphony uses this to append
		 *  a "Create new" link to the Context div.
		 */
		public function appendSubheading($value, $actions = null){
			if(!is_array($actions) && $actions){ // Backward compatibility
				$actions = array($actions);
			}

			if(!empty($actions)) foreach($actions as $a){
				$this->insertAction($a);
			}

			$this->Breadcrumbs->appendChild(new XMLElement('h2', $value));
		}

		/**
		 * This function allows a user to insert an Action button to the page.
		 * It accepts an `XMLElement` (which should be of the `Anchor` type),
		 * an optional parameter `$prepend`, which when `true` will add this
		 * action before any existing actions.
		 *
		 * @since Symphony 2.3
		 * @see core.Widget#Anchor
		 * @param XMLElement $action
		 *  An Anchor element to add to the top of the page.
		 * @param boolean $append
		 *  If true, this will add the `$action` after existing actions, otherwise
		 *  it will be added before existing actions. By default this is `true`,
		 *  which will add the `$action` after current actions.
		 */
		public function insertAction(XMLElement $action, $append = true) {
			$actions = $this->Context->getChildrenByName('ul');

			// Actions haven't be added yet, create the element
			if(empty($actions)) {
				$ul = new XMLElement('ul', NULL, array('class' => 'actions'));
				$this->Context->appendChild($ul);
			}
			else {
				$ul = current($actions);
				$this->Context->replaceChildAt(1, $ul);
			}

			$li = new XMLElement('li', $action);

			if($append) {
				$ul->prependChild($li);
			}
			else {
				$ul->appendChild($li);
			}
		}

		/**
		 * Allows developers to specify a list of nav items that build the
		 * path to the current page or, in jargon, "breadcrumbs".
		 *
		 * @since Symphony 2.3
		 * @param array $values
		 *  An array of `XMLElement`'s or strings that compose the path. If breadcrumbs
		 *  already exist, any new item will be appended to the rightmost part of the
		 *  path.
		 */
		public function insertBreadcrumbs(array $values) {
			if(empty($values)) return;

			if($this->Breadcrumbs instanceof XMLELement
				&& count($this->Breadcrumbs->getChildrenByName('nav')) === 1) {
				$nav = $this->Breadcrumbs->getChildrenByName('nav');
				$nav = $nav[0];

				$p = $nav->getChild(0);
			}
			else {
				$p = new XMLElement('p');
				$nav = new XMLElement('nav');
				$nav->appendChild($p);

				$this->Breadcrumbs->prependChild($nav);
			}

			foreach($values as $v){
				$p->appendChild($v);
				$p->appendChild(new XMLElement('span', '&#8250;', array('class' => 'sep')));
			}
		}

		/**
		 * Allows a Drawer element to added to the backend page in one of three
		 * positions, `horizontal`, `vertical-left` or `vertical-right`. The button
		 * to trigger the visibility of the drawer will be added after existing
		 * actions by default.
		 *
		 * @since Symphony 2.3
		 * @see core.Widget#Drawer
		 * @param XMLElement $drawer
		 *  An XMLElement representing the drawer, use `Widget::Drawer` to construct
		 * @param string $position
		 *  Where `$position` can be `horizontal`, `vertical-left` or
		 *  `vertical-right`. Defaults to `horizontal`.
		 * @param string $button
		 *  If not passed, a button to open/close the drawer will not be added
		 *  to the interface. Accepts 'prepend' or 'append' values, which will
		 *  add the button before or after existing buttons. Defaults to `prepend`.
		 *  If any other value is passed, no button will be added.
		 */
		public function insertDrawer(XMLElement $drawer, $position = 'horizontal', $button = 'append') {
			$this->Drawer[$position][] = $drawer;

			if(in_array($button, array('prepend', 'append'))) {
				$this->insertAction(Widget::Anchor(
						$drawer->getAttribute('data-label'), '#' . $drawer->getAttribute('id'), null, 'button drawer'
					),
					($button === 'append') ? true : false
				);
			}
		}

		/**
		 * This function initialises a lot of the basic elements that make up a Symphony
		 * backend page such as the default stylesheets and scripts, the navigation and
		 * the footer. Any alerts are also appended by this function. `view()` is called to
		 * build the actual content of the page. Two delegates fire, `InitaliseAdminPageHead`
		 * and `AppendElementBelowView` to allow extensions to add elements to the `<head>` and footer.
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
			$this->addElementToHead(new XMLElement('meta', NULL, array('charset' => 'UTF-8')), 0);
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge,chrome=1')), 1);

			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.legacy.css', 'screen', 40);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.basic.css', 'screen', 41);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.grids.css', 'screen', 42);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.frames.css', 'screen', 43);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.buttons.css', 'screen', 44);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/admin.css', 'screen', 45);

			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/jquery.js', 50);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/jquery.color.js', 51);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.js', 60);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.collapsible.js', 61);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.orderable.js', 62);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.selectable.js', 63);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.duplicator.js', 64);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.tags.js', 65);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.pickable.js', 66);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.timeago.js', 67);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/symphony.notify.js', 68);
			$this->addScriptToHead(SYMPHONY_URL . '/assets/js/admin.js', 70);

			$this->addElementToHead(
				new XMLElement(
					'script',
					"Symphony.Context.add('env', " . json_encode(array_merge(
						array('page-namespace' => Symphony::getPageNamespace()),
						$this->_context
					)) . "); Symphony.Context.add('root', '" . URL . "');",
					array('type' => 'text/javascript')
				), 72
			);

			// Initialise page containers
			$this->Wrapper = new XMLElement('div', NULL, array('id' => 'wrapper'));
			$this->Header = new XMLElement('header', NULL, array('id' => 'header'));
			$this->Context = new XMLElement('div', NULL, array('id' => 'context'));
			$this->Breadcrumbs = new XMLElement('div', NULL, array('id' => 'breadcrumbs'));
			$this->Contents = new XMLElement('div', NULL, array('id' => 'contents'));
			$this->Form = Widget::Form(Administration::instance()->getCurrentPageURL(), 'post');

			/**
			 * Allows developers to insert items into the page HEAD. Use `Administration::instance()->Page`
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
				Symphony::Profiler()->sample('Page action run', PROFILE_LAP);
			}

			$h1 = new XMLElement('h1');
			$h1->appendChild(Widget::Anchor(Symphony::Configuration()->get('sitename', 'general'), rtrim(URL, '/') . '/'));
			$this->Header->appendChild($h1);

			$this->appendUserLinks();
			$this->appendNavigation();

			// Add Breadcrumbs
			$this->Context->prependChild($this->Breadcrumbs);

			$this->view();

			$this->Contents->appendChild($this->Form);

			$this->appendAlert();

			Symphony::Profiler()->sample('Page content created', PROFILE_LAP);
		}

		/**
		 * Checks the current Symphony Author can access the current page.
		 * This check uses the `ASSETS . /navigation.xml` file to determine
		 * if the current page (or the current page namespace) can be viewed
		 * by the currently logged in Author.
		 *
		 * @link http://github.com/symphonycms/symphony-2/blob/master/symphony/assets/navigation.xml
		 * @return boolean
		 *  True if the Author can access the current page, false otherwise
		 */
		public function canAccessPage(){
			$nav = $this->getNavigationArray();
			$page = '/' . trim(getCurrentPage(), '/') . '/';

			$page_limit = 'author';

			foreach($nav as $item){
				if(
					// If page directly matches one of the children
					General::in_array_multi($page, $item['children'])
					// If the page namespace matches one of the children (this will usually drop query
					// string parameters such as /edit/1/)
					or General::in_array_multi(Symphony::getPageNamespace() . '/', $item['children'])
				) {
					if(is_array($item['children'])){
						foreach($item['children'] as $c) {
							if($c['link'] == $page && isset($c['limit'])) {
								$page_limit = $c['limit'];
							}
						}
					}

					if(isset($item['limit']) && $page_limit != 'primary'){
						if($page_limit == 'author' && $item['limit'] == 'developer') $page_limit = 'developer';
					}
				}

				else if(isset($item['link']) && $page == $item['link'] && isset($item['limit'])) {
					$page_limit = $item['limit'];
				}
			}

			if(
				$page_limit == 'author'
				or ($page_limit == 'developer' && Administration::instance()->Author->isDeveloper())
				or ($page_limit == 'primary' && Administration::instance()->Author->isPrimaryAccount())
			) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Appends the `$this->Header`, `$this->Context` and `$this->Contents`
		 * to `$this->Wrapper` before adding the ID and class attributes for
		 * the `<body>` element. This function will also place any Drawer elements
		 * in their relevant positions in the page. After this has completed the
		 * parent `generate()` is called which will convert the `XMLElement`'s
		 * into strings ready for output.
		 *
		 * @see core.HTMLPage#generate()
		 * @return string
		 */
		public function generate() {
			$this->Wrapper->appendChild($this->Header);

			// Add horizontal drawers (inside #context)
			if(isset($this->Drawer['horizontal'])) {
				$this->Context->appendChildArray($this->Drawer['horizontal']);
			}

			$this->Wrapper->appendChild($this->Context);

			// Add vertical-left drawers (between #context and #contents
			if(isset($this->Drawer['vertical-left'])) {
				$this->Wrapper->appendChildArray($this->Drawer['vertical-left']);
			}

			$this->Wrapper->appendChild($this->Contents);

			// Add vertical-right drawers (after #contents
			if(isset($this->Drawer['vertical-right'])) {
				$this->Wrapper->appendChildArray($this->Drawer['vertical-right']);
			}

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
			$body_class = '';

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

			$this->$function(null);
		}

		/**
		 * If `$this->Alert` is set, it will be prepended to the Form of this page.
		 * A delegate is fired here to allow extensions to provide their
		 * their own Alert messages to the page. Since Symphony 2.3, there may be
		 * more than one `Alert` for a particular page. Alerts are displayed in
		 * reverse order to what they were added, ie. the last Alert to be added will
		 * be shown first, second will the be the second last Alert and so on.
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

			foreach($this->Alert as $alert){
				$this->Header->prependChild($alert->asXML());
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

			$navElement = new XMLElement('nav', NULL, array('id' => 'nav'));
			$contentNav = new XMLElement('ul', NULL, array('class' => 'content'));
			$structureNav = new XMLElement('ul', NULL, array('class' => 'structure'));

			foreach($nav as $n){
				if(isset($n['visible']) && $n['visible'] == 'no') continue;

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
									Widget::Anchor($c['name'], SYMPHONY_URL . $c['link'])
								);

								$xChildren->appendChild($xChild);
								$hasChildren = true;
							}
						}

						if($hasChildren){
							$xGroup->appendChild($xChildren);

							if ($n['type'] === 'content')
								$contentNav->appendChild($xGroup);
							else if ($n['type'] === 'structure')
								$structureNav->appendChild($xGroup);
						}
					}
				}
			}

			$navElement->appendChild($contentNav);
			$navElement->appendChild($structureNav);
			$this->Header->appendChild($navElement);
			Symphony::Profiler()->sample('Navigation Built', PROFILE_LAP);
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
					'type' => 'structure',
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
							'type' => 'content',
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
			foreach($extensions as $e) {
				$extension = Symphony::ExtensionManager()->getInstance($e);
				$extension_navigation = $extension->fetchNavigation();

				if(is_array($extension_navigation) && !empty($extension_navigation)){
					foreach($extension_navigation as $item){

						$type = isset($item['children']) ? Extension::NAV_GROUP : Extension::NAV_CHILD;

						switch($type){
							case Extension::NAV_GROUP:

								$index = General::array_find_available_index($nav, $item['location']);

								$nav[$index] = array(
									'name' => $item['name'],
									'type' => isset($item['type']) ? $item['type'] : 'structure',
									'index' => $index,
									'children' => array(),
									'limit' => isset($item['limit']) ? $item['limit'] : null
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
										'visible' => (isset($child['visible']) && $child['visible'] == 'no') ? 'no' : 'yes',
										'limit' => isset($child['limit']) ? $child['limit'] : null
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
									'visible' => (isset($item['visible']) && $item['visible'] == 'no') ? 'no' : 'yes',
									'limit' => isset($item['limit']) ? $item['limit'] : null
								);

								if ($group_index === false) {
									$group_index = General::array_find_available_index($nav, 0);
									// add new navigation group
									$nav[$group_index] = array(
										'name' => $group_name,
										'index' => $group_index,
										'children' => array($child),
										'limit' => isset($item['limit']) ? $item['limit'] : null
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
			 * @deprecated This delegate is deprecated and will be removed in the next
			 *  major release of Symphony. Extensions are encouraged to use provide the
			 *  `fetchNavigation` method instead.
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
		 * Since Symphony 2.3, it no longer uses the `AddElementToFooter` delegate.
		 */
		public function appendUserLinks(){
			$ul = new XMLElement('ul', NULL, array('id' => 'session'));

			$li = new XMLElement('li');
			$li->appendChild(
				Widget::Anchor(
					Administration::instance()->Author->getFullName(),
					SYMPHONY_URL . '/system/authors/edit/' . Administration::instance()->Author->get('id') . '/',
					null,
					null,
					null,
					array(
						'data-id' => Administration::instance()->Author->get('id'),
						'data-name' => Administration::instance()->Author->get('first_name'),
						'data-type' => Administration::instance()->Author->get('user_type')
					)
				)
			);
			$ul->appendChild($li);

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor(__('Logout'), SYMPHONY_URL . '/logout/', NULL, NULL, NULL, array('accesskey' => 'l')));
			$ul->appendChild($li);

			$this->Header->appendChild($ul);
		}

		/**
		 * Returns all the page types that exist in this Symphony install.
		 * There are 5 default system page types, and new types can be added
		 * by Developers via the Page Editor.
		 *
		 * @deprecated This function will be removed in Symphony 2.4.
		 *  The preferred way to access the page types is via
		 *  `PageManager::fetchAvailablePageTypes()`
		 * @see toolkit.PageManager#fetchAvailablePageTypes
		 * @return array
		 *  An array of strings of the page types used in this Symphony
		 *  install. At the minimum, this will be an array with the values
		 * 'index', 'XML', 'admin', '404' and '403'.
		 */
		public function __fetchAvailablePageTypes(){
			return PageManager::fetchAvailablePageTypes();
		}

	}
