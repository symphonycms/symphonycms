<?php

	require_once('lib/staticxmldatasource.php');
		
	class Extension_DS_StaticXML extends Extension {
		public function about() {
			return array(
				'name'			=> 'Static XML',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'type'			=> array(
					'Data Source', 'Core'
				),
				'author'		=> array(
					'name'			=> 'Symphony Team',
					'website'		=> 'http://symphony-cms.com/',
					'email'			=> 'team@symphony-cms.com'
				),
				'provides'		=> array(
					'datasource_template'
				),
				'description'	=> 'Create data sources from an XML string.'
			);
		}
		
		public function prepare(array $data=NULL) {
			$datasource = new StaticXMLDataSource;

			if(!is_null($data)){
				if(isset($data['about']['name'])) $datasource->about()->name = $data['about']['name'];
				if(isset($data['xml'])) $datasource->parameters()->{'xml'} = $data['xml'];
			}
			
			return $datasource;
		}
		
		/*
		public function action($context = array()) {
			
			require_once TOOLKIT . '/class.xslproc.php';
			
			// Validate:
			$fields = $context['fields'];
			$errors = $context['errors'];
			$failed = $context['failed'];
			
			if (!isset($fields['static_xml']) or empty($fields['static_xml'])) {
				$errors['static_xml'] = 'Body must not be empty.';
				$failed = true;
			}
			
			$xml_errors = null;
			
			General::validateXML($fields['static_xml'], $xml_errors);

			if (!empty($xml_errors)) {
				$errors['static_xml'] = __('XML is invalid');
				
				foreach ($xml_errors as $error) {
					$errors['static_xml'] .= "<br />" . General::sanitize($error->message);
				}
				
				$failed = true;
			}
			
			$context['fields'] = $fields;
			$context['errors'] = $errors;
			$context['failed'] = $failed;
			
			// Send back template to save:
			$context['template_file'] = EXTENSIONS . '/ds_staticxml/templates/datasource.php';
			$context['template_data'] = array(
				Lang::createHandle($fields['about']['name']),
				$fields['static_xml']
			);
		}
		*/
		
		public function view(Datasource $datasource, SymphonyDOMElement &$wrapper, MessageStack $errors) {
			
			//	Essentials --------------------------------------------------------

				$fieldset = Symphony::Parent()->Page->createElement('fieldset');
				$fieldset->setAttribute('class', 'settings');
				$fieldset->appendChild(Symphony::Parent()->Page->createElement('legend', __('Essentials')));

				// Name:
				$label = Widget::Label(__('Name'));
				$input = Widget::Input('fields[about][name]', General::sanitize($datasource->about()->name));
				$label->appendChild($input);

				if (isset($errors->{'about::name'})) {
					$label = Widget::wrapFormElementWithError($label, $errors->{'about::name'});
				}

				$fieldset->appendChild($label);
			
				$label = Widget::Label(__('XML'));
				$input = Widget::Textarea('fields[xml]', $datasource->parameters()->{'xml'}, array(
					'rows' => '24',
					'cols' => '50',
					'class' => 'code'
				));
				$label->appendChild($input);
			
				if (isset($errors->{'xml'})) {
					$label = Widget::wrapFormElementWithError($label, $errors->{'xml'});
				}
			
				$fieldset->appendChild($label);
				
			$wrapper->appendChild($fieldset);
		}
	}
	