<?php

/**
 * @package toolkit
 */

/**
 * The `FrontendPage` class represents a page of the website that is powered
 * by Symphony. It takes the current URL and resolves it to a page as specified
 * in Symphony which involves deducing the parameters from the URL, ensuring
 * this page is accessible and exists, setting the correct Content-Type for the page
 * and executing any Datasources or Events attached to the page to generate a
 * string of HTML that is returned to the browser. If the resolved page does not exist
 * or the user is not allowed to view it, the appropriate 404/403 page will be shown
 * instead.
 */

class FrontendPage extends XSLTPage
{
    /**
     * An associative array of all the parameters for this page including
     * Symphony parameters, URL Parameters, DS Parameters and Page
     * parameters
     * @var array
     */
    public $_param = array();

    /**
     * The URL of the current page that is being Rendered as returned
     * by `getCurrentPage`
     *
     * @var string
     * @see boot#getCurrentPage()
     */
    private $_page;

    /**
     * An associative array of the resolved pages's data as returned from `tbl_pages`
     * with the keys mapping to the columns in that table. Additionally, 'file-location'
     * and 'type' are also added to this array
     *
     * @var array
     */
    private $_pageData;

    /**
     * Returns whether the user accessing this page is logged in as a Symphony
     * Author
     *
     * @since Symphony 2.2.1
     * @var boolean
     */
    private $is_logged_in = false;

    /**
     * When events are processed, the results of them often can't be reproduced
     * when debugging the page as they happen during `$_POST`. There is a Symphony
     * configuration setting that allows the event results to be appended as a HTML
     * comment at the bottom of the page source, so logged in Authors can view-source
     * page to see the result of an event. This variable holds the event XML so that it
     * can be appended to the page source if `display_event_xml_in_source` is set to 'yes'.
     * By default this is set to no.
     *
     * @var XMLElement
     */
    private $_events_xml;

    /**
     * Holds all the environment variables which include parameters set by
     * other Datasources or Events.
     * @var array
     */
    private $_env = array();

    /**
     * Constructor function sets the `$is_logged_in` variable.
     */
    public function __construct()
    {
        parent::__construct();

        $this->is_logged_in = Frontend::instance()->isLoggedIn();
    }

    /**
     * Accessor function for the environment variables, aka `$this->_env`
     *
     * @return array
     */
    public function Env()
    {
        return $this->_env;
    }

    /**
     * Setter function for `$this->_env`, which takes an associative array
     * of environment information and replaces the existing `$this->_env`.
     *
     * @since Symphony 2.3
     * @param array $env
     *  An associative array of new environment values
     */
    public function setEnv(array $env = array())
    {
        $this->_env = $env;
    }

    /**
     * Accessor function for the resolved page's data (`$this->_pageData`)
     * as it lies in `tbl_pages`
     *
     * @return array
     */
    public function pageData()
    {
        return $this->_pageData;
    }

    /**
     * Accessor function for this current page URL, `$this->_page`
     *
     * @return string
     */
    public function Page()
    {
        return $this->_page;
    }

    /**
     * Accessor function for the current page params, `$this->_param`
     *
     * @since Symphony 2.3
     * @return array
     */
    public function Params()
    {
        return $this->_param;
    }

