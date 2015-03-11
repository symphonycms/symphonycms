<?php

/**
 * @package toolkit
 */
/**
 * The `XsltProcess` class is responsible for taking a chunk of XML
 * and applying an XSLT stylesheet to it. Custom error handlers are
 * used to capture any errors that occurred during this process, and
 * are exposed to the `ExceptionHandler`'s for display to the user.
 */

class XsltProcess
{
    /**
     * The XML for the transformation to be applied to
     * @var string
     */
    private $_xml;

    /**
     * The XSL for the transformation
     * @var string
     */
    private $_xsl;

    /**
     * Any errors that occur during the transformation are stored in this array.
     * @var array
     */
    private $_errors = array();

    /**
     * The `XsltProcess` constructor takes a two parameters for the
     * XML and the XSL and initialises the `$this->_xml` and `$this->_xsl` variables.
     * If an `XSLTProcessor` is not available, this function will return false
     *
     * @param string $xml
     *  The XML for the transformation to be applied to
     * @param string $xsl
     *  The XSL for the transformation
     */
    public function __construct($xml = null, $xsl = null)
    {
        $this->_xml = $xml;
        $this->_xsl = $xsl;
    }

    /**
     * Checks if there is an available `XSLTProcessor`
     *
     * @return boolean
     *  True if there is an existing `XsltProcessor` class, false otherwise
     */
    public static function isXSLTProcessorAvailable()
    {
        return (class_exists('XsltProcessor') || function_exists('xslt_process'));
    }

    /**
     * This function will take a given XML file, a stylesheet and apply
     * the transformation. Any errors will call the error function to log
     * them into the `$_errors` array
     *
     * @see toolkit.XSLTProcess#__error()
     * @see toolkit.XSLTProcess#__process()
     * @param string $xml
     *  The XML for the transformation to be applied to
     * @param string $xsl
     *  The XSL for the transformation
     * @param array $parameters
     *  An array of available parameters the XSL will have access to
     * @param array $register_functions
     *  An array of available PHP functions that the XSL can use
     * @return string|boolean
     *  The string of the resulting transform, or false if there was an error
     */
    public function process($xml = null, $xsl = null, array $parameters = array(), array $register_functions = array())
    {

        if ($xml) {
            $this->_xml = $xml;
        }

        if ($xsl) {
            $this->_xsl = $xsl;
        }

        // dont let process continue if no xsl functionality exists
        if (!XsltProcess::isXSLTProcessorAvailable()) {
            return false;
        }

        $XSLProc = new XsltProcessor;

        if (!empty($register_functions)) {
            $XSLProc->registerPHPFunctions($register_functions);
        }

        $result = @$this->__process(
            $XSLProc,
            $this->_xml,
            $this->_xsl,
            $parameters
        );

        unset($XSLProc);

        return $result;
    }

    /**
     * Uses `DomDocument` to transform the document. Any errors that
     * occur are trapped by custom error handlers, `trapXMLError` or
     * `trapXSLError`.
     *
     * @param XsltProcessor $XSLProc
     *  An instance of `XsltProcessor`
     * @param string $xml
     *  The XML for the transformation to be applied to
     * @param string $xsl
     *  The XSL for the transformation
     * @param array $parameters
     *  An array of available parameters the XSL will have access to
     * @return string
     */
    private function __process(XsltProcessor $XSLProc, $xml, $xsl, array $parameters = array())
    {
        // Create instances of the DomDocument class
        $xmlDoc = new DomDocument;
        $xslDoc= new DomDocument;

        // Set up error handling
        if (function_exists('ini_set')) {
            $ehOLD = ini_set('html_errors', false);
        }

        // Load the xml document
        set_error_handler(array($this, 'trapXMLError'));
        // Prevent remote entities from being loaded, RE: #1939
        $elOLD = libxml_disable_entity_loader(true);
        $xmlDoc->loadXML($xml, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0);
        libxml_disable_entity_loader($elOLD);

        // Must restore the error handler to avoid problems
        restore_error_handler();

        // Load the xsl document
        set_error_handler(array($this, 'trapXSLError'));
        // Ensure that the XSLT can be loaded with `false`. RE: #1939
        // Note that `true` will cause `<xsl:import />` to fail.
        $elOLD = libxml_disable_entity_loader(false);
        $xslDoc->loadXML($xsl, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0);
        libxml_disable_entity_loader($elOLD);

        // Load the xsl template
        $XSLProc->importStyleSheet($xslDoc);

        // Set parameters when defined
        if (!empty($parameters)) {
            General::flattenArray($parameters);

            $XSLProc->setParameter('', $parameters);
        }

        // Must restore the error handler to avoid problems
        restore_error_handler();

        // Start the transformation
        set_error_handler(array($this, 'trapXMLError'));
        $processed = $XSLProc->transformToXML($xmlDoc);

        // Restore error handling
        if (function_exists('ini_set') && isset($ehOLD)) {
            ini_set('html_errors', $ehOLD);
        }

        // Must restore the error handler to avoid problems
        restore_error_handler();

        return $processed;
    }

