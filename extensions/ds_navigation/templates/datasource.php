<?php
	
	require_once EXTENSIONS . '/ds_navigation/lib/navigationdatasource.php';
	
	class DataSource%s extends NavigationDataSource {
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
		
		public function canRedirectOnEmpty() {
			return %s;
		}
		
		public function getFilters() {
			return %s;
		}
		
		public function getRequiredURLParam() {
			return %s;
		}
		
		public function getRootElement() {
			return %s;
		}
	}
	
?>
