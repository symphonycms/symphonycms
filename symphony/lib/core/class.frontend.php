<?php

/**
 * @package core
 */

/**
 * The Frontend class is the renderer that is used to display FrontendPage's.
 * A `FrontendPage` is one that is setup in Symphony and it's output is generated
 * by using XML and XSLT
 */
class Frontend extends Symphony
{
    /**
     * An instance of the FrontendPage class
     * @var FrontendPage
     */
    private static $_page;

    /**
     * This function returns an instance of the Frontend
     * class. It is the only way to create a new Frontend, as
     * it implements the Singleton interface
     *
     * @return Frontend
     */
    public static function instance()
    {
        if (!(self::$_instance instanceof Frontend)) {
            self::$_instance = new Frontend;
        }

        return self::$_instance;
    }

    /**
     * The constructor for Frontend calls the parent Symphony constructor.
     *
     * @see core.Symphony#__construct()
     */
    protected function __construct()
    {
        parent::__construct();

        $this->_env = array();
    }

    /**
     * Accessor for `$_page`
     *
     * @return FrontendPage
     */
    public static function Page()
    {
        return self::$_page;
    }

    /**
     * Overrides the Symphony `isLoggedIn()` function to allow Authors
     * to become logged into the frontend when `$_REQUEST['auth-token']`
     * is present. This logs an Author in using the loginFromToken function.
     * This function allows the use of 'admin' type pages, where a Frontend
     * page requires that the viewer be a Symphony Author
     *
     * @see core.Symphony#loginFromToken()
     * @see core.Symphony#isLoggedIn()
     * @return boolean
     */
    public static function isLoggedIn()
    {
        if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && strlen($_REQUEST['auth-token']) == 8) {
            return self::loginFromToken($_REQUEST['auth-token']);
        }

        return Symphony::isLoggedIn();
    }

    /**
     * Called by index.php, this function is responsible for rendering the current
     * page on the Frontend. One delegate is fired, `FrontendInitialised`
     *
     * @uses FrontendInitialised
     * @see boot.getCurrentPage()
     * @param string $page
     *  The result of getCurrentPage, which returns the `$_GET['symphony-page']`
     * @throws FrontendPageNotFoundException
     * @throws SymphonyErrorPage
     * @return string
     *  The HTML of the page to return
     */
    public function display($page)
    {
        self::$_page = new FrontendPage;

        /**
         * `FrontendInitialised` is fired just after the `$_page` variable has been
         * created with an instance of the `FrontendPage` class. This delegate is
         * fired just before the `FrontendPage->generate()`.
         *
         * @delegate FrontendInitialised
         * @param string $context
         *  '/frontend/'
         */
        Symphony::ExtensionManager()->notifyMembers('FrontendInitialised', '/frontend/');

        $output = self::$_page->generate($page);

        return $output;
    }
}

/**
 * `FrontendPageNotFoundException` extends a default Exception, it adds nothing
 * but allows a different Handler to be used to render the Exception
 *
 * @see core.FrontendPageNotFoundExceptionHandler
 */
class FrontendPageNotFoundException extends Exception
{
    /**
     * The constructor for `FrontendPageNotFoundException` sets the default
     * error message and code for Logging purposes
     */
    public function __construct()
    {
        parent::__construct();
        $pagename = getCurrentPage();

        if (empty($pagename)) {
            $this->message = __('The page you requested does not exist.');
        } else {
            $this->message = __('The page you requested, %s, does not exist.', array('<code>' . $pagename . '</code>'));
        }

        $this->code = E_USER_NOTICE;
    }
}

/**
 * The `FrontendPageNotFoundExceptionHandler` attempts to find a Symphony
 * page that has been given the '404' page type to render the SymphonyErrorPage
 * error, instead of using the Symphony default.
 */
class FrontendPageNotFoundExceptionHandler extends SymphonyErrorPageHandler
{
    /**
     * The render function will take a `FrontendPageNotFoundException` Exception and
     * output a HTML page. This function first checks to see if their is a page in Symphony
     * that has been given the '404' page type, otherwise it will just use the default
     * Symphony error page template to output the exception
     *
     * @param Exception $e
     *  The Exception object
     * @throws FrontendPageNotFoundException
     * @throws SymphonyErrorPage
     * @return string
     *  An HTML string
     */
    public static function render(Exception $e)
    {
        $page = PageManager::fetchPageByType('404');
        $previous_exception = Frontend::instance()->getException();

        // No 404 detected, throw default Symphony error page
        if (is_null($page['id'])) {
            parent::render(new SymphonyErrorPage(
                $e->getMessage(),
                __('Page Not Found'),
                'generic',
                array(),
                Page::HTTP_STATUS_NOT_FOUND
            ));

            // Recursive 404
        } elseif (isset($previous_exception)) {
            parent::render(new SymphonyErrorPage(
                __('This error occurred whilst attempting to resolve the 404 page for the original request.') . ' ' . $e->getMessage(),
                __('Page Not Found'),
                'generic',
                array(),
                Page::HTTP_STATUS_NOT_FOUND
            ));

            // Handle 404 page
        } else {
            $url = '/' . PageManager::resolvePagePath($page['id']) . '/';

            Frontend::instance()->setException($e);
            $output = Frontend::instance()->display($url);
            echo $output;

            exit;
        }
    }
}