    /**
     * This function is called immediately from the Frontend class passing the current
     * URL for generation. Generate will resolve the URL to the specific page in the Symphony
     * and then execute all events and datasources registered to this page so that it can
     * be rendered. A number of delegates are fired during stages of execution for extensions
     * to hook into.
     *
     * @uses FrontendDevKitResolve
     * @uses FrontendOutputPreGenerate
     * @uses FrontendPreRenderHeaders
     * @uses FrontendOutputPostGenerate
     * @see __buildPage()
     * @param string $page
     * The URL of the current page that is being Rendered as returned by getCurrentPage
     * @throws Exception
     * @throws FrontendPageNotFoundException
     * @throws SymphonyErrorPage
     * @return string
     * The page source after the XSLT has transformed this page's XML. This would be
     * exactly the same as the 'view-source' from your browser
     */
    public function generate($page = null)
    {
        $full_generate = true;
        $devkit = null;
        $output = null;

        $this->addHeaderToPage('Cache-Control', 'no-cache, must-revalidate, max-age=0');
        $this->addHeaderToPage('Expires', 'Mon, 12 Dec 1982 06:14:00 GMT');
        $this->addHeaderToPage('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $this->addHeaderToPage('Pragma', 'no-cache');

        if ($this->is_logged_in) {
            /**
             * Allows a devkit object to be specified, and stop continued execution:
             *
             * @delegate FrontendDevKitResolve
             * @param string $context
             * '/frontend/'
             * @param boolean $full_generate
             *  Whether this page will be completely generated (ie. invoke the XSLT transform)
             *  or not, by default this is true. Passed by reference
             * @param mixed $devkit
             *  Allows a devkit to register to this page
             */
            Symphony::ExtensionManager()->notifyMembers('FrontendDevKitResolve', '/frontend/', array(
                'full_generate' => &$full_generate,
                'devkit'        => &$devkit
            ));
        }

        Symphony::Profiler()->sample('Page creation started');
        $this->_page = $page;
        $this->__buildPage();

        if ($full_generate) {
            /**
             * Immediately before generating the page. Provided with the page object, XML and XSLT
             * @delegate FrontendOutputPreGenerate
             * @param string $context
             * '/frontend/'
             * @param FrontendPage $page
             *  This FrontendPage object, by reference
             * @param XMLElement $xml
             *  This pages XML, including the Parameters, Datasource and Event XML, by reference as
             *  an XMLElement
             * @param string $xsl
             *  This pages XSLT, by reference
             */
            Symphony::ExtensionManager()->notifyMembers('FrontendOutputPreGenerate', '/frontend/', array(
                'page'  => &$this,
                'xml'   => &$this->_xml,
                'xsl'   => &$this->_xsl
            ));

            if (is_null($devkit)) {
                if(General::in_iarray('XML', $this->_pageData['type'])) {
                    $this->addHeaderToPage('Content-Type', 'text/xml; charset=utf-8');
                }
                else if(General::in_iarray('JSON', $this->_pageData['type'])) {
                    $this->addHeaderToPage('Content-Type', 'application/json; charset=utf-8');
                }
                else{
                    $this->addHeaderToPage('Content-Type', 'text/html; charset=utf-8');
                }

                if(in_array('404', $this->_pageData['type'])){
                    $this->setHttpStatus(self::HTTP_STATUS_NOT_FOUND);
                }
                else if(in_array('403', $this->_pageData['type'])){
                    $this->setHttpStatus(self::HTTP_STATUS_FORBIDDEN);
                }
            }

            /**
             * This is just prior to the page headers being rendered, and is suitable for changing them
             * @delegate FrontendPreRenderHeaders
             * @param string $context
             * '/frontend/'
             */
            Symphony::ExtensionManager()->notifyMembers('FrontendPreRenderHeaders', '/frontend/');

            // If not set by another extension, lock down the frontend
            if(!array_key_exists('x-frame-options', $this->headers())) {
                $this->addHeaderToPage('X-Frame-Options', 'SAMEORIGIN');
            }
            if(!array_key_exists('access-control-allow-origin', $this->headers())) {
                $this->addHeaderToPage('Access-Control-Allow-Origin', URL);
            }

            $backup_param = $this->_param;
            $this->_param['current-query-string'] = General::wrapInCDATA($this->_param['current-query-string']);

            // In Symphony 2.4, the XML structure stays as an object until
            // the very last moment.
            Symphony::Profiler()->seed(precision_timer());
            if($this->_xml instanceof XMLElement) {
                $this->setXML($this->_xml->generate(true, 0));
            }
            Symphony::Profiler()->sample('XML Generation', PROFILE_LAP);

            $output = parent::generate();
            $this->_param = $backup_param;

            /**
             * Immediately after generating the page. Provided with string containing page source
             * @delegate FrontendOutputPostGenerate
             * @param string $context
             * '/frontend/'
             * @param string $output
             *  The generated output of this page, ie. a string of HTML, passed by reference
             */
            Symphony::ExtensionManager()->notifyMembers('FrontendOutputPostGenerate', '/frontend/', array('output' => &$output));

            Symphony::Profiler()->sample('XSLT Transformation', PROFILE_LAP);

            if (is_null($devkit) && !$output) {
                $errstr = null;

                while (list($key, $val) = $this->Proc->getError()) {
                    $errstr .= 'Line: ' . $val['line'] . ' - ' . $val['message'] . PHP_EOL;
                }

                Frontend::instance()->throwCustomError(
                    trim($errstr),
                    __('XSLT Processing Error'),
                    Page::HTTP_STATUS_ERROR,
                    'xslt',
                    array('proc' => clone $this->Proc)
                );
            }

            Symphony::Profiler()->sample('Page creation complete');
        }

        if (!is_null($devkit)) {
            $devkit->prepare($this, $this->_pageData, $this->_xml, $this->_param, $output);

            return $devkit->build();
        }

        // Display the Event Results in the page source if the user is logged
        // into Symphony, the page is not JSON and if it is enabled in the
        // configuration.
        if ($this->is_logged_in && !General::in_iarray('JSON', $this->_pageData['type']) && Symphony::Configuration()->get('display_event_xml_in_source', 'public') === 'yes') {
            $output .= PHP_EOL . '<!-- ' . PHP_EOL . $this->_events_xml->generate(true) . ' -->';
        }

        return $output;
    }

    /**
     * This function sets the page's parameters, processes the Datasources and
     * Events and sets the `$xml` and `$xsl` variables. This functions resolves the `$page`
     * by calling the `resolvePage()` function. If a page is not found, it attempts
     * to locate the Symphony 404 page set in the backend otherwise it throws
     * the default Symphony 404 page. If the page is found, the page's XSL utility
     * is found, and the system parameters are set, including any URL parameters,
     * params from the Symphony cookies. Events and Datasources are executed and
     * any parameters  generated by them are appended to the existing parameters
     * before setting the Page's XML and XSL variables are set to the be the
     * generated XML (from the Datasources and Events) and the XSLT (from the
     * file attached to this Page)
     *
     * @uses FrontendPageResolved
     * @uses FrontendParamsResolve
     * @uses FrontendParamsPostResolve
     * @see resolvePage()
     */
    private function __buildPage()
    {
        $start = precision_timer();

        if (!$page = $this->resolvePage()) {
            throw new FrontendPageNotFoundException;
        }

        /**
         * Just after having resolved the page, but prior to any commencement of output creation
         * @delegate FrontendPageResolved
         * @param string $context
         * '/frontend/'
         * @param FrontendPage $page
         *  An instance of this class, passed by reference
         * @param array $page_data
         *  An associative array of page data, which is a combination from `tbl_pages` and
         *  the path of the page on the filesystem. Passed by reference
         */
        Symphony::ExtensionManager()->notifyMembers('FrontendPageResolved', '/frontend/', array('page' => &$this, 'page_data' => &$page));

        $this->_pageData = $page;
        $path = explode('/', $page['path']);
        $root_page = is_array($path) ? array_shift($path) : $path;
        $current_path = explode(dirname($_SERVER['SCRIPT_NAME']), $_SERVER['REQUEST_URI'], 2);
        $current_path = '/' . ltrim(end($current_path), '/');
        $split_path = explode('?', $current_path, 3);
        $current_path = rtrim(current($split_path), '/');
        $querystring = next($split_path);

        // Get max upload size from php and symphony config then choose the smallest
        $upload_size_php = ini_size_to_bytes(ini_get('upload_max_filesize'));
        $upload_size_sym = Symphony::Configuration()->get('max_upload_size', 'admin');
        $date = new DateTime();

        $this->_param = array(
            'today' => $date->format('Y-m-d'),
            'current-time' => $date->format('H:i'),
            'this-year' => $date->format('Y'),
            'this-month' => $date->format('m'),
            'this-day' => $date->format('d'),
            'timezone' => $date->format('P'),
            'website-name' => Symphony::Configuration()->get('sitename', 'general'),
            'page-title' => $page['title'],
            'root' => URL,
            'workspace' => URL . '/workspace',
            'http-host' => HTTP_HOST,
            'root-page' => ($root_page ? $root_page : $page['handle']),
            'current-page' => $page['handle'],
            'current-page-id' => $page['id'],
            'current-path' => ($current_path == '') ? '/' : $current_path,
            'parent-path' => '/' . $page['path'],
            'current-query-string' => self::sanitizeParameter($querystring),
            'current-url' => URL . $current_path,
            'upload-limit' => min($upload_size_php, $upload_size_sym),
            'symphony-version' => Symphony::Configuration()->get('version', 'symphony'),
        );

        if (isset($this->_env['url']) && is_array($this->_env['url'])) {
            foreach ($this->_env['url'] as $key => $val) {
                $this->_param[$key] = $val;
            }
        }

        if (is_array($_GET) && !empty($_GET)) {
            foreach ($_GET as $key => $val) {
                if (in_array($key, array('symphony-page', 'debug', 'profile'))) {
                    continue;
                }

                // If the browser sends encoded entities for &, ie. a=1&amp;b=2
                // this causes the $_GET to output they key as amp;b, which results in
                // $url-amp;b. This pattern will remove amp; allow the correct param
                // to be used, $url-b
                $key = preg_replace('/(^amp;|\/)/', null, $key);

                // If the key gets replaced out then it will break the XML so prevent
                // the parameter being set.
                $key = General::createHandle($key);
                if (!$key) {
                    continue;
                }

                // Handle ?foo[bar]=hi as well as straight ?foo=hi RE: #1348
                if (is_array($val)) {
                    $val = General::array_map_recursive(array('FrontendPage', 'sanitizeParameter'), $val);
                } else {
                    $val = self::sanitizeParameter($val);
                }

                $this->_param['url-' . $key] = $val;
            }
        }

        if (is_array($_COOKIE[__SYM_COOKIE_PREFIX__]) && !empty($_COOKIE[__SYM_COOKIE_PREFIX__])) {
            foreach ($_COOKIE[__SYM_COOKIE_PREFIX__] as $key => $val) {
                if ($key === 'xsrf-token' && is_array($val)) {
                    $val = key($val);
                }

                $this->_param['cookie-' . $key] = $val;
            }
        }

        // Flatten parameters:
        General::flattenArray($this->_param);

        // Add Page Types to parameters so they are not flattened too early
        $this->_param['page-types'] = $page['type'];

        // Add Page events the same way
        $this->_param['page-events'] = explode(',', trim(str_replace('_', '-', $page['events']), ','));

        /**
         * Just after having resolved the page params, but prior to any commencement of output creation
         * @delegate FrontendParamsResolve
         * @param string $context
         * '/frontend/'
         * @param array $params
         *  An associative array of this page's parameters
         */
        Symphony::ExtensionManager()->notifyMembers('FrontendParamsResolve', '/frontend/', array('params' => &$this->_param));

        $xml_build_start = precision_timer();

        $xml = new XMLElement('data');
        $xml->setIncludeHeader(true);

        $events = new XMLElement('events');
        $this->processEvents($page['events'], $events);
        $xml->appendChild($events);

        $this->_events_xml = clone $events;

        $this->processDatasources($page['data_sources'], $xml);

        Symphony::Profiler()->seed($xml_build_start);
        Symphony::Profiler()->sample('XML Built', PROFILE_LAP);

        if (isset($this->_env['pool']) && is_array($this->_env['pool']) && !empty($this->_env['pool'])) {
            foreach ($this->_env['pool'] as $handle => $p) {
                if (!is_array($p)) {
                    $p = array($p);
                }

                foreach ($p as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        foreach ($value as $kk => $vv) {
                            $this->_param[$handle] .= @implode(', ', $vv) . ',';
                        }
                    } else {
                        $this->_param[$handle] = @implode(', ', $p);
                    }
                }

                $this->_param[$handle] = trim($this->_param[$handle], ',');
            }
        }

        /**
         * Access to the resolved param pool, including additional parameters provided by Data Source outputs
         * @delegate FrontendParamsPostResolve
         * @param string $context
         * '/frontend/'
         * @param array $params
         *  An associative array of this page's parameters
         */
        Symphony::ExtensionManager()->notifyMembers('FrontendParamsPostResolve', '/frontend/', array('params' => &$this->_param));

        $params = new XMLElement('params');
        foreach ($this->_param as $key => $value) {
            // To support multiple parameters using the 'datasource.field'
            // we will pop off the field handle prior to sanitizing the
            // key. This is because of a limitation where General::createHandle
            // will strip '.' as it's technically punctuation.
            if (strpos($key, '.') !== false) {
                $parts = explode('.', $key);
                $field_handle = '.' . array_pop($parts);
                $key = implode('', $parts);
            } else {
                $field_handle = '';
            }

            $key = Lang::createHandle($key) . $field_handle;
            $param = new XMLElement($key);

            // DS output params get flattened to a string, so get the original pre-flattened array
            if (isset($this->_env['pool'][$key])) {
                $value = $this->_env['pool'][$key];
            }

            if (is_array($value) && !(count($value) == 1 && empty($value[0]))) {
                foreach ($value as $key => $value) {
                    $item = new XMLElement('item', General::sanitize($value));
                    $item->setAttribute('handle', Lang::createHandle($value));
                    $param->appendChild($item);
                }
            } elseif (is_array($value)) {
                $param->setValue(General::sanitize($value[0]));
            } elseif (in_array($key, array('xsrf-token','current-query-string'))) {
                $param->setValue(General::wrapInCDATA($value));
            } else {
                $param->setValue(General::sanitize($value));
            }

            $params->appendChild($param);
        }
        $xml->prependChild($params);

        $this->setXML($xml);
        $xsl = '<?xml version="1.0" encoding="UTF-8"?>' .
               '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' .
               '    <xsl:import href="/' . rawurlencode(ltrim($page['filelocation'], '/')) . '"/>' .
               '</xsl:stylesheet>';

        $this->setXSL($xsl, false);
        $this->setRuntimeParam($this->_param);

        Symphony::Profiler()->seed($start);
        Symphony::Profiler()->sample('Page Built', PROFILE_LAP);
    }

