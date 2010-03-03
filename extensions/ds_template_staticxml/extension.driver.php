<?php
	
	class Extension_DS_Template_StaticXML extends Extension {
		public function about() {
			return array(
				'name'			=> 'Static XML',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'type'			=> array(
					'Data Source Type'
				),
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'provides'		=> array(
					'datasource_template'
				),
				'description'	=> 'Create data sources from an XML string.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DataSourceFormPrepare',
					'callback'	=> 'prepare'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DataSourceFormAction',
					'callback'	=> 'action'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DataSourceFormView',
					'callback'	=> 'view'
				)
			);
		}
		
		public function prepare($context = array()) {
			if ($context['template'] != 'static_xml') return;
			
			$datasource = $context['datasource'];
			
			if ($datasource instanceof StaticXMLDataSource) {
				$context['fields']['static_xml'] = $datasource->getStaticXML();
			}
		}
		
		public function action($context = array()) {
			if ($context['template'] != 'static_xml') return;
			
			require_once TOOLKIT . '/class.xsltprocess.php';
			
			// Validate:
			$fields = $context['fields'];
			$errors = $context['errors'];
			$failed = $context['failed'];
			
			if (!isset($fields['static_xml']) or empty($fields['static_xml'])) {
				$errors['static_xml'] = 'Body must not be empty.';
				$failed = true;
			}
			
			$xml_errors = null;
			
			General::validateXML($fields['static_xml'], $xml_errors, false, new XsltProcess());

			if (!empty($xml_errors)) {
				$errors['static_xml'] = __('XML is invalid');
				
				foreach ($xml_errors as $error) {
					$errors['static_xml'] .= "<br />" . General::sanitize($error['message']);
				}
				
				$failed = true;
			}
			
			$context['fields'] = $fields;
			$context['errors'] = $errors;
			$context['failed'] = $failed;
			
			// Send back template to save:
			$context['template_file'] = EXTENSIONS . '/ds_template_staticxml/templates/datasource.php';
			$context['template_data'] = array(
				Lang::createHandle($fields['about']['name']),
				$fields['static_xml']
			);
		}
		
		public function view($context = array()) {
			if ($context['template'] != 'static_xml') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			
		//	Essentials --------------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			// Name:
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[about][name]', General::sanitize($fields['about']['name']));
			$label->appendChild($input);
			
			if (isset($errors['about']['name'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['about']['name']);
			}
			
			$fieldset->appendChild($label);
			
			$label = Widget::Label(__('Static XML'));
			$input = Widget::Textarea(
				'fields[static_xml]', 24, 50,
				General::sanitize($fields['static_xml'])
			);
			$input->setAttribute('class', 'code');
			$label->appendChild($input);
			
			if (isset($errors['static_xml'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['static_xml']);
			}
			
			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
		}
	}
	
?>
