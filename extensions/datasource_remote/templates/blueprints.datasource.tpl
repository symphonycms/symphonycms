<?php

	require_once(EXTENSIONS . '/datasource_remote/data-sources/datasource.remote.php');

	Class datasource<!-- CLASS NAME --> extends DatasourceRemote {

		public $dsParamROOTELEMENT = '%s';
		public $dsParamURL = '%s';
		public $dsParamFORMAT = '%s';
		public $dsParamXPATH = '%s';
		public $dsParamCACHE = %d;
		public $dsParamTIMEOUT = %d;

		<!-- NAMESPACES -->

		public function __construct($env=NULL, $process_params=true){
			parent::__construct($env, $process_params);
			$this->_dependencies = array(<!-- DS DEPENDENCY LIST -->);
		}

		public function about(){
			return array(
				'name' => '<!-- NAME -->',
				'author' => array(
					'name' => '<!-- AUTHOR NAME -->',
					'website' => '<!-- AUTHOR WEBSITE -->',
					'email' => '<!-- AUTHOR EMAIL -->'),
				'version' => '<!-- VERSION -->',
				'release-date' => '<!-- RELEASE DATE -->'
			);
		}

		public function allowEditorToParse(){
			return true;
		}

	}