    /**
     * This function attempts to resolve the given page in to it's Symphony page. If no
     * page is given, it is assumed the 'index' is being requested. Before a page row is
     * returned, it is checked to see that if it has the 'admin' type, that the requesting
     * user is authenticated as a Symphony author. If they are not, the Symphony 403
     * page is returned (whether that be set as a user defined page using the page type
     * of 403, or just returning the Default Symphony 403 error page). Any URL parameters
     * set on the page are added to the `$env` variable before the function returns an
     * associative array of page details such as Title, Content Type etc.
     *
     * @uses FrontendPrePageResolve
     * @see __isSchemaValid()
     * @param string $page
     * The URL of the current page that is being Rendered as returned by `getCurrentPage()`.
     * If no URL is provided, Symphony assumes the Page with the type 'index' is being
     * requested.
     * @throws SymphonyErrorPage
     * @return array
     *  An associative array of page details
     */
    public function resolvePage($page = null)
    {
        if ($page) {
            $this->_page = $page;
        }

        $row = null;
        /**
         * Before page resolve. Allows manipulation of page without redirection
         * @delegate FrontendPrePageResolve
         * @param string $context
         * '/frontend/'
         * @param mixed $row
         * @param FrontendPage $page
         *  An instance of this FrontendPage
         */
        Symphony::ExtensionManager()->notifyMembers('FrontendPrePageResolve', '/frontend/', array('row' => &$row, 'page' => &$this->_page));

        // Default to the index page if no page has been specified
        if ((!$this->_page || $this->_page == '//') && is_null($row)) {
            $row = PageManager::fetchPageByType('index');

            // Not the index page (or at least not on first impression)
        } elseif (is_null($row)) {
            $page_extra_bits = array();
            $pathArr = preg_split('/\//', trim($this->_page, '/'), -1, PREG_SPLIT_NO_EMPTY);
            $handle = array_pop($pathArr);

            do {
                $path = implode('/', $pathArr);

                if ($row = PageManager::resolvePageByPath($handle, $path)) {
                    $pathArr[] = $handle;

                    break 1;
                } else {
                    $page_extra_bits[] = $handle;
                }
            } while (($handle = array_pop($pathArr)) !== null);

            // If the `$pathArr` is empty, that means a page hasn't resolved for
            // the given `$page`, however in some cases the index page may allow
            // parameters, so we'll check.
            if (empty($pathArr)) {
                // If the index page does not handle parameters, then return false
                // (which will give up the 404), otherwise treat the `$page` as
                // parameters of the index. RE: #1351
                $index = PageManager::fetchPageByType('index');

                if (!$this->__isSchemaValid($index['params'], $page_extra_bits)) {
                    return false;
                } else {
                    $row = $index;
                }

                // Page resolved, check the schema (are the parameters valid?)
            } elseif (!$this->__isSchemaValid($row['params'], $page_extra_bits)) {
                return false;
            }
        }

        // Nothing resolved, bail now
        if (!is_array($row) || empty($row)) {
            return false;
        }

        // Process the extra URL params
        $url_params = preg_split('/\//', $row['params'], -1, PREG_SPLIT_NO_EMPTY);

        foreach ($url_params as $var) {
            $this->_env['url'][$var] = null;
        }

        if (isset($page_extra_bits)) {
            if (!empty($page_extra_bits)) {
                $page_extra_bits = array_reverse($page_extra_bits);
            }

            for ($i = 0, $ii = count($page_extra_bits); $i < $ii; $i++) {
                $this->_env['url'][$url_params[$i]] = str_replace(' ', '+', $page_extra_bits[$i]);
            }
        }

        $row['type'] = PageManager::fetchPageTypes($row['id']);

        // Make sure the user has permission to access this page
        if (!$this->is_logged_in && in_array('admin', $row['type'])) {
            $row = PageManager::fetchPageByType('403');

            if (empty($row)) {
                Frontend::instance()->throwCustomError(
                    __('Please login to view this page.') . ' <a href="' . SYMPHONY_URL . '/login/">' . __('Take me to the login page') . '</a>.',
                    __('Forbidden'),
                    Page::HTTP_STATUS_FORBIDDEN
                );
            }

            $row['type'] = PageManager::fetchPageTypes($row['id']);
        }

        $row['filelocation'] = PageManager::resolvePageFileLocation($row['path'], $row['handle']);

        return $row;
    }

