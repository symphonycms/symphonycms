<?php
	
	require_once EXTENSIONS . '/ds_staticxml/lib/staticxmldatasource.php';

	Final Class DataSource%1$s extends StaticXMLDataSource {

		public function __construct(){
			parent::__construct();

			$this->_about = (object)array(
				'name'			=> %2$s,
				'author'		=> (object)array(
					'name'			=> %3$s,
					'website'		=> %4$s,
					'email'			=> %5$s
				),
				'version'		=> %6$s,
				'release-date'	=> %7$s
			);
		
			$this->_parameters = (object)array(
				'xml' => %8$s,
				'root-element' => %9$s
			);
			
		}
		
		public function allowEditorToParse(){
			return true;
		}
		
	}

	return 'DataSource%1$s';