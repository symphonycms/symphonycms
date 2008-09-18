<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcenavigation extends Datasource{
		
		var $dsParamROOTELEMENT = 'navigation';
		var $dsParamORDER = 'desc';
		var $dsParamREDIRECTONEMPTY = 'no';
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_cache_sections = array();
			$this->_dependencies = array();
		}
		
		function about(){
			return array(
					 'name' => 'Navigation',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://symphony.local:8888',
							'email' => 'alistair@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-01-16T08:19:45+00:00');	
		}
		
		function getSource(){
			return 'navigation';
		}
		
		function allowEditorToParse(){
			return true;
		}
		
		function grab(&$param_pool){
			$result = NULL;
				
			include(TOOLKIT . '/data-sources/datasource.navigation.php');
			
			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			return $result;
		}
	}

?>