<?php
	
	require_once EXTENSIONS . '/ds_template_navigation/lib/navigationdatasource.php';
	
	class DataSource%s extends UsersDataSource {
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
		
		public $dsParamROOTELEMENT = %s;
		public $dsParamORDER = %s;
		public $dsParamREDIRECTONEMPTY = %s;
		
		public function allowEditorToParse() {
			return true;
		}
	}
	
?>
