<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcearchive extends Datasource{
		
		public $dsParamROOTELEMENT = 'archive';
		public $dsParamORDER = 'desc';
		public $dsParamGROUP = '29';
		public $dsParamLIMIT = '100';
		public $dsParamREDIRECTONEMPTY = 'no';
		public $dsParamSORT = 'date';
		public $dsParamSTARTPAGE = '1';
		
		public $dsParamFILTERS = array(
				'29' => '{$year:$this-year}',
				'30' => 'yes',
		);
		
		public $dsParamINCLUDEDELEMENTS = array(
				'title',
				'date'
		);

		public function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array();
		}
		
		public function about(){
			return array(
					 'name' => 'Archive',
					 'author' => array(
							'name' => 'Admin Admin',
							'website' => 'http://localhost:8888/projects/legacy/symphony-2-beta',
							'email' => 'admin@admin.com'),
					 'version' => '1.0',
					 'release-date' => '2008-12-03T04:59:04+00:00');	
		}
		
		public function getSource(){
			return '6';
		}
		
		public function allowEditorToParse(){
			return true;
		}
		
		public function grab(&$param_pool){
			$result = NULL;
				
			include(TOOLKIT . '/data-sources/datasource.section.php');
			
			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			return $result;
		}
	}

