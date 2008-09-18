<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourcearticle_images extends Datasource{
		
		var $dsParamROOTELEMENT = 'article-images';
		var $dsParamORDER = 'asc';
		var $dsParamLIMIT = '20';
		var $dsParamREDIRECTONEMPTY = 'no';
		var $dsParamSORT = 'system:id';
		var $dsParamSTARTPAGE = '1';
		
		var $dsParamFILTERS = array(
				'45' => '{$ds-homepage-article:$ds-articles:$ds-drafts}',
		);
		
		var $dsParamINCLUDEDELEMENTS = array(
				'image',
				'description'
		);

		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array('$ds-homepage-article', '$ds-articles', '$ds-drafts');
		}
		
		function about(){
			return array(
					 'name' => 'Article Images',
					 'author' => array(
							'name' => 'Alistair Kearney',
							'website' => 'http://symphony.local:8888',
							'email' => 'alistair@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-03-18T01:02:17+00:00');	
		}
		
		function getSource(){
			return '10';
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