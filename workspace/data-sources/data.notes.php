<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcenotes extends Datasource{
		
		var $dsParamROOTELEMENT = 'notes';
		var $dsParamORDER = 'desc';
		var $dsParamLIMIT = '5';
		var $dsParamREDIRECTONEMPTY = 'no';
		var $dsParamSORT = 'date';
		var $dsParamSTARTPAGE = '1';
		var $dsParamINCLUDEDELEMENTS = array(
				'date',
				'note'
		);

		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array();
		}
		
		function about(){
			return array(
					 'name' => 'Notes',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://symphony.local:8888',
							'email' => 'alistair@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-03-05T07:29:44+00:00');	
		}
		
		function getSource(){
			return '8';
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