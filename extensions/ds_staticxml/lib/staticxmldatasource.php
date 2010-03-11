<?php
	
	class StaticXMLDataSource extends DataSource {
		public function getRootElement() {
			return 'static-xml';
		}
		
		public function getTemplate() {
			return 'static_xml';
		}
		
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