    /**
     * That validate function takes an XSD to valid against `$this->_xml`
     * returning boolean. Optionally, a second parameter `$xml` can be
     * passed that will be used instead of `$this->_xml`.
     *
     * @since Symphony 2.3
     * @param string $xsd
     *  The XSD to validate `$this->_xml` against
     * @param string $xml (optional)
     *  If provided, this function will use this `$xml` instead of
     *  `$this->_xml`.
     * @return boolean
     *  Returns true if the `$xml` validates against `$xsd`, false otherwise.
     *  If false is returned, the errors can be obtained with `XSLTProcess->getErrors()`
     */
    public function validate($xsd, $xml = null)
    {
        if (is_null($xml) && !is_null($this->_xml)) {
            $xml = $this->_xml;
        }

        if (is_null($xsd) || is_null($xml)) {
            return false;
        }

        // Create instances of the DomDocument class
        $xmlDoc = new DomDocument;

        // Set up error handling
        if (function_exists('ini_set')) {
            $ehOLD = ini_set('html_errors', false);
        }

        // Load the xml document
        set_error_handler(array($this, 'trapXMLError'));
        $elOLD = libxml_disable_entity_loader(true);
        $xmlDoc->loadXML($xml, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0);
        libxml_disable_entity_loader($elOLD);

        // Must restore the error handler to avoid problems
        restore_error_handler();

        // Validate the XML against the XSD
        set_error_handler(array($this, 'trapXSDError'));
        $result = $xmlDoc->schemaValidateSource($xsd);

        // Restore error handling
        if(function_exists('ini_set') && isset($ehOLD)){
            ini_set('html_errors', $ehOLD);
        }

        // Must restore the error handler to avoid problems
        restore_error_handler();

        return $result;
    }

    /**
     * A custom error handler especially for XML errors.
     *
     * @link http://au.php.net/manual/en/function.set-error-handler.php
     * @param integer $errno
     * @param integer $errstr
     * @param integer $errfile
     * @param integer $errline
     */
    public function trapXMLError($errno, $errstr, $errfile, $errline)
    {
        $this->__error($errno, str_replace('DOMDocument::', null, $errstr), $errfile, $errline, 'xml');
    }

    /**
     * A custom error handler especially for XSL errors.
     *
     * @link http://au.php.net/manual/en/function.set-error-handler.php
     * @param integer $errno
     * @param integer $errstr
     * @param integer $errfile
     * @param integer $errline
     */
    public function trapXSLError($errno, $errstr, $errfile, $errline)
    {
        $this->__error($errno, str_replace('DOMDocument::', null, $errstr), $errfile, $errline, 'xsl');
    }

    /**
     * A custom error handler especially for XSD errors.
     *
     * @since Symphony 2.3
     * @link http://au.php.net/manual/en/function.set-error-handler.php
     * @param integer $errno
     * @param integer $errstr
     * @param integer $errfile
     * @param integer $errline
     */
    public function trapXSDError($errno, $errstr, $errfile, $errline)
    {
        $this->__error($errno, str_replace('DOMDocument::', null, $errstr), $errfile, $errline, 'xsd');
    }

    /**
     * Writes an error to the `$_errors` array, which contains the error information
     * and some basic debugging information.
     *
     * @link http://au.php.net/manual/en/function.set-error-handler.php
     * @param integer $number
     * @param string $message
     * @param string $file
     * @param string $line
     * @param string $type
     *  Where the error occurred, can be either 'xml', 'xsl' or `xsd`
     */
    public function __error($number, $message, $file = null, $line = null, $type = null)
    {
        $context = null;

        if ($type == 'xml' || $type == 'xsd') {
            $context = $this->_xml;
        }

        if ($type == 'xsl') {
            $context = $this->_xsl;
        }

        $this->_errors[] = array(
            'number' => $number,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'type' => $type,
            'context' => $context
        );
    }

    /**
     * Returns boolean if any errors occurred during the transformation.
     *
     * @see getError
     * @return boolean
     */
    public function isErrors()
    {
        return (!empty($this->_errors) ? true : false);
    }

    /**
     * Provides an Iterator interface to return an error from the `$_errors`
     * array. Repeat calls to this function to get all errors
     *
     * @param boolean $all
     *  If true, return all errors instead of one by one. Defaults to false
     * @param boolean $rewind
     *  If rewind is true, resets the internal array pointer to the start of
     *  the `$_errors` array. Defaults to false.
     * @return array
     *  Either an array of error array's or just an error array
     */
    public function getError($all = false, $rewind = false)
    {
        if ($rewind) {
            reset($this->_errors);
        }

        return ($all ? $this->_errors : each($this->_errors));
    }
}
