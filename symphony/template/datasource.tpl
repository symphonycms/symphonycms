<?php

	require_once(TOOLKIT . '/class.datasource.php');

	Class datasource<!-- CLASS NAME --> extends Datasource{

		<!-- VAR LIST -->

		<!-- FILTERS -->

		<!-- INCLUDED ELEMENTS -->

		public function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
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

		public function getSource(){
			return '<!-- SOURCE -->';
		}

		public function allowEditorToParse(){
			return true;
		}

		public function grab(&$param_pool=NULL){
			$result = new XMLElement($this->dsParamROOTELEMENT);

			try{
				<!-- GRAB -->
			}
			catch(FrontendPageNotFoundException $e){
				// Work around. This ensures the 404 page is displayed and
				// is not picked up by the default catch() statement below
				FrontendPageNotFoundExceptionHandler::render($e);
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', $e->getMessage()));
				return $result;
			}

			if($this->_force_empty_result) $result = $this->emptyXMLSet();

			<!-- EXTRAS -->

			return $result;
		}

	}
