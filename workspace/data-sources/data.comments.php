<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcecomments extends Datasource{
		
		var $dsParamROOTELEMENT = 'comments';
		var $dsParamORDER = 'asc';
		var $dsParamLIMIT = '999';
		var $dsParamREDIRECTONEMPTY = 'no';
		var $dsParamREQUIREDPARAM = '$ds-articles';
		var $dsParamSORT = 'date';
		var $dsParamSTARTPAGE = '1';
		
		var $dsParamFILTERS = array(
				'39' => '{$ds-articles}',
		);
		
		var $dsParamINCLUDEDELEMENTS = array(
				'author',
				'email',
				'website',
				'date',
				'comment',
				'authorised'
		);

		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array('$ds-articles');
		}
		
		function about(){
			return array(
					 'name' => 'Comments',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://symphony.local:8888',
							'email' => 'alistair@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-03-18T03:06:54+00:00');	
		}
		
		function getSource(){
			return '9';
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