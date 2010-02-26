<?php
	
	class StaticXMLDataSource extends StaticXMLDataSource {
		public $dsParamROOTELEMENT = 'static-xml';
		
		public function grab() {
			$result = new XMLElement($this->dsParamROOTELEMENT);
			
			try {
				$result = $this->getStaticXML();
			}
			
			catch (FrontendPageNotFoundException $error) {
				FrontendPageNotFoundExceptionHandler::render($error);
			}
			
			catch (Exception $error) {
				$result->appendChild(new XMLElement(
					'error', General::sanitize($error->getMessage())
				));
			}	
			
			return $result;
		}
	}
	
?>