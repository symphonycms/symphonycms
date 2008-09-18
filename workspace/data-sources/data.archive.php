<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcearchive extends Datasource{
		
		var $dsParamROOTELEMENT = 'archive';
		var $dsParamORDER = 'desc';
		var $dsParamGROUP = '29';
		var $dsParamLIMIT = '100';
		var $dsParamREDIRECTONEMPTY = 'no';
		var $dsParamSORT = 'date';
		var $dsParamSTARTPAGE = '1';
		
		var $dsParamFILTERS = array(
				'29' => '{$year:$this-year}',
				'30' => 'yes',
		);
		
		var $dsParamINCLUDEDELEMENTS = array(
				'title',
				'date'
		);

		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array();
		}
		
		function about(){
			return array(
					 'name' => 'Archive',
					 'author' => array(
							'name' => 'Allen Chang',
							'website' => 'http://symphony.local:8888',
							'email' => 'allen@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-01-22T07:03:10+00:00');	
		}
		
		function getSource(){
			return '6';
		}
		
		function allowEditorToParse(){
			return true;
		}
		
		function grab(&$param_pool){
			$result = NULL;
				
			include(TOOLKIT . '/data-sources/datasource.section.php');
			
			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			return $result;
		}
	}

?>