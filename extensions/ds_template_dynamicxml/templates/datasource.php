<?php
	
	require_once EXTENSIONS . '/ds_template_dynamicxml/lib/dynamicxmldatasource.php';
	
	class DataSource%s extends DynamicXMLDataSource {
		public $dsParamROOTELEMENT = %s;
		public $dsParamURL = %s;
		public $dsParamXPATH = %s;
		public $dsParamCACHE = %d;
		
		public function about() {
			return array(
				'name'			=> %s,
				'author'		=> array(
					'name'			=> %s,
					'website'		=> %s,
					'email'			=> %s
				),
				'version'		=> %s,
				'release-date'	=> %s
			);	
		}
		
		public function allowEditorToParse() {
			return true;
		}
	}
	
?>
