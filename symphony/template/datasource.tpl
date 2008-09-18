<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasource<!-- CLASS NAME --> extends Datasource{
		
		<!-- VAR LIST -->
		
		<!-- FILTERS -->
		
		<!-- INCLUDED ELEMENTS -->
		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array(<!-- DS DEPENDANCY LIST -->);
		}
		
		function about(){
			return array(
					 'name' => '<!-- NAME -->',
					 'author' => array(
							'name' => '<!-- AUTHOR NAME -->',
							'website' => '<!-- AUTHOR WEBSITE -->',
							'email' => '<!-- AUTHOR EMAIL -->'),
					 'version' => '<!-- VERSION -->',
					 'release-date' => '<!-- RELEASE DATE -->');	
		}
		
		function getSource(){
			return '<!-- SOURCE -->';
		}
		
		function allowEditorToParse(){
			return true;
		}
		
		function grab(&$param_pool){
			$result = NULL;
				
			<!-- GRAB -->
			
			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			
			<!-- EXTRAS -->
			
			return $result;
		}
	}

?>