    /**
     * Given the allowed params for the resolved page, compare it to
     * params provided in the URL. If the number of params provided
     * is less than or equal to the number of URL parameters set for a page,
     * the Schema is considered valid, otherwise, it's considered to be false
     * a 404 page will result.
     *
     * @param string $schema
     *  The URL schema for a page, ie. article/read
     * @param array $bits
     *  The URL parameters provided from parsing the current URL. This
     *  does not include any `$_GET` or `$_POST` variables.
     * @return boolean
     *  True if the number of $schema (split by /) is less than the size
     *  of the $bits array.
     */
    private function __isSchemaValid($schema, array $bits)
    {
        $schema_arr = preg_split('/\//', $schema, -1, PREG_SPLIT_NO_EMPTY);

        return (count($schema_arr) >= count($bits));
    }

    /**
     * The processEvents function executes all Events attached to the resolved
     * page in the correct order determined by `__findEventOrder()`. The results
     * from the Events are appended to the page's XML. Events execute first,
     * before Datasources.
     *
     * @uses FrontendProcessEvents
     * @uses FrontendEventPostProcess
     * @param string $events
     *  A string of all the Events attached to this page, comma separated.
     * @param XMLElement $wrapper
     *  The XMLElement to append the Events results to. Event results are
     *  contained in a root XMLElement that is the handlised version of
     *  their name.
     * @throws Exception
     */
    private function processEvents($events, XMLElement &$wrapper)
    {
        /**
         * Manipulate the events array and event element wrapper
         * @delegate FrontendProcessEvents
         * @param string $context
         * '/frontend/'
         * @param array $env
         * @param string $events
         *  A string of all the Events attached to this page, comma separated.
         * @param XMLElement $wrapper
         *  The XMLElement to append the Events results to. Event results are
         *  contained in a root XMLElement that is the handlised version of
         *  their name.
         * @param array $page_data
         *  An associative array of page meta data
         */
        Symphony::ExtensionManager()->notifyMembers('FrontendProcessEvents', '/frontend/', array(
            'env' => $this->_env,
            'events' => &$events,
            'wrapper' => &$wrapper,
            'page_data' => $this->_pageData
        ));

        if (strlen(trim($events)) > 0) {
            $events = preg_split('/,\s*/i', $events, -1, PREG_SPLIT_NO_EMPTY);
            $events = array_map('trim', $events);

            if (!is_array($events) || empty($events)) {
                return;
            }

            $pool = array();

            foreach ($events as $handle) {
                $pool[$handle] = EventManager::create($handle, array('env' => $this->_env, 'param' => $this->_param));
            }

            uasort($pool, array($this, '__findEventOrder'));

            foreach ($pool as $handle => $event) {
                Symphony::Profiler()->seed();
                $queries = Symphony::Database()->queryCount();

                if ($xml = $event->load()) {
                    if (is_object($xml)) {
                        $wrapper->appendChild($xml);
                    } else {
                        $wrapper->setValue(
                            $wrapper->getValue() . PHP_EOL . '    ' . trim($xml)
                        );
                    }
                }

                $queries = Symphony::Database()->queryCount() - $queries;
                Symphony::Profiler()->sample($handle, PROFILE_LAP, 'Event', $queries);
            }
        }

        /**
         * Just after the page events have triggered. Provided with the XML object
         * @delegate FrontendEventPostProcess
         * @param string $context
         * '/frontend/'
         * @param XMLElement $xml
         *  The XMLElement to append the Events results to. Event results are
         *  contained in a root XMLElement that is the handlised version of
         *  their name.
         */
        Symphony::ExtensionManager()->notifyMembers('FrontendEventPostProcess', '/frontend/', array('xml' => &$wrapper));

    }

