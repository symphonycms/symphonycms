<?php
	
	Class StaticXMLDataSource extends DataSource {
		
		public function __construct(){
			// Set Default Values
			$this->_about = new StdClass;
			$this->_parameters = (object)array(
				'root-element' => 'static-xml',
				'xml' => '<?xml version="1.0" encoding="UTF-8"?>'."\n<data>\n\t\n</data>"
			);
		}
		
		final public function type(){
			return 'ds_staticxml';
		}
		
		public function template(){
			return EXTENSIONS . '/ds_staticxml/templates/datasource.php';
		}

		public function save(MessageStack &$errors){
			$xsl_errors = new MessageStack;
			
			if(strlen(trim($this->parameters()->xml)) == 0){
				$errors->append('xml', __('This is a required field'));
			}
			
			elseif(!General::validateXML($this->parameters()->xml, $xsl_errors)){

				if(XSLProc::hasErrors()){
					$errors->append('xml', sprintf('XSLT specified is invalid. The following error was returned: "%s near line %s"', $xsl_errors[0]->message, $xsl_errors[0]->line));
				}
				else{
					$errors->append('xml', 'XSLT specified is invalid.');
				}
			}
			
			return parent::save($errors);
		}
		
		public function render(Register &$ParameterOutput){
			$result = Symphony::Parent()->Page->createElement($this->about()->{'root-element'});
			
			try {
				$result = $this->parameters()->xml;
			}
			
			catch (FrontendPageNotFoundException $error) {
				FrontendPageNotFoundExceptionHandler::render($error);
			}
			
			catch (Exception $error) {
				$result->appendChild(Symphony::Parent()->Page->createElement(
					'error', General::sanitize($error->getMessage())
				));
			}	
			
			return $result;
		}
	}
