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
				'timeout' => %8$d,
				'cache-lifetime' => %9$d,
				'namespaces' => %10$s,
				'url' => %11$s,
				'xpath' => %12$s,
				'root-element' => %13$s
			);
			
		}
		
		public function allowEditorToParse(){
			return true;
		}
		
	}

	return 'DataSource%1$s';