    /**
     * This function determines the correct order that events should be executed in.
     * Events are executed based off priority, with `Event::kHIGH` priority executing
     * first. If there is more than one Event of the same priority, they are then
     * executed in alphabetical order. This function is designed to be used with
     * PHP's uasort function.
     *
     * @link http://php.net/manual/en/function.uasort.php
     * @param Event $a
     * @param Event $b
     * @return integer
     */
    private function __findEventOrder($a, $b)
    {
        if ($a->priority() == $b->priority()) {
            $a = $a->about();
            $b = $b->about();

            $handles = array($a['name'], $b['name']);
            asort($handles);

            return (key($handles) == 0) ? -1 : 1;
        }
        return(($a->priority() > $b->priority()) ? -1 : 1);
    }

    /**
     * Given an array of all the Datasources for this page, sort them into the
     * correct execution order and append the Datasource results to the
     * page XML. If the Datasource provides any parameters, they will be
     * added to the `$env` pool for use by other Datasources and eventual
     * inclusion into the page parameters.
     *
     * @param string $datasources
     *  A string of Datasource's attached to this page, comma separated.
     * @param XMLElement $wrapper
     *  The XMLElement to append the Datasource results to. Datasource
     *  results are contained in a root XMLElement that is the handlised
     *  version of their name.
     * @param array $params
     *  Any params to automatically add to the `$env` pool, by default this
     *  is an empty array. It looks like Symphony does not utilise this parameter
     *  at all
     * @throws Exception
     */
    public function processDatasources($datasources, XMLElement &$wrapper, array $params = array())
    {
        if (trim($datasources) == '') {
            return;
        }

        $datasources = preg_split('/,\s*/i', $datasources, -1, PREG_SPLIT_NO_EMPTY);
        $datasources = array_map('trim', $datasources);

        if (!is_array($datasources) || empty($datasources)) {
            return;
        }

        $this->_env['pool'] = $params;
        $pool = $params;
        $dependencies = array();

        foreach ($datasources as $handle) {
            $pool[$handle] = DatasourceManager::create($handle, array(), false);
            $dependencies[$handle] = $pool[$handle]->getDependencies();
        }

        $dsOrder = $this->__findDatasourceOrder($dependencies);

        foreach ($dsOrder as $handle) {
            Symphony::Profiler()->seed();
            $queries = Symphony::Database()->queryCount();

            // default to no XML
            $xml = null;
            $ds = $pool[$handle];

            // Handle redirect on empty setting correctly RE: #1539
            try {
                $ds->processParameters(array('env' => $this->_env, 'param' => $this->_param));
            } catch (FrontendPageNotFoundException $e) {
                // Work around. This ensures the 404 page is displayed and
                // is not picked up by the default catch() statement below
                FrontendPageNotFoundExceptionHandler::render($e);
            }

            /**
             * Allows extensions to execute the data source themselves (e.g. for caching)
             * and providing their own output XML instead
             *
             * @since Symphony 2.3
             * @delegate DataSourcePreExecute
             * @param string $context
             * '/frontend/'
             * @param DataSource $datasource
             *  The Datasource object
             * @param mixed $xml
             *  The XML output of the data source. Can be an `XMLElement` or string.
             * @param array $param_pool
             *  The existing param pool including output parameters of any previous data sources
             */
            Symphony::ExtensionManager()->notifyMembers('DataSourcePreExecute', '/frontend/', array(
                'datasource' => &$ds,
                'xml' => &$xml,
                'param_pool' => &$this->_env['pool']
            ));

            // if the XML is still null, an extension has not run the data source, so run normally
            if (is_null($xml)) {
                $xml = $ds->grab($this->_env['pool']);
            }

            if ($xml) {
                /**
                 * After the datasource has executed, either by itself or via the
                 * `DataSourcePreExecute` delegate, and if the `$xml` variable is truthy,
                 * this delegate allows extensions to modify the output XML and parameter pool
                 *
                 * @since Symphony 2.3
                 * @delegate DataSourcePostExecute
                 * @param string $context
                 * '/frontend/'
                 * @param DataSource $datasource
                 *  The Datasource object
                 * @param mixed $xml
                 *  The XML output of the data source. Can be an `XMLElement` or string.
                 * @param array $param_pool
                 *  The existing param pool including output parameters of any previous data sources
                 */
                Symphony::ExtensionManager()->notifyMembers('DataSourcePostExecute', '/frontend/', array(
                    'datasource' => $ds,
                    'xml' => &$xml,
                    'param_pool' => &$this->_env['pool']
                ));

                if ($xml instanceof XMLElement) {
                    $wrapper->appendChild($xml);
                } else {
                    $wrapper->appendChild(
                        '    ' . trim($xml) . PHP_EOL
                    );
                }
            }

            $queries = Symphony::Database()->queryCount() - $queries;
            Symphony::Profiler()->sample($handle, PROFILE_LAP, 'Datasource', $queries);
            unset($ds);
        }
    }

