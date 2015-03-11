<?php
/**
 * @package toolkit
 */
/**
 * XMLPage extends the Page class to provide an object representation
 * of a Symphony backend XML/AJAX page.
 */

abstract class XMLPage extends TextPage
{
    /**
     * The root node for the response of the XMLPage
     * @var XMLElement
     */
    protected $_Result;

    /**
     * The constructor for `XMLPage`. This sets the page status to `Page::HTTP_STATUS_OK`,
     * the default content type to `text/xml` and initialises `$this->_Result`
     * with an `XMLElement`. The constructor also starts the Profiler for this
     * page template.
     *
     * @see toolkit.Profiler
     */
    public function __construct()
    {
        $this->_Result = new XMLElement('result');
        $this->_Result->setIncludeHeader(true);

        $this->setHttpStatus(self::HTTP_STATUS_OK);
        $this->addHeaderToPage('Content-Type', 'text/xml');

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
        $this->_Result->setValue(__('You are not authorised to access this page.'));
    }

    /**
     * The generate functions outputs the correct headers for
     * this `XMLPage`, adds `$this->getHttpStatusCode()` code to the root attribute
     * before calling the parent generate function and generating
     * the `$this->_Result` XMLElement
     *
     * @param null $page
     * @return string
     */
    public function generate($page = null)
    {
        // Set the actual status code in the xml response
        $this->_Result->setAttribute('status', $this->getHttpStatusCode());

        parent::generate($page);

        return $this->_Result->generate(true);
    }
}
