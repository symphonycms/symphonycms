<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	
	class ContentExtensionPublishfilteringFilters extends AdministrationPage {
		protected $_driver = null;
		
		public function __construct(&$parent){
			parent::__construct($parent);
			
			$this->_driver = $this->_Parent->ExtensionManager->create('publishfiltering');
		}
		
		public function __viewIndex() {
			header('content-type: text/javascript');
			
			$sm = new SectionManager($this->_Parent);
			$section_id = $sm->fetchIDFromHandle($_GET['section']);
			$section = $sm->fetch($section_id);
			$fields = array();
			
			foreach ($section->fetchFilterableFields() as $field) {
				$fields[$field->get('label')]['handle'] = $field->get('element_name');
				
				$html = new XMLElement('html');
				$field->displayPublishPanel($html);
				
				$dom = new DomDocument();
				$dom->loadXML($html->generate());
				
				$xpath = new DomXPath($dom);
				
				$count = 0;
				foreach($xpath->query("//*[name()='option'] | //*[name()='li']") as $option) {
					
					$value = '';
					
					if ($option->getAttribute('value')) {
						$value = $option->getAttribute('value');
					} else {
						$value = $option->nodeValue;
					}
					
					if ($value != '') {
						$fields[$field->get('label')]['options'][$count]['label'] = $option->nodeValue;
						$fields[$field->get('label')]['options'][$count]['value'] = $value;
						$count++;
					}
					
				}
				
				if ($field->get('type') == 'checkbox') {
					$fields[$field->get('label')]['options'][] = 'Yes';
					$fields[$field->get('label')]['options'][] = 'No';
				}
				
				
			}
			
			echo 'var filters = ', json_encode($fields), ";\n";
			echo "var filters_label = \"\";\n";
			echo 'var filters_apply = "', __('Apply'), "\";\n";
			echo 'var filters_clear = "', __('Clear'), "\";\n";
			exit;
		}
	}
	
?>