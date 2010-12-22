<?php

	/**
	 * @package toolkit
	 */
	/**
	 * XSLTPage extends the Page class to provide an object representation
	 * of a Page that will be generated using XSLT.
	 */

	require_once(TOOLKIT . '/class.page.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');

	Class XSLTPage extends Page{

		/**
		 * An instance of the XsltProcess class
		 * @var XsltProcess
		 */
		public $Proc;

		/**
		 * The XML to be transformed
		 * @var string
		 */
		protected $_xml;

		/**
		 * The XSL to apply to the XML
		 * @var string
		 */
		protected $_xsl;

		/**
		 * An array of all the parameters to be made available during the XSLT
		 * transform
		 * @var array
		 */
		protected $_param = array();

		/**
		 * An array of functions to be made available during the XSLT transform
		 * @var array
		 */
		protected $_registered_php_functions = array();

		/**
		 * The constructor for the XSLTPage ensures that an XSLTProcessor
		 * is available, and then sets an instance of it to `$this->Proc`, otherwise
		 * throws a SymphonyErrorPage.
		 */
		public function __construct(){

			if(!XsltProcess::isXSLTProcessorAvailable()) {
                GenericExceptionHandler::$enabled = true;
				throw new SymphonyErrorPage(__('No suitable XSLT processor was found.'));
			}

			$this->Proc = new XsltProcess;
		}

		/**
		 * Setter for `$this->_xml`, can optionally load the XML from a file.
		 *
		 * @param string $xml
		 *  The XML for this XSLT page
		 * @param boolean $isFile
		 *  If set to true, the XML will be loaded from a file. It is false by default
		 */
		public function setXML($xml, $isFile=false){
			$this->_xml = ($isFile ? file_get_contents($xml) : $xml);
		}

		/**
		 * Accessor for the XML of this page
		 *
		 * @return string
		 */
		public function getXML(){
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
		public function setXSL($xsl, $isFile=false){
			$this->_xsl = ($isFile ? file_get_contents($xsl) : $xsl);
		}

		/**
		 * Accessor for the XSL of this page
		 *
		 * @return string
		 */
		public function getXSL(){
			return $this->_xsl;
		}

		/**
		 * Sets the parameters that will output with the resulting page
		 * and be accessible in the XSLT. This function translates all ' into
		 * `&apos;` to prevent the issue described on the setParameter page
		 * http://www.php.net/manual/en/xsltprocessor.setparameter.php#81077
		 * The tradeoff is that a <xsl:value-of select='$param' /> that has a '
		 * will output `&apos;`, the benefit is that both ' and " can be in the params
		 *
		 * @param array $param
		 *  An associative array of params for this page
		 */
		public function setRuntimeParam($param){
			$this->_param = str_replace("'", "&apos;", $param);
		}

		/**
		 * Returns an Iterator of errors from the XsltProcess. Use this function
		 * inside a loop to get all the errors
		 *
		 * @return array
		 *  An associative array containing the errors details from the
		 *  XsltProcessor
		 */
		public function getError(){
			return $this->Proc->getError();
		}

		/**
		 * Allows the registration of PHP functions to be used on the Frontend
		 * by passing the function name or an array of function names
		 *
		 * @param mixed $function
		 *  Either an array of function names, or just the function name as a
		 *  string
		 */
		public function registerPHPFunction($function){
			if(is_array($function)) $this->_registered_php_functions += $function;
			else $this->_registered_php_functions[] = $function;
		}

		/**
		 * The generate function calls on the XsltProcess to transform the
		 * XML with the given XSLT passing any parameters or functions
		 * If no errors occur, the parent generate function is called to add
		 * the page headers and a string containing the transformed result
		 * is result.
		 *
		 * @return string
		 */
		public function generate(){

			$result = $this->Proc->process($this->_xml, $this->_xsl, $this->_param, $this->_registered_php_functions);

			if($this->Proc->isErrors()) return false;

			parent::generate();

			return $result;
		}

	}
