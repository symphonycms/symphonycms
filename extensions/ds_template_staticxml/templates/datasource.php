<?php
	
	require_once EXTENSIONS . '/ds_template_staticxml/lib/staticxmldatasource.php';
	
	class DataSource%s extends StaticXMLDataSource {
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
		
		public function getRootElement() {
			return %s;
		}
		
		public function getStaticXML() {
			return %s;
		}
	}
	
?>
