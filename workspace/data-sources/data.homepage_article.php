<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcehomepage_article extends Datasource{
		
		var $dsParamROOTELEMENT = 'homepage-article';
		var $dsParamORDER = 'desc';
		var $dsParamLIMIT = '1';
		var $dsParamREDIRECTONEMPTY = 'no';
		var $dsParamPARAMOUTPUT = 'system:id';
		var $dsParamSORT = 'date';
		var $dsParamSTARTPAGE = '1';
		
		var $dsParamFILTERS = array(
				'30' => 'yes',
		);
		
		var $dsParamINCLUDEDELEMENTS = array(
				'title',
				'body',
				'date',
				'categories'
		);

		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array();
		}
		
		function about(){
			return array(
					 'name' => 'Homepage Article',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://symphony.local:8888',
							'email' => 'alistair@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-03-13T01:02:24+00:00');	
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