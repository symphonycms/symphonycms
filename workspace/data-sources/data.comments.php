<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcecomments extends Datasource{
		
		public $dsParamROOTELEMENT = 'comments';
		public $dsParamORDER = 'asc';
		public $dsParamLIMIT = '999';
		public $dsParamREDIRECTONEMPTY = 'no';
		public $dsParamREQUIREDPARAM = '$ds-articles';
		public $dsParamSORT = 'date';
		public $dsParamSTARTPAGE = '1';
		
		public $dsParamFILTERS = array(
				'39' => '{$ds-articles}',
		);
		
		public $dsParamINCLUDEDELEMENTS = array(
				'author',
				'email',
				'website',
				'date',
				'comment',
				'authorised'
		);

		public function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array('$ds-articles');
		}
		
		public function about(){
			return array(
					 'name' => 'Comments',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://symphony.local:8888/2',
							'email' => 'alistair@symphony21.com'),
					 'version' => '1.0',
					 'release-date' => '2008-12-12T04:29:10+00:00');	
		}
		
		public function getSource(){
			return '9';
		}
		
		public function allowEditorToParse(){
			return true;
		}
		
		public function grab(&$param_pool){
			$result = new XMLElement($this->dsParamROOTELEMENT);
				
			try{
				include(TOOLKIT . '/data-sources/datasource.section.php');
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', $e->getMessage()));
				return $result;
			}	

			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			return $result;
		}
	}

