<?php
/**
 * @package toolkit
 */
/**
 * TextPage extends the Page class to provide an object representation
 * of a Symphony backend text page.
 */

abstract class TextPage extends Page
{
    /**
     * The body string response of the TextPage
     * @var string
     */
    protected $_Result;

    /**
     * The constructor for `TextPage`. This sets the page status to `Page::HTTP_STATUS_OK`,
     * the default content type to `text/plain` and initialises `$this->_Result`
     * with an empty `string`. The constructor also starts the Profiler for this
     * page template.
     *
     * @see toolkit.Profiler
     */
    public function __construct()
    {
        $this->_Result = "";

        $this->setHttpStatus(self::HTTP_STATUS_OK);
        $this->addHeaderToPage('Content-Type', 'text/plain');

        Symphony::Profiler()->sample('Page template created', PROFILE_LAP);
    }

    /**
     * This function is called by Administration class when a user is not authenticated
     * to the Symphony backend. It sets the status of this page to
     * `Page::HTTP_STATUS_UNAUTHORIZED` and appends a message for generation
     */
    public function handleFailedAuthorisation()
    {
        $this->setHttpStatus(self::HTTP_STATUS_UNAUTHORIZED);
        $this->_Result = __('You are not authorised to access this page.');
    }

    /**
     * Calls the view function of this page. If a context is passed, it is
     * also set.
     *
     * @see view()
     * @param array $context
     *  The context of the page as an array. Defaults to null
     */
    public function build($context = null)
    {
        if ($context) {
            $this->_context = $context;
        }

        $this->view();
    }

    /**
     * The generate functions outputs the correct headers for
     * this `TextPage`, before calling the parent generate function and
     * returning the `$this->_Result` string
     *
     * @param null $page
     * @return string
     */
    public function generate($page = null)
    {
        parent::generate($page);
        return $this->_Result;
    }

    /**
     * All classes that extend the `TextPage` class must define a view method
     * which contains the logic for the content of this page. The resulting values
     * must be appended to `$this->_Result` where it is generated as json on build
     *
     * @see build()
     */
    abstract public function view();
}
