<?php
	
	require_once EXTENSIONS . '/ds_template_users/lib/usersdatasource.php';
	
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
		
		public function allowEditorToParse() {
			return true;
		}
	}
	
?>
