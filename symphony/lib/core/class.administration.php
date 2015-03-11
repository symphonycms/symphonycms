<?php

/**
 * @package core
 */

/**
 * The Administration class is an instance of Symphony that controls
 * all backend pages. These pages are HTMLPages are usually generated
 * using XMLElement before being rendered as HTML. These pages do not
 * use XSLT. The Administration is only accessible by logged in Authors
 */

class Administration extends Symphony
{
    /**
     * The path of the current page, ie. '/blueprints/sections/'
     * @var string
     */
    private $_currentPage  = null;

    /**
     * An associative array of the page's callback, including the keys
     * 'driver', which is a lowercase version of `$this->_currentPage`
     * with any slashes removed, 'classname', which is the name of the class
     * for this page, 'pageroot', which is the root page for the given page, (ie.
     * excluding /saved/, /created/ or any sub pages of the current page that are
     * handled using the _switchboard function.
     *
     * @see toolkit.AdministrationPage#__switchboard()
     * @var array
     */
    private $_callback = null;

    /**
     * The class representation of the current Symphony backend page,
     * which is a subclass of the `HTMLPage` class. Symphony uses a convention
     * of prefixing backend page classes with 'content'. ie. 'contentBlueprintsSections'
     * @var HTMLPage
     */
    public $Page;

    /**
     * Overrides the default Symphony constructor to add XSRF checking
     */
    protected function __construct()
    {
        parent::__construct();

        // Ensure the request is legitimate. RE: #1874
        if (self::isXSRFEnabled()) {
            XSRF::validateRequest();
        }
    }

    /**
     * This function returns an instance of the Administration
     * class. It is the only way to create a new Administration, as
     * it implements the Singleton interface
     *
     * @return Administration
     */
    public static function instance()
    {
        if (!(self::$_instance instanceof Administration)) {
            self::$_instance = new Administration;
        }

        return self::$_instance;
    }

    /**
     * Returns the current Page path, excluding the domain and Symphony path.
     *
     * @return string
     *  The path of the current page, ie. '/blueprints/sections/'
     */
    public function getCurrentPageURL()
    {
        return $this->_currentPage;
    }

    /**
     * Overrides the Symphony isLoggedIn function to allow Authors
     * to become logged into the backend when `$_REQUEST['auth-token']`
     * is present. This logs an Author in using the loginFromToken function.
     * A token may be 6 or 8 characters in length in the backend. A 6 or 16 character token
     * is used for forget password requests, whereas the 8 character token is used to login
     * an Author into the page
     *
     * @see core.Symphony#loginFromToken()
     * @return boolean
     */
    public static function isLoggedIn()
    {
        if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && in_array(strlen($_REQUEST['auth-token']), array(6, 8, 16))) {
            return parent::loginFromToken($_REQUEST['auth-token']);
        }

