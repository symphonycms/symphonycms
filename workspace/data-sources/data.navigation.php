<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcenavigation extends Datasource{
		
		public $dsParamROOTELEMENT = 'navigation';
		public $dsParamORDER = 'desc';
		public $dsParamREDIRECTONEMPTY = 'no';
		public function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array();
		}
		
		public function about(){
			return array(
					 'name' => 'Navigation',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://symphony.local:8888/2',
							'email' => 'alistair@symphony21.com'),
					 'version' => '1.0',
					 'release-date' => '2008-12-12T04:29:18+00:00');	
		}
		
		public function getSource(){
			return 'navigation';
		}
		
		public function allowEditorToParse(){
			return true;
		}
		
		public function grab(&$param_pool){
			$result = new XMLElement($this->dsParamROOTELEMENT);
				
			try{
				include(TOOLKIT . '/data-sources/datasource.navigation.php');
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', $e->getMessage()));
				return $result;
			}	

			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			return $result;
		}
	}

