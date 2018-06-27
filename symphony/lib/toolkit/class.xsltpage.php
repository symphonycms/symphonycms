<?php

/**
 * @package toolkit
 */
/**
 * XSLTPage extends the Page class to provide an object representation
 * of a Page that will be generated using XSLT.
 */

class XSLTPage extends Page
{
    /**
     * An instance of the XSLTProcess class
     * @var XSLTProcess
     */
    public $Proc;

    /**
     * The XML to be transformed
     * @since Symphony 2.4 this variable may be a string or an XMLElement
     * @var string|XMLElement
     */
    protected $_xml;

    /**
     * The XSL to apply to the `$this->_xml`.
     * @var string
     */
    protected $_xsl;

    /**
     * The constructor for the `XSLTPage` ensures that an `XSLTProcessor`
     * is available, and then sets an instance of it to `$this->Proc`, otherwise
     * it will throw a `SymphonyException` exception.
     */
    public function __construct()
    {
        parent::__construct();
        if (!XSLTProcess::isXSLTProcessorAvailable()) {
            Symphony::Engine()->throwCustomError(__('No suitable XSLT processor was found.'));
        }

        $this->Proc = new XSLTProcess;
    }

    /**
     * Setter for `$this->_xml`, can optionally load the XML from a file.
     *
     * @param string|XMLElement $xml
     *  The XML for this XSLT page
     * @param boolean $isFile
     *  If set to true, the XML will be loaded from a file. It is false by default
     */
    public function setXML($xml, $isFile = false)
    {
        $this->_xml = ($isFile ? file_get_contents($xml) : $xml);
    }

    /**
     * Accessor for the XML of this page
     *
     * @return string|XMLElement
     */
    public function getXML()
    {
        return $this->_xml;
    }

    /**
     * Setter for `$this->_xsl`, can optionally load the XSLT from a file.
     *
     * @param string $xsl
     *  The XSLT for this XSLT page
     * @param boolean $isFile
     *  If set to true, the XSLT will be loaded from a file. It is false by default
     */
    public function setXSL($xsl, $isFile = false)
    {
        $this->_xsl = ($isFile ? file_get_contents($xsl) : $xsl);
    }

    /**
     * Accessor for the XSL of this page
     *
     * @return string
     */
    public function getXSL()
    {
        return $this->_xsl;
    }

    /**
     * Returns an iterator of errors from the `XSLTProcess`. Use this function
     * inside a loop to get all the errors that occurring when transforming
     * `$this->_xml` with `$this->_xsl`.
     *
     * @return array
     *  An associative array containing the errors details from the
     *  `XSLTProcessor`
     */
    public function getError()
    {
        return $this->Proc->getError();
    }

    /**
     * The generate function calls on the `XSLTProcess` to transform the
     * XML with the given XSLT passing any parameters or functions
     * If no errors occur, the parent generate function is called to add
     * the page headers and a string containing the transformed result
     * is result.
     *
     * @param null $page
     * @return string
     */
    public function generate($page = null)
    {
        $result = $this->Proc->process($this->_xml, $this->_xsl);

        if ($this->Proc->isErrors()) {
            $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
            return '';
        }

        parent::generate($page);

        return $result;
    }
}