    /**
     * The function finds the correct order Datasources need to be processed in to
     * satisfy all dependencies that parameters can resolve correctly and in time for
     * other Datasources to filter on.
     *
     * @param array $dependenciesList
     *  An associative array with the key being the Datasource handle and the values
     *  being it's dependencies.
     * @return array
     *  The sorted array of Datasources in order of how they should be executed
     */
    private function __findDatasourceOrder($dependenciesList)
    {
        if (!is_array($dependenciesList) || empty($dependenciesList)) {
            return;
        }

        foreach ($dependenciesList as $handle => $dependencies) {
            foreach ($dependencies as $i => $dependency) {
                $dependency = explode('.', $dependency);
                $dependenciesList[$handle][$i] = reset($dependency);
            }
        }

        $orderedList = array();
        $dsKeyArray = $this->__buildDatasourcePooledParamList(array_keys($dependenciesList));

        // 1. First do a cleanup of each dependency list, removing non-existant DS's and find
        //    the ones that have no dependencies, removing them from the list
        foreach ($dependenciesList as $handle => $dependencies) {
            $dependenciesList[$handle] = @array_intersect($dsKeyArray, $dependencies);

            if (empty($dependenciesList[$handle])) {
                unset($dependenciesList[$handle]);
                $orderedList[] = str_replace('_', '-', $handle);
            }
        }

        // 2. Iterate over the remaining DS's. Find if all their dependencies are
        //    in the $orderedList array. Keep iterating until all DS's are in that list
        //    or there are circular dependencies (list doesn't change between iterations
        //    of the while loop)
        do {
            $last_count = count($dependenciesList);

            foreach ($dependenciesList as $handle => $dependencies) {
                if (General::in_array_all(array_map(create_function('$a', "return str_replace('\$ds-', '', \$a);"), $dependencies), $orderedList)) {
                    $orderedList[] = str_replace('_', '-', $handle);
                    unset($dependenciesList[$handle]);
                }
            }
        } while (!empty($dependenciesList) && $last_count > count($dependenciesList));

        if (!empty($dependenciesList)) {
            $orderedList = array_merge($orderedList, array_keys($dependenciesList));
        }

        return array_map(create_function('$a', "return str_replace('-', '_', \$a);"), $orderedList);
    }

    /**
     * Given an array of datasource dependancies, this function will translate
     * each of them to be a valid datasource handle.
     *
     * @param array $datasources
     *  The datasource dependencies
     * @return array
     *  An array of the handlised datasources
     */
    private function __buildDatasourcePooledParamList($datasources)
    {
        if (!is_array($datasources) || empty($datasources)) {
            return array();
        }

        $list = array();

        foreach ($datasources as $handle) {
            $rootelement = str_replace('_', '-', $handle);
            $list[] = '$ds-' . $rootelement;
        }

        return $list;
    }

    /**
     * Given a string (expected to be a URL parameter) this function will
     * ensure it is safe to embed in an XML document.
     *
     * @since Symphony 2.3.1
     * @param string $parameter
     *  The string to sanitize for XML
     * @return string
     *  The sanitized string
     */
    public static function sanitizeParameter($parameter)
    {
        return XMLElement::stripInvalidXMLCharacters($parameter);
    }
}
