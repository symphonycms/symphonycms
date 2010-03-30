<?php
	
	require_once EXTENSIONS . '/ds_dynamicxml/lib/dynamicxmldatasource.php';

	Final Class DataSource%1$s extends DynamicXMLDataSource {

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
				'cache-lifetime' => %8$d,
				'namespaces' => %9$s,
				'url' => %10$s,
				'xpath' => %11$s,
				'root-element' => %12$s
			);
			
		}
		
		public function allowEditorToParse(){
			return true;
		}
		
	}

	return 'DataSource%1$s';