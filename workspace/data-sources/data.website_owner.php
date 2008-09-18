<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcewebsite_owner extends Datasource{
		
		var $dsParamROOTELEMENT = 'website-owner';
		var $dsParamORDER = 'desc';
		var $dsParamLIMIT = '1';
		var $dsParamREDIRECTONEMPTY = 'no';
		var $dsParamSORT = 'id';
		var $dsParamSTARTPAGE = '1';
		
		var $dsParamFILTERS = array(
				'id' => '1',
		);
		
		var $dsParamINCLUDEDELEMENTS = array(
				'username',
				'name'
		);

		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array();
		}
		
		function about(){
			return array(
					 'name' => 'Website Owner',
					 'author' => array(
							'name' => 'Allen Chang',
							'website' => 'http://symphony.local:8888',
							'email' => 'allen@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-01-20T07:06:41+00:00');	
		}
		
		function getSource(){
			return 'authors';
		}
		
		function allowEditorToParse(){
			return true;
		}
		
		function grab(&$param_pool){
			$result = NULL;
				
			include(TOOLKIT . '/data-sources/datasource.author.php');
			
			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			return $result;
		}
	}

?>