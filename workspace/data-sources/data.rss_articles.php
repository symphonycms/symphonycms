<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcerss_articles extends Datasource{
		
		var $dsParamROOTELEMENT = 'rss-articles';
		var $dsParamORDER = 'desc';
		var $dsParamLIMIT = '20';
		var $dsParamREDIRECTONEMPTY = 'no';
		var $dsParamSORT = 'date';
		var $dsParamSTARTPAGE = '1';
		var $dsParamHTMLENCODE = 'yes';
		
		var $dsParamFILTERS = array(
				'30' => 'yes',
		);
		
		var $dsParamINCLUDEDELEMENTS = array(
				'title',
				'body',
				'date'
		);

		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array();
		}
		
		function about(){
			return array(
					 'name' => 'RSS Articles',
					 'author' => array(
							'name' => 'Allen Chang',
							'website' => 'http://symphony.local:8888',
							'email' => 'allen@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-01-19T22:12:43+00:00');	
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