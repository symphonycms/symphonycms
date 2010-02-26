<?php
	
	require_once EXTENSIONS . '/staticxmlds/lib/staticxmldatasource.php';
	
	class DataSource%s extends StaticXMLDataSource {
		public $dsParamROOTELEMENT = %s;
		
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
		
		public function getStaticXML() {
			return %s;
		}
	}
	
?>