        return parent::isLoggedIn();
    }

    /**
     * Given the URL path of a Symphony backend page, this function will
     * attempt to resolve the URL to a Symphony content page in the backend
     * or a page provided by an extension. This function checks to ensure a user
     * is logged in, otherwise it will direct them to the login page
     *
     * @param string $page
     *  The URL path after the root of the Symphony installation, including a starting
     *  slash, such as '/login/'
     * @throws SymphonyErrorPage
     * @throws Exception
     * @return HTMLPage
     */
    private function __buildPage($page)
    {
        $is_logged_in = self::isLoggedIn();

        if (empty($page) || is_null($page)) {
            if (!$is_logged_in) {
                $page  = "/login";
            } else {
                // Will redirect an Author to their default area of the Backend
                // Integers are indicative of section's, text is treated as the path
                // to the page after `SYMPHONY_URL`
                $default_area = null;

                if (is_numeric(Symphony::Author()->get('default_area'))) {
                    $default_section = SectionManager::fetch(Symphony::Author()->get('default_area'));

                    if ($default_section instanceof Section) {
                        $section_handle = $default_section->get('handle');
                    }

                    if (!$section_handle) {
                        $all_sections = SectionManager::fetch();

                        if (!empty($all_sections)) {
                            $section_handle = $all_sections[0]->get('handle');
                        } else {
                            $section_handle = null;
                        }
                    }

                    if (!is_null($section_handle)) {
                        $default_area = "/publish/{$section_handle}/";
                    }
                } elseif (!is_null(Symphony::Author()->get('default_area'))) {
                    $default_area = preg_replace('/^' . preg_quote(SYMPHONY_URL, '/') . '/i', '', Symphony::Author()->get('default_area'));
                }

                if (is_null($default_area)) {
                    if (Symphony::Author()->isDeveloper()) {
                        $all_sections = SectionManager::fetch();
                        $section_handle = !empty($all_sections) ? $all_sections[0]->get('handle') : null;

                        if (!is_null($section_handle)) {
                            // If there are sections created, redirect to the first one (sortorder)
                            redirect(SYMPHONY_URL . "/publish/{$section_handle}/");
                        } else {
                            // If there are no sections created, default to the Section page
                            redirect(SYMPHONY_URL . '/blueprints/sections/');
                        }
                    } else {
                        redirect(SYMPHONY_URL . "/system/authors/edit/".Symphony::Author()->get('id')."/");
                    }
                } else {
                    redirect(SYMPHONY_URL . $default_area);
                }
            }
        }

        if (!$this->_callback = $this->getPageCallback($page)) {
            if ($page === '/publish/') {
                $sections = SectionManager::fetch(null, 'ASC', 'sortorder');
                $section = current($sections);
                redirect(SYMPHONY_URL . '/publish/' . $section->get('handle'));
            } else {
                $this->errorPageNotFound();
            }
        }

        include_once($this->_callback['driver_location']);
        $this->Page = new $this->_callback['classname'];

        if (!$is_logged_in && $this->_callback['driver'] !== 'login') {
            if (is_callable(array($this->Page, 'handleFailedAuthorisation'))) {
                $this->Page->handleFailedAuthorisation();
            } else {
                include_once(CONTENT . '/content.login.php');
                $this->Page = new contentLogin;

                // Include the query string for the login, RE: #2324
                if ($queryString = $this->Page->__buildQueryString(array('symphony-page', 'mode'), FILTER_SANITIZE_STRING)) {
                    $page .= '?' . $queryString;
                }
                $this->Page->build(array('redirect' => $page));
            }
        } else {
            if (!is_array($this->_callback['context'])) {
                $this->_callback['context'] = array();
            }

            if($this->__canAccessAlerts()) {
                // Can the core be updated?
                $this->checkCoreForUpdates();
                // Do any extensions need updating?
                $this->checkExtensionsForUpdates();
            }

            $this->Page->build($this->_callback['context']);
        }

        return $this->Page;
    }

    /**
     * Scan the install directory to look for new migrations that can be applied
     * to update this version of Symphony. If one if found, a new Alert is added
     * to the page.
     *
     * @since Symphony 2.5.2
     * @return boolean
     *  Returns true if there is an update available, false otherwise.
     */
    public function checkCoreForUpdates()
    {
        // Is there even an install directory to check?
        if ($this->isInstallerAvailable() === false) {
            return false;
        }

        try {
            // The updater contains a version higher than the current Symphony version.
            if ($this->isUpgradeAvailable()) {
                $message = __('An update has been found in your installation to upgrade Symphony to %s.', array($this->getMigrationVersion())) . ' <a href="' . URL . '/install/">' . __('View update.') . '</a>';

                // The updater contains a version lower than the current Symphony version.
                // The updater is the same version as the current Symphony install.
            } else {
                $message = __('Your Symphony installation is up to date, but the installer was still detected. For security reasons, it should be removed.') . ' <a href="' . URL . '/install/?action=remove">' . __('Remove installer?') . '</a>';
            }

            // Can't detect update Symphony version
        } catch (Exception $e) {
            $message = __('An update script has been found in your installation.') . ' <a href="' . URL . '/install/">' . __('View update.') . '</a>';
        }

        $this->Page->pageAlert($message, Alert::NOTICE);

        return true;
    }

    /**
     * Checks all installed extensions to see any have an outstanding update. If any do
     * an Alert will be added to the current page directing the Author to the Extension page
     *
     * @since Symphony 2.5.2
     */
    public function checkExtensionsForUpdates()
    {
        $extensions = Symphony::ExtensionManager()->listInstalledHandles();

        if (is_array($extensions) && !empty($extensions)) {
            foreach ($extensions as $name) {
                $about = Symphony::ExtensionManager()->about($name);

                if (array_key_exists('status', $about) && in_array(EXTENSION_REQUIRES_UPDATE, $about['status'])) {
                    $this->Page->pageAlert(
                        __('An extension requires updating.') . ' <a href="' . SYMPHONY_URL . '/system/extensions/">' . __('View extensions') . '</a>'
                    );
                    break;
                }
            }
        }
    }

    /**
     * This function determines whether an administrative alert can be
     * displayed on the current page. It ensures that the page exists,
     * and the user is logged in and a developer
     *
     * @since Symphony 2.2
     * @return boolean
     */
    private function __canAccessAlerts()
    {
        if ($this->Page instanceof AdministrationPage && self::isLoggedIn() && Symphony::Author()->isDeveloper()) {
            return true;
        }

        return false;
    }

    /**
     * This function resolves the string of the page to the relevant
     * backend page class. The path to the backend page is split on
     * the slashes and the resulting pieces used to determine if the page
     * is provided by an extension, is a section (index or entry creation)
     * or finally a standard Symphony content page. If no page driver can
     * be found, this function will return false.
     *
     * @uses AdminPagePostCallback
     * @param string $page
     *  The full path (including the domain) of the Symphony backend page
     * @return array|boolean
     *  If successful, this function will return an associative array that at the
     *  very least will return the page's classname, pageroot, driver, driver_location
     *  and context, otherwise this will return false.
     */
    public function getPageCallback($page = null)
    {
        if (!$page && $this->_callback) {
            return $this->_callback;
        } elseif (!$page && !$this->_callback) {
            trigger_error(__('Cannot request a page callback without first specifying the page.'));
        }

        $this->_currentPage = SYMPHONY_URL . preg_replace('/\/{2,}/', '/', $page);
        $bits = preg_split('/\//', trim($page, '/'), 3, PREG_SPLIT_NO_EMPTY);
        $callback = array(
            'driver' => null,
            'driver_location' => null,
            'context' => null,
            'classname' => null,
            'pageroot' => null
        );

        // Login page, /symphony/login/
        if ($bits[0] == 'login') {
            if (isset($bits[1], $bits[2])) {
                $context = preg_split('/\//', $bits[1] . '/' . $bits[2], -1, PREG_SPLIT_NO_EMPTY);
            } elseif (isset($bits[1])) {
                $context = preg_split('/\//', $bits[1], -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $context = array();
            }

            $callback = array(
                'driver' => 'login',
                'driver_location' => CONTENT . '/content.login.php',
                'context' => $context,
                'classname' => 'contentLogin',
                'pageroot' => '/login/'
            );

            // Extension page, /symphony/extension/{extension_name}/
        } elseif ($bits[0] == 'extension' && isset($bits[1])) {
            $extension_name = $bits[1];
            $bits = preg_split('/\//', trim($bits[2], '/'), 2, PREG_SPLIT_NO_EMPTY);

            $callback['driver'] = 'index';
            $callback['classname'] = 'contentExtension' . ucfirst($extension_name) . 'Index';
            $callback['pageroot'] = '/extension/' . $extension_name. '/';

            if (isset($bits[0])) {
                $callback['driver'] = $bits[0];
                $callback['classname'] = 'contentExtension' . ucfirst($extension_name) . ucfirst($bits[0]);
                $callback['pageroot'] .= $bits[0] . '/';
            }

            if (isset($bits[1])) {
                $callback['context'] = preg_split('/\//', $bits[1], -1, PREG_SPLIT_NO_EMPTY);
            }

            $callback['driver_location'] = EXTENSIONS . '/' . $extension_name . '/content/content.' . $callback['driver'] . '.php';

            // Publish page, /symphony/publish/{section_handle}/
        } elseif ($bits[0] == 'publish') {
            if (!isset($bits[1])) {
                return false;
            }

            $callback = array(
                'driver' => 'publish',
                'driver_location' => $callback['driver_location'] = CONTENT . '/content.publish.php',
                'context' => array(
                    'section_handle' => $bits[1],
                    'page' => null,
                    'entry_id' => null,
                    'flag' => null
                ),
                'pageroot' => '/' . $bits[0] . '/' . $bits[1] . '/',
                'classname' => 'contentPublish'
            );

            if (isset($bits[2])) {
                $extras = preg_split('/\//', $bits[2], -1, PREG_SPLIT_NO_EMPTY);
                $callback['context']['page'] = $extras[0];

                if (isset($extras[1])) {
                    $callback['context']['entry_id'] = intval($extras[1]);
                }

                if (isset($extras[2])) {
                    $callback['context']['flag'] = $extras[2];
                }
            } else {
                $callback['context']['page'] = 'index';
            }

            // Everything else
        } else {
            $callback['driver'] = ucfirst($bits[0]);
            $callback['pageroot'] = '/' . $bits[0] . '/';

            if (isset($bits[1])) {
                $callback['driver'] = $callback['driver'] . ucfirst($bits[1]);
                $callback['pageroot'] .= $bits[1] . '/';
            }

            if (isset($bits[2])) {
                $callback['context'] = preg_split('/\//', $bits[2], -1, PREG_SPLIT_NO_EMPTY);
            }

            $callback['classname'] = 'content' . $callback['driver'];
            $callback['driver'] = strtolower($callback['driver']);
            $callback['driver_location'] = CONTENT . '/content.' . $callback['driver'] . '.php';
        }

        /**
         * Immediately after determining which class will resolve the current page, this
         * delegate allows extension to modify the routing or provide additional information.
         *
         * @since Symphony 2.3.1
         * @delegate AdminPagePostCallback
         * @param string $context
         *  '/backend/'
         * @param string $page
         *  The current URL string, after the SYMPHONY_URL constant (which is `/symphony/`
         *  at the moment.
         * @param array $parts
         *  An array representation of `$page`
         * @param array $callback
         *  An associative array that contains `driver`, `pageroot`, `classname` and
         *  `context` keys. The `driver_location` is the path to the class to render this
         *  page, `driver` should be the view to render, the `classname` the name of the
         *  class, `pageroot` the rootpage, before any extra URL params and `context` can
         *  provide additional information about the page
         */
        Symphony::ExtensionManager()->notifyMembers('AdminPagePostCallback', '/backend/', array(
            'page' => $this->_currentPage,
            'parts' => $bits,
            'callback' => &$callback
        ));

        if (isset($callback['driver_location']) && !is_file($callback['driver_location'])) {
            return false;
        }

        return $callback;
    }

    /**
     * Called by index.php, this function is responsible for rendering the current
     * page on the Frontend. Two delegates are fired, AdminPagePreGenerate and
     * AdminPagePostGenerate. This function runs the Profiler for the page build
     * process.
     *
     * @uses AdminPagePreBuild
     * @uses AdminPagePreGenerate
     * @uses AdminPagePostGenerate
     * @see core.Symphony#__buildPage()
     * @see boot.getCurrentPage()
     * @param string $page
     *  The result of getCurrentPage, which returns the $_GET['symphony-page']
     *  variable.
     * @throws Exception
     * @throws SymphonyErrorPage
     * @return string
     *  The HTML of the page to return
     */
    public function display($page)
    {
        Symphony::Profiler()->sample('Page build process started');

        /**
         * Immediately before building the admin page. Provided with the page parameter
         * @delegate AdminPagePreBuild
         * @since Symphony 2.6.0
         * @param string $context
         *  '/backend/'
         * @param string $page
         *  The result of getCurrentPage, which returns the $_GET['symphony-page']
         *  variable.
         */
        Symphony::ExtensionManager()->notifyMembers('AdminPagePreBuild', '/backend/', array('page' => $page));

        $this->__buildPage($page);

        // Add XSRF token to form's in the backend
        if (self::isXSRFEnabled() && isset($this->Page->Form)) {
            $this->Page->Form->prependChild(XSRF::formToken());
        }

        /**
         * Immediately before generating the admin page. Provided with the page object
         * @delegate AdminPagePreGenerate
         * @param string $context
         *  '/backend/'
         * @param HTMLPage $oPage
         *  An instance of the current page to be rendered, this will usually be a class that
         *  extends HTMLPage. The Symphony backend uses a convention of contentPageName
         *  as the class that extends the HTMLPage
         */
        Symphony::ExtensionManager()->notifyMembers('AdminPagePreGenerate', '/backend/', array('oPage' => &$this->Page));

        $output = $this->Page->generate();

        /**
         * Immediately after generating the admin page. Provided with string containing page source
         * @delegate AdminPagePostGenerate
         * @param string $context
         *  '/backend/'
         * @param string $output
         *  The resulting backend page HTML as a string, passed by reference
         */
        Symphony::ExtensionManager()->notifyMembers('AdminPagePostGenerate', '/backend/', array('output' => &$output));

        Symphony::Profiler()->sample('Page built');

        return $output;
    }

    /**
     * If a page is not found in the Symphony backend, this function should
     * be called which will raise a customError to display the default Symphony
     * page not found template
     */
    public function errorPageNotFound()
    {
        $this->throwCustomError(
            __('The page you requested does not exist.'),
            __('Page Not Found'),
            Page::HTTP_STATUS_NOT_FOUND
        );
    }
}
