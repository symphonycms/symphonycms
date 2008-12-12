<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcearticle_images extends Datasource{
		
		public $dsParamROOTELEMENT = 'article-images';
		public $dsParamORDER = 'asc';
		public $dsParamLIMIT = '20';
		public $dsParamREDIRECTONEMPTY = 'no';
		public $dsParamSORT = 'system:id';
		public $dsParamSTARTPAGE = '1';
		
		public $dsParamFILTERS = array(
				'45' => '{$ds-homepage-article:$ds-articles:$ds-drafts}',
		);
		
		public $dsParamINCLUDEDELEMENTS = array(
				'image',
				'description'
		);

		public function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array('$ds-homepage-article', '$ds-articles', '$ds-drafts');
		}
		
		public function about(){
			return array(
					 'name' => 'Article Images',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://symphony.local:8888/2',
							'email' => 'alistair@symphony21.com'),
					 'version' => '1.0',
					 'release-date' => '2008-12-12T04:29:03+00:00');	
		}
		
		public function getSource(){
			return '10';
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

