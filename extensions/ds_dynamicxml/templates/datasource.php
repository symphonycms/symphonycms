<?php
	
	require_once EXTENSIONS . '/ds_dynamicxml/lib/dynamicxmldatasource.php';
	
	class DataSource%s extends DynamicXMLDataSource {
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
		
		public function getCacheTime() {
			return %d;
		}
		
		public function getNamespaces() {
			return %s;
		}
		
		public function getRootElement() {
			return %s;
		}
		
		public function getURL() {
			return %s;
		}
		
		public function getXPath() {
			return %s;
		}
	}
	
?>
