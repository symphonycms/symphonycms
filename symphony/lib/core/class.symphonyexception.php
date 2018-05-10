<?php

/**
 * @package core
 */
/**
 * `SymphonyException` extends the default `Exception` class. All
 * of these exceptions will halt execution immediately and return the
 * exception as a HTML page. By default the HTML template is `usererror.generic.php`
 * from the `TEMPLATES` directory.
 */

class SymphonyException extends Exception
{
    /**
     * A heading for the error page, this will be prepended to
     * "Symphony Fatal Error".
     * @return string
     */
    private $_heading;

    /**
     * A string for the error page template to use, defaults to 'generic'. This
     * can be the name of any template file in the `TEMPLATES` directory.
     * A template using the naming convention of `usererror.*.php`.
     * @var string
     */
    private $_template = 'generic';

    /**
     * If the message as provided as an `XMLElement`, it will be saved to
     * this parameter
     * @var XMLElement
     */
    private $_messageObject = null;

    /**
     * An object of an additional information for this error page. Note that
     * this is provided as an array and then typecast to an object
     * @var StdClass
     */
    private $_additional = null;

    /**
     * A simple container for the response status code.
     * Full value is setted usign `$Page->setHttpStatus()`
     * in the template.
     */
    private $_status = Page::HTTP_STATUS_ERROR;

    /**
     * Constructor for SymphonyException sets it's class variables
     *
     * @param string|XMLElement $message
     *  A description for this error, which can be provided as a string
     *  or as an XMLElement.
     * @param string $heading
     *  A heading for the error page, by default this is "Symphony Fatal Error"
     * @param string $template
     *  A string for the error page template to use, defaults to 'generic'. This
     *  can be the name of any template file in the `TEMPLATES` directory.
     *  A template using the naming convention of `tpl.*.php`.
     * @param array $additional
     *  Allows custom information to be passed to the Symphony Error Page
     *  that the template may want to expose, such as custom Headers etc.
     * @param integer $status
     *  Properly sets the HTTP status code for the response. Defaults to
     *  `Page::HTTP_STATUS_ERROR`
     */
    public function __construct($message, $heading = 'Symphony Fatal Error', $template = 'generic', array $additional = array(), $status = Page::HTTP_STATUS_ERROR)
    {
        if ($message instanceof XMLElement) {
            $this->_messageObject = $message;
            $message = $this->_messageObject->generate();
        }

        parent::__construct($message);

        $this->_heading = $heading;
        $this->_template = $template;
        $this->_additional = (object)$additional;
        $this->_status = $status;
    }

    /**
     * Accessor for the `$_heading` of the error page
     *
     * @return string
     */
    public function getHeading()
    {
        return $this->_heading;
    }

    /**
     * Accessor for `$_messageObject`
     *
     * @return XMLElement
     */
    public function getMessageObject()
    {
        return $this->_messageObject;
    }

    /**
     * Accessor for `$_additional`
     *
     * @return StdClass
     */
    public function getAdditional()
    {
        return $this->_additional;
    }

    /**
     * Accessor for `$_status`
     *
     * @since Symphony 2.3.2
     * @return integer
     */
    public function getHttpStatusCode()
    {
        return $this->_status;
    }

    /**
     * Returns the path to the current template by looking at the
     * `WORKSPACE/template/` directory, then at the `TEMPLATES`
     * directory for the convention `usererror.*.php`. If the template
     * is not found, `false` is returned
     *
     * @since Symphony 2.3
     * @return string|false
     *  String, which is the path to the template if the template is found,
     *  false otherwise
     */
    public function getTemplate()
    {
        $format = '%s/usererror.%s.php';

        if (file_exists($template = sprintf($format, WORKSPACE . '/template', $this->_template))) {
            return $template;
        } elseif (file_exists($template = sprintf($format, TEMPLATE, $this->_template))) {
            return $template;
        } else {
            return false;
        }
    }

    /**
     * A simple getter to the template name in order to be able
     * to identify which type of exception this is.
     *
     * @since Symphony 2.3.2
     * @return string
     */
    public function getTemplateName()
    {
        return $this->_template;
    }
}
