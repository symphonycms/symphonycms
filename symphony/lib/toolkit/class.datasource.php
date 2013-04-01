<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The Datasource class provides functionality to mainly process any parameters
	 * that the fields will use in filters find the relevant Entries and return these Entries
	 * data as XML so that XSLT can be applied on it to create your website. In Symphony,
	 * there are four Datasource types provided, Section, Author, Navigation and Dynamic
	 * XML. Section is the mostly commonly used Datasource, which allows the filtering
	 * and searching for Entries in a Section to be returned as XML. Navigation datasources
	 * expose the Symphony Navigation structure of the Pages in the installation. Authors
	 * expose the Symphony Authors that are registered as users of the backend. Finally,
	 * the Dynamic XML datasource allows XML pages to be retrieved. This is especially
	 * helpful for working with Restful XML API's. Datasources are saved through the
	 * Symphony backend, which uses a Datasource template defined in
	 * `TEMPLATE . /datasource.tpl`.
	 */

	Class DataSource {

		/**
		 * A constant that represents if this filter is an AND filter in which
		 * an Entry must match all these filters. This filter is triggered when
		 * the filter string contains a ` + `.
		 *
		 * @since Symphony 2.3.2
		 * @var integer
		 */
		const FILTER_AND = 1;

		/**
		 * A constant that represents if this filter is an OR filter in which an
		 * entry can match any or all of these filters
		 *
		 * @since Symphony 2.3.2
		 * @var integer
		 */
		const FILTER_OR = 2;

		/**
		 * Holds all the environment variables which include parameters set by
		 * other Datasources or Events.
		 * @var array
		 */
		protected $_env = array();

		/**
		 * If true, this datasource only will be outputting parameters from the
		 * Entries, and no actual content.
		 * @var boolean
		 */
		protected $_param_output_only;

		/**
		 * An array of datasource dependancies. These are datasources that must
		 * run first for this datasource to be able to execute correctly
		 * @var array
		 */
		protected $_dependencies = array();

		/**
		 * When there is no entries found by the Datasource, this parameter will
		 * be set to true, which will inject the default Symphony 'No records found'
		 * message into the datasource's result
		 * @var boolean
		 */
		protected $_force_empty_result = false;

		/**
		 * Constructor for the datasource sets the parent, if `$process_params` is set,
		 * the `$env` variable will be run through `Datasource::processParameters`.
		 *
		 * @see toolkit.Datasource#processParameters()
		 * @param array $env
		 *  The environment variables from the Frontend class which includes
		 *  any params set by Symphony or Events or by other Datasources
		 * @param boolean $process_params
		 *  If set to true, `Datasource::processParameters` will be called. By default
		 *  this is true
		 */
		public function __construct(array $env = null, $process_params=true){
			// Support old the __construct (for the moment anyway).
			// The old signature was array/array/boolean
			// The new signature is array/boolean
			$arguments = func_get_args();

			if(count($arguments) == 3 && is_bool($arguments[1]) && is_bool($arguments[2])) {
				$env = $arguments[0];
				$process_params = $arguments[1];
			}

			if($process_params){
				$this->processParameters($env);
			}
		}

		/**
		 * This function is required in order to edit it in the datasource editor page.
		 * Do not overload this function if you are creating a custom datasource. It is only
		 * used by the datasource editor. If this is set to false, which is default, the
		 * Datasource's `about()` information will be displayed.
		 *
		 * @return boolean
		 *   True if the Datasource can be edited, false otherwise. Defaults to false
		 */
		public function allowEditorToParse(){
			return false;
		}

		/**
		 * This function is required in order to identify what section this Datasource is for. It
		 * is used in the datasource editor. It must remain intact. Do not overload this function in
		 * custom events. Other datasources may return a string here defining their datasource
		 * type when they do not query a section.
		 *
		 * @return mixed
		 */
		public function getSource(){
			return null;
		}

		/**
		 * Accessor function to return this Datasource's dependencies
		 *
		 * @return array
		 */
		public function getDependencies(){
			return $this->_dependencies;
		}

		/**
		 * Returns an associative array of information about a datasource.
		 *
		 * @return array
		 */
		public function about() {
			return array();
		}

		/**
		 * @deprecated This function has been renamed to `execute` as of
		 *  Symphony 2.3.1, please use `execute()` instead.
		 * @see execute()
		 */
		public function grab(array &$param_pool = null) {
			return $this->execute($param_pool);
		}

		/**
		 * The meat of the Datasource, this function includes the datasource
		 * type's file that will preform the logic to return the data for this datasource
		 * It is passed the current parameters.
		 *
		 * @param array $param_pool
		 *  The current parameter pool that this Datasource can use when filtering
		 *  and finding Entries or data.
		 * @return XMLElement
		 *  The XMLElement to add into the XML for a page.
		 */
		public function execute(array &$param_pool = null) {
			$result = new XMLElement($this->dsParamROOTELEMENT);

			try{
				$result = $this->execute($param_pool);
			}
			catch(FrontendPageNotFoundException $e){
				// Work around. This ensures the 404 page is displayed and
				// is not picked up by the default catch() statement below
				FrontendPageNotFoundExceptionHandler::render($e);
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', $e->getMessage()));
				return $result;
			}

			if($this->_force_empty_result) $result = $this->emptyXMLSet();

			return $result;
		}

		/**
		 * By default, all Symphony filters are considering to be AND filters, that is
		 * they are all used and Entries must match each filter to be included. It is
		 * possible to use OR filtering in a field by using an + to separate the values.
		 * eg. If the filter is test1 + test2, this will match any entries where this field
		 * is test1 OR test2. This function is run on each filter (ie. each field) in a
		 * datasource
		 *
		 * @param string $value
		 *  The filter string for a field.
		 * @return integer
		 *  DataSource::FILTER_OR or DataSource::FILTER_AND
		 */
		public function __determineFilterType($value){
			return preg_match('/\s+\+\s+/', $value) ? DataSource::FILTER_AND : DataSource::FILTER_OR;
		}

		/**
		 * If there is no results to return this function calls `Datasource::__noRecordsFound`
		 * which appends an XMLElement to the current root element.
		 *
		 * @param XMLElement $xml
		 *  The root element XMLElement for this datasource. By default, this will
		 *  the handle of the datasource, as defined by `$this->dsParamROOTELEMENT`
		 * @return XMLElement
		 */
		public function emptyXMLSet(XMLElement $xml = null){
			if(is_null($xml)) $xml = new XMLElement($this->dsParamROOTELEMENT);
			$xml->appendChild($this->__noRecordsFound());

			return $xml;
		}

		/**
		 * Returns an error XMLElement with 'No records found' text
		 *
		 * @return XMLElement
		 */
		public function __noRecordsFound(){
			return new XMLElement('error', __('No records found.'));
		}

		/**
		 * This function will iterates over the filters and replace any parameters with their
		 * actual values. All other Datasource variables such as sorting, ordering and
		 * pagination variables are also set by this function
		 *
		 * @param array $env
		 *  The environment variables from the Frontend class which includes
		 *  any params set by Symphony or Events or by other Datasources
		 */
		public function processParameters(array $env = null){

			if($env) $this->_env = $env;

			if((isset($this->_env) && is_array($this->_env)) && isset($this->dsParamFILTERS) && is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
				foreach($this->dsParamFILTERS as $key => $value){
					$value = stripslashes($value);
					$new_value = $this->__processParametersInString($value, $this->_env);

					if(strlen(trim($new_value)) == 0) unset($this->dsParamFILTERS[$key]);
					else $this->dsParamFILTERS[$key] = $new_value;

				}
			}

			if(isset($this->dsParamORDER)) $this->dsParamORDER = $this->__processParametersInString($this->dsParamORDER, $this->_env);

			if(isset($this->dsParamSORT)) $this->dsParamSORT = $this->__processParametersInString($this->dsParamSORT, $this->_env);

			if(isset($this->dsParamSTARTPAGE)) {
				$this->dsParamSTARTPAGE = $this->__processParametersInString($this->dsParamSTARTPAGE, $this->_env);
				if ($this->dsParamSTARTPAGE == '') $this->dsParamSTARTPAGE = '1';
			}

			if(isset($this->dsParamLIMIT)) $this->dsParamLIMIT = $this->__processParametersInString($this->dsParamLIMIT, $this->_env);

			if(
				isset($this->dsParamREQUIREDPARAM)
				&& strlen(trim($this->dsParamREQUIREDPARAM)) > 0
				&& $this->__processParametersInString(trim($this->dsParamREQUIREDPARAM), $this->_env, false) == ''
			) {
				$this->_force_empty_result = true; // don't output any XML
				$this->dsParamPARAMOUTPUT = null; // don't output any parameters
				$this->dsParamINCLUDEDELEMENTS = null; // don't query any fields in this section
			}

			$this->_param_output_only = ((!isset($this->dsParamINCLUDEDELEMENTS) || !is_array($this->dsParamINCLUDEDELEMENTS) || empty($this->dsParamINCLUDEDELEMENTS)) && !isset($this->dsParamGROUP));

			if(isset($this->dsParamREDIRECTONEMPTY) && $this->dsParamREDIRECTONEMPTY == 'yes' && $this->_force_empty_result){
				throw new FrontendPageNotFoundException;
			}
		}

		/**
		 * This function will parse a string (usually a URL) and fully evaluate any
		 * parameters (defined by {$param}) to return the absolute string value.
		 *
		 * @since Symphony 2.3
		 * @param string $url
		 *  The string (usually a URL) that contains the parameters (or doesn't)
		 * @return string
		 *  The parsed URL
		 */
		public function parseParamURL($url = null) {
			if(!isset($url)) return null;

			// urlencode parameters
			$params = array();

			if(preg_match_all('@{([^}]+)}@i', $url, $matches, PREG_SET_ORDER)){
				foreach($matches as $m){
					$params[$m[1]] = array(
						'param' => preg_replace('/:encoded$/', NULL, $m[1]),
						'encode' => preg_match('/:encoded$/', $m[1])
					);
				}
			}

			foreach($params as $key => $info){
				$replacement = $this->__processParametersInString($info['param'], $this->_env, false);
				if($info['encode'] == true){
					$replacement = urlencode($replacement);
				}
				$url = str_replace("{{$key}}", $replacement, $url);
			}

			return $url;
		}

		/**
		 * This function will replace any parameters in a string with their value.
		 * Parameters are defined by being prefixed by a $ character. In certain
		 * situations, the parameter will be surrounded by {}, which Symphony
		 * takes to mean, evaluate this parameter to a value, other times it will be
		 * omitted which is usually used to indicate that this parameter exists
		 *
		 * @param string $value
		 *  The string with the parameters that need to be evaluated
		 * @param array $env
		 *  The environment variables from the Frontend class which includes
		 *  any params set by Symphony or Events or by other Datasources
		 * @param boolean $includeParenthesis
		 *  Parameters will sometimes not be surrounded by {}. If this is the case
		 *  setting this parameter to false will make this function automatically add
		 *  them to the parameter. By default this is true, which means all parameters
		 *  in the string already are surrounded by {}
		 * @param boolean $escape
		 *  If set to true, the resulting value will be `urlencode`'d before being returned.
		 *  By default this is false
		 * @return string
		 *  The string will all parameters evaluated. If a parameter was not found, it will
		 *  not be replaced at all.
		 */
		public function __processParametersInString($value, array $env, $includeParenthesis=true, $escape=false){
			if(trim($value) == '') return null;

			if(!$includeParenthesis) $value = '{'.$value.'}';

			if(preg_match_all('@{([^}]+)}@i', $value, $matches, PREG_SET_ORDER)){

				foreach($matches as $match){

					list($source, $cleaned) = $match;

					$replacement = null;

					$bits = preg_split('/:/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

					foreach($bits as $param){

						if($param{0} != '$'){
							$replacement = $param;
							break;
						}

						$param = trim($param, '$');

						$replacement = Datasource::findParameterInEnv($param, $env);

						if(is_array($replacement)){
							$replacement = array_map(array('Datasource', 'escapeCommas'), $replacement);
							if(count($replacement) > 1) $replacement = implode(',', $replacement);
							else $replacement = end($replacement);
						}

						if(!empty($replacement)) break;

					}

					if($escape == true) $replacement = urlencode($replacement);
					$value = str_replace($source, $replacement, $value);

				}
			}

			return $value;
		}

		/**
		 * Using regexp, this escapes any commas in the given string
		 *
		 * @param string $string
		 *  The string to escape the commas in
		 * @return string
		 */
		public static function escapeCommas($string){
			return preg_replace('/(?<!\\\\),/', "\\,", $string);
		}

		/**
		 * Used in conjunction with escapeCommas, this function will remove
		 * the escaping pattern applied to the string (and commas)
		 *
		 * @param string $string
		 *  The string with the escaped commas in it to remove
		 * @return string
		 */
		public static function removeEscapedCommas($string){
			return preg_replace('/(?<!\\\\)\\\\,/', ',', $string);
		}

		/**
		 * Parameters can exist in three different facets of Symphony; in the URL,
		 * in the parameter pool or as an Symphony param. This function will attempt
		 * to find a parameter in those three areas and return the value. If it is not found
		 * null is returned
		 *
		 * @param string $needle
		 *  The parameter name
		 * @param array $env
		 *  The environment variables from the Frontend class which includes
		 *  any params set by Symphony or Events or by other Datasources
		 * @return mixed
		 *  If the value is not found, null, otherwise a string or an array is returned
		 */
		public static function findParameterInEnv($needle, $env){
			if(isset($env['env']['url'][$needle])) return $env['env']['url'][$needle];

			if(isset($env['env']['pool'][$needle])) return $env['env']['pool'][$needle];

			if(isset($env['param'][$needle])) return $env['param'][$needle];

			return null;
		}
	}

	/**
	 * A constant that represents if this filter is an AND filter in which
	 * an Entry must match all these filters
	 *
	 * @deprecated This constant has been deprecated and will be removed in
	 *  Symphony 2.4. Use DataSource::FILTER_AND instead
	 * @var integer
	 */
	define_safe('DS_FILTER_AND', 1);

	/**
	 * A constant that represents if this filter is an OR filter in which an
	 * entry can match any or all of these filters
	 *
	 * @deprecated This constant has been deprecated and will be removed in
	 *  Symphony 2.4. Use DataSource::FILTER_AND instead
	 * @var integer
	 */
	define_safe('DS_FILTER_OR', 2);

	require_once(TOOLKIT . '/data-sources/class.datasource.author.php');
	require_once(TOOLKIT . '/data-sources/class.datasource.section.php');
	require_once(TOOLKIT . '/data-sources/class.datasource.static.php');
	require_once(TOOLKIT . '/data-sources/class.datasource.dynamic_xml.php');
	require_once(TOOLKIT . '/data-sources/class.datasource.navigation.php');
