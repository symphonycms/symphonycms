<?php

	require_once(TOOLKIT . '/class.xsltpage.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once(TOOLKIT . '/class.extensionmanager.php');
			
	Class FrontendPage extends XSLTPage{
		
		const FRONTEND_OUTPUT_NORMAL = 0;
		const FRONTEND_OUTPUT_DEBUG = 1;
		const FRONTEND_OUTPUT_PROFILE = 2;
				
		private $_page;
		private $_pageData;
		private $_env;
		private $_events_xml;
		public $_param;		
		public $_Parent;
		public $DatasourceManager;
		public $ExtensionManager;		
				
		function __construct(&$parent){
			parent::__construct();
			
			$this->_Parent = $parent;
			$this->_env = array();
			
			$this->DatasourceManager = new DatasourceManager($this->_Parent);
			$this->EventManager = new EventManager($this->_Parent);	
			$this->ExtensionManager = new ExtensionManager($this->_Parent);
		}
		
		public function pageData(){
			return $this->_pageData;
		}
		
		public function Env(){
			return $this->_env;
		}
		
		public function generate($page) {
			$full_generate = true;
			$devkit = null;
			$output = null;
			
			if ($this->_Parent->isLoggedIn()) {
				####
				# Delegate: FrontendDevKitResolve
				# Description: Allows a devkit object to be specified, and stop continued execution:
				# Global: Yes
				$this->ExtensionManager->notifyMembers(
					'FrontendDevKitResolve', '/frontend/',
					array(
						'full_generate'	=> &$full_generate,
						'devkit'		=> &$devkit
					)
				);
			}
			
			$this->_Parent->Profiler->sample('Page creation process started');
			$this->_page = $page;
			$this->__buildPage($full_generate);
			
			if ($full_generate) {
				####
				# Delegate: FrontendOutputPreGenerate
				# Description: Immediately before generating the page. Provided with the page object, XML and XSLT
				# Global: Yes
				$this->ExtensionManager->notifyMembers(
					'FrontendOutputPreGenerate', '/frontend/',
					array(
						'page'	=> &$this,
						'xml'	=> $this->_xml,
						'xsl'	=> $this->_xsl
					)
				);
				
				if (is_null($devkit)) {
					if(@in_array('XML', $this->_pageData['type']) || @in_array('xml', $this->_pageData['type'])) {
						$this->addHeaderToPage('Content-Type', 'text/xml; charset=utf-8');
					}
					
					else{
						$this->addHeaderToPage('Content-Type', 'text/html; charset=utf-8');
					}
						
					if(@in_array('404', $this->_pageData['type'])){
						$this->addHeaderToPage('HTTP/1.0 404 Not Found');
					}
					
					elseif(@in_array('403', $this->_pageData['type'])){
						$this->addHeaderToPage('HTTP/1.0 403 Forbidden');
					}
				}
				
				####
				# Delegate: FrontendPreRenderHeaders
				# Description: This is just prior to the page headers being rendered, and is suitable for changing them
				# Global: Yes
				$this->ExtensionManager->notifyMembers('FrontendPreRenderHeaders', '/frontend/');
				
				$output = parent::generate();
				
				####
				# Delegate: FrontendOutputPostGenerate
				# Description: Immediately after generating the page. Provided with string containing page source
				# Global: Yes
				$this->ExtensionManager->notifyMembers('FrontendOutputPostGenerate', '/frontend/', array('output' => &$output));

				$this->_Parent->Profiler->sample('XSLT Transformation', PROFILE_LAP);
				
				if (is_null($devkit) && !$output) {
					$errstr = NULL;
					
					while (list($key, $val) = $this->Proc->getError()) {
						$errstr .= 'Line: ' . $val['line'] . ' - ' . $val['message'] . self::CRLF;
					};
					
					throw new SymphonyErrorPage(trim($errstr), NULL, 'xslt-error', array('proc' => clone $this->Proc));
				}
				
				$this->_Parent->Profiler->sample('Page creation complete');
			}
			
			if (!is_null($devkit)) {
				$devkit->prepare($this, $this->_pageData, $this->_xml, $this->_param, $output);
				
				return $devkit->build();
			}
			
			## EVENT DETAILS IN SOURCE
			if ($this->_Parent->isLoggedIn() && Symphony::Configuration()->get('display_event_xml_in_source', 'public') == 'yes') {
				$output .= self::CRLF . '<!-- ' . self::CRLF . $this->_events_xml->generate(true) . ' -->';
			}
			
			return $output;
		}
		
		private function __buildPage(){
			
			$start = precision_timer();
			
			if(!$page = $this->resolvePage()){
				
				$page = Symphony::Database()->fetchRow(0, "
								SELECT `tbl_pages`.* 
								FROM `tbl_pages`, `tbl_pages_types` 
								WHERE `tbl_pages_types`.page_id = `tbl_pages`.id 
								AND tbl_pages_types.`type` = '404' 
								LIMIT 1");

				if(empty($page)){
					throw new SymphonyErrorPage(
						__('The page you requested does not exist.'), 	
						__('Page Not Found'),
						'error', 
						array('header' => 'HTTP/1.0 404 Not Found')
					);
				}
				
				$page['filelocation'] = $this->resolvePageFileLocation($page['path'], $page['handle']);
				$page['type'] = $this->__fetchPageTypes($page['id']);	
			}
			
			####
			# Delegate: FrontendPageResolved
			# Description: Just after having resolved the page, but prior to any commencement of output creation
			# Global: Yes
			$this->ExtensionManager->notifyMembers('FrontendPageResolved', '/frontend/', array('page' => &$this, 'page_data' => &$page));

			$this->_pageData = $page;
			$root_page = @array_shift(explode('/', $page['path']));
			$current_path = explode(dirname($_SERVER['SCRIPT_NAME']), $_SERVER['REQUEST_URI'], 2);
			$current_path = '/' . ltrim(end($current_path), '/');
			
			// Get max upload size from php and symphony config then choose the smallest
			$upload_size_php = ini_size_to_bytes(ini_get('upload_max_filesize'));
			$upload_size_sym = Frontend::instance()->Configuration->get('max_upload_size','admin');

			$this->_param = array(
				'today' => DateTimeObj::get('Y-m-d'),
				'current-time' => DateTimeObj::get('H:i'),
				'this-year' => DateTimeObj::get('Y'),
				'this-month' => DateTimeObj::get('m'),
				'this-day' => DateTimeObj::get('d'),
				'timezone' => DateTimeObj::get('P'),
				'website-name' => Symphony::Configuration()->get('sitename', 'general'),
				'page-title' => $page['title'],
				'root' => URL,
				'workspace' => URL . '/workspace',
				'root-page' => ($root_page ? $root_page : $page['handle']),
				'current-page' => $page['handle'],
				'current-page-id' => $page['id'],
				'current-path' => $current_path,
				'parent-path' => '/' . $page['path'],
				'current-url' => URL . $current_path,
				'upload-limit' => min($upload_size_php, $upload_size_sym),
				'symphony-version' => $this->_Parent->Configuration->get('version', 'symphony'),
			);
		
			if(is_array($this->_env['url'])){
				foreach($this->_env['url'] as $key => $val) $this->_param[$key] = $val;
			}

			if(is_array($_GET) && !empty($_GET)){
			    foreach($_GET as $key => $val){			    
			        if(!in_array($key, array('symphony-page', 'debug', 'profile'))) $this->_param['url-' . $key] = $val;
			    }
			}
			
			if(is_array($_COOKIE[__SYM_COOKIE_PREFIX_]) && !empty($_COOKIE[__SYM_COOKIE_PREFIX_])){
				foreach($_COOKIE[__SYM_COOKIE_PREFIX_] as $key => $val){
					$this->_param['cookie-' . $key] = $val;
				}
			}
			
			// Flatten parameters:
			General::flattenArray($this->_param);

			####
			# Delegate: FrontendParamsResolve
			# Description: Just after having resolved the page params, but prior to any commencement of output creation
			# Global: Yes
			$this->ExtensionManager->notifyMembers('FrontendParamsResolve', '/frontend/', array('params' => &$this->_param));
			
			$xml_build_start = precision_timer();
			
			$xml = new XMLElement('data');
			$xml->setIncludeHeader(true);
			
			$events = new XMLElement('events');
			$this->processEvents($page['events'], $events);
			$xml->appendChild($events);
			
			$this->_events_xml = clone $events;
						
			$this->processDatasources($page['data_sources'], $xml);
			
			$this->_Parent->Profiler->seed($xml_build_start);
			$this->_Parent->Profiler->sample('XML Built', PROFILE_LAP);
			
			if(is_array($this->_env['pool']) && !empty($this->_env['pool'])){
				foreach($this->_env['pool'] as $handle => $p){
		
					if(!is_array($p)) $p = array($p);
					foreach($p as $key => $value){
						
						if(is_array($value) && !empty($value)){
							foreach($value as $kk => $vv){
								$this->_param[$handle] .= @implode(', ', $vv) . ',';
							}
						}

						else{
							$this->_param[$handle] = @implode(', ', $p);
						}
					}
					
					$this->_param[$handle] = trim($this->_param[$handle], ',');
				}
			}
			
			####
			# Delegate: FrontendParamsPostResolve
			# Description: Access to the resolved param pool, including additional parameters provided by Data Source outputs
			# Global: Yes
			$this->ExtensionManager->notifyMembers('FrontendParamsPostResolve', '/frontend/', array('params' => &$this->_param));

			$xsl = '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:import href="./workspace/pages/' . basename($page['filelocation']).'"/>
</xsl:stylesheet>';
			
			$this->_Parent->Profiler->seed();
			$this->setXML($xml->generate(true, 0));
			$this->_Parent->Profiler->sample('XML Generation', PROFILE_LAP);

			$this->setXSL($xsl, false);
			$this->setRuntimeParam($this->_param);
			
			$this->_Parent->Profiler->seed($start);
			$this->_Parent->Profiler->sample('Page Built', PROFILE_LAP);
		
		}

		public function resolvePage($page=NULL){
		
			if($page) $this->_page = $page;
		
			$row = NULL;

			####
			# Delegate: FrontendPrePageResolve
			# Description: Before page resolve. Allows manipulation of page without redirection
			# Global: Yes
			$this->ExtensionManager->notifyMembers('FrontendPrePageResolve', '/frontend/', array('row' => &$row, 'page' => &$this->_page));
			
			
			## Default to the index page if no page has been specified
			if(!$this->_page && is_null($row)){
				$row = Symphony::Database()->fetchRow(0, "SELECT `tbl_pages`.* FROM `tbl_pages`, `tbl_pages_types` 
															  WHERE `tbl_pages_types`.page_id = `tbl_pages`.id 
															  AND tbl_pages_types.`type` = 'index' 
															  LIMIT 1");
			}
			
			elseif(is_null($row)){

				$pathArr = preg_split('/\//', trim($this->_page, '/'), -1, PREG_SPLIT_NO_EMPTY);			
				$prevPage = NULL;

				$valid_page_path = array();
				$page_extra_bits = array();
	
				$handle = array_pop($pathArr);

				do{
					$path = implode('/', $pathArr);

					$sql = sprintf(
						"SELECT * FROM `tbl_pages` WHERE `path` %s AND `handle` = '%s' LIMIT 1",
						($path ? " = '".Symphony::Database()->cleanValue($path)."'" : 'IS NULL'), 
						Symphony::Database()->cleanValue($handle)
					);

					if($row = Symphony::Database()->fetchRow(0, $sql)){

						array_push($pathArr, $handle);
						$valid_page_path = $pathArr;

						break 1;	

					}else
						$page_extra_bits[] = $handle;
				
				}while($handle = array_pop($pathArr));
			
				if(empty($valid_page_path)) return;
			
				if(!$this->__isSchemaValid($row['params'], $page_extra_bits)) return;
			}

			##Process the extra URL params
			$url_params = preg_split('/\//', $row['params'], -1, PREG_SPLIT_NO_EMPTY);

			foreach($url_params as $var){
				$this->_env['url'][$var] = NULL;
			}
			
			if(is_array($page_extra_bits) && !empty($page_extra_bits)) $page_extra_bits = array_reverse($page_extra_bits);

			for($ii = 0; $ii < count($page_extra_bits); $ii++){
				$this->_env['url'][$url_params[$ii]] = str_replace(' ', '+', $page_extra_bits[$ii]);
			}
			
			if(!is_array($row) || empty($row)) return;

			$row['type'] = $this->__fetchPageTypes($row['id']);

			## Make sure the user has permission to access this page
			if(!$this->_Parent->isLoggedIn() && in_array('admin', $row['type'])){
				$row = Symphony::Database()->fetchRow(0, "SELECT `tbl_pages`.* FROM `tbl_pages`, `tbl_pages_types` 
															  WHERE `tbl_pages_types`.page_id = `tbl_pages`.id AND tbl_pages_types.`type` = '403' 
															  LIMIT 1");
				
				if(empty($row)){
					throw new SymphonyErrorPage( 
						__('Please <a href="%s">login</a> to view this page.', array(URL.'/symphony/login/')), 
						__('Forbidden'), 
						'error', 
						array('header' => 'HTTP/1.0 403 Forbidden')
					);
				}
				
				$row['type'] = $this->__fetchPageTypes($row['id']);
				
 			}	

			$row['filelocation'] = $this->resolvePageFileLocation($row['path'], $row['handle']);
	
			return $row;
				
		}
		
		private function __fetchPageTypes($page_id){
			return Symphony::Database()->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` WHERE `page_id` = '{$page_id}' ");
		}
		
		private function __isSchemaValid($schema, $bits){
	
			if (is_numeric($schema)) $schema = Symphony::Database()->fetchVar('params', 0, "SELECT `params` FROM `tbl_pages` WHERE `id` = '1' LIMIT 1");
			
			$schema_arr = preg_split('/\//', $schema, -1, PREG_SPLIT_NO_EMPTY);		
	
			return (count($schema_arr) >= count($bits));
		
		}

		private static function resolvePageFileLocation($path, $handle){
			return (PAGES . '/' . trim(str_replace('/', '_', $path . '_' . $handle), '_') . '.xsl');
		}
		
		private function __buildDatasourcePooledParamList($datasources){
			if(!is_array($datasources) || empty($datasources)) return array();
			
			$list = array();
			
			foreach($datasources as $handle){
				$rootelement = str_replace('_', '-', $handle);
				$list[] = '$ds-' . $rootelement;
			}
			
			return $list;		
		}
		
		private function __findDatasourceOrder($dependenciesList){
			if(!is_array($dependenciesList) || empty($dependenciesList)) return;
			
			$orderedList = array();
			$dsKeyArray = $this->__buildDatasourcePooledParamList(array_keys($dependenciesList));

			## 1. First do a cleanup of each dependency list, removing non-existant DS's and find 
			##    the ones that have no dependencies, removing them from the list
			foreach($dependenciesList as $handle => $dependencies){
				
				$dependenciesList[$handle] = @array_intersect($dsKeyArray, $dependencies);
				
				if(empty($dependenciesList[$handle])){ 
					unset($dependenciesList[$handle]);
					$orderedList[] = str_replace('_', '-', $handle);
				}
			}
			
			## 2. Iterate over the remaining DS's. Find if all their dependencies are
			##    in the $orderedList array. Keep iterating until all DS's are in that list
			##	  or there are circular dependencies (list doesn't change between iterations of the while loop)
			do{
				
				$last_count = count($dependenciesList);
				
				foreach($dependenciesList as $handle => $dependencies){					
					if(General::in_array_all(array_map(create_function('$a', "return str_replace('\$ds-', '', \$a);"), $dependencies), $orderedList)){
						$orderedList[] = str_replace('_', '-', $handle);
						unset($dependenciesList[$handle]);
					}		
				}
								
			}while(!empty($dependenciesList) && $last_count > count($dependenciesList));
			
			if(!empty($dependenciesList)) $orderedList = array_merge($orderedList, array_keys($dependenciesList));
			
			return array_map(create_function('$a', "return str_replace('-', '_', \$a);"), $orderedList);
			
		}
		
		public function processDatasources($datasources, &$wrapper, array $params = array()) {
			if (trim($datasources) == '') return;
			
			$datasources = preg_split('/,\s*/i', $datasources, -1, PREG_SPLIT_NO_EMPTY);
			$datasources = array_map('trim', $datasources);
			
			if (!is_array($datasources) || empty($datasources)) return;
			
			$this->_env['pool'] = $params;
			$pool = $params;
			$dependencies = array();
			
			foreach ($datasources as $handle) {
				$this->_Parent->Profiler->seed();
				
				$pool[$handle] =& $this->DatasourceManager->create($handle, NULL, false);
				$dependencies[$handle] = $pool[$handle]->getDependencies();
				
				unset($ds);
			}
			
			$dsOrder = $this->__findDatasourceOrder($dependencies);
			
			foreach ($dsOrder as $handle) {
				$this->_Parent->Profiler->seed();
				
				$dbstats = Symphony::Database()->getStatistics();
				$queries = $dbstats['queries'];
				
				$ds = $pool[$handle];
				$ds->processParameters(array('env' => $this->_env, 'param' => $this->_param));
				
				if ($xml = $ds->grab($this->_env['pool'])) {
					if (is_object($xml)) $wrapper->appendChild($xml);
					else $wrapper->setValue(
						$wrapper->getValue() . self::CRLF . '	' . trim($xml)
					);
				}
				
				$dbstats = Symphony::Database()->getStatistics();
				$queries = $dbstats['queries'] - $queries;
				
				$this->_Parent->Profiler->sample($handle, PROFILE_LAP, 'Datasource', $queries);
				
				unset($ds);
			}
		}
		
		private function __findEventOrder($a, $b){
			if ($a->priority() == $b->priority()) {
		        return 0;
		    }
		    return(($a->priority() > $b->priority()) ? -1 : 1);
		}
		
		private function processEvents($events, &$wrapper){
			
			####
			# Delegate: FrontendProcessEvents
			# Description: Manipulate the events array and event element wrapper
			# Global: Yes
			$this->ExtensionManager->notifyMembers(
				'FrontendProcessEvents', 
				'/frontend/', 
				array(
					'env' => $this->_env, 
					'events' => &$events, 
					'wrapper' => &$wrapper, 
					'page_data' => $this->_pageData
				)
			);
			#####

			if(strlen(trim($events)) > 0){			
				$events = preg_split('/,\s*/i', $events, -1, PREG_SPLIT_NO_EMPTY);
				$events = array_map('trim', $events);
			
				if(!is_array($events) || empty($events)) return;
				
				$pool = array();
				foreach($events as $handle){
					$pool[$handle] = $this->EventManager->create($handle, array('env' => $this->_env, 'param' => $this->_param));
				}
				
				uasort($pool, array($this, '__findEventOrder'));
				
				foreach($pool as $handle => $event){
					$this->_Parent->Profiler->seed();
					
					$dbstats = Symphony::Database()->getStatistics();
					$queries = $dbstats['queries'];
	
					if($xml = $event->load()):
				
						if(is_object($xml)) $wrapper->appendChild($xml);
						else $wrapper->setValue($wrapper->getValue() . self::CRLF . '	' . trim($xml));
										
					endif;
				
					$dbstats = Symphony::Database()->getStatistics();
					$queries = $dbstats['queries'] - $queries;

					$this->_Parent->Profiler->sample($handle, PROFILE_LAP, 'Event', $queries);
				
				}
			}
			
			####
			# Delegate: FrontendEventPostProcess
			# Description: Just after the page events have triggered. Provided with the XML object
			# Global: Yes
			$this->ExtensionManager->notifyMembers('FrontendEventPostProcess', '/frontend/', array('xml' => &$wrapper));
			
		}		
	}