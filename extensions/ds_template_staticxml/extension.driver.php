<?php
	
	class Extension_DS_Template_StaticXML extends Extension {
		public function about() {
			return array(
				'name'			=> 'Data Source Template: Static XML',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description'	=> 'Create data sources from an XML string.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'NewDataSourceAction',
					'callback'	=> 'action'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'NewDataSourceForm',
					'callback'	=> 'form'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'EditDataSourceAction',
					'callback'	=> 'action'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'EditDataSourceForm',
					'callback'	=> 'form'
				)
			);
		}
		
		protected function getTemplate() {
			$file = EXTENSIONS . '/ds_template_staticxml/templates/datasource.php';
			
			if (!file_exists($file)) {
				throw new Exception(sprintf("Unable to find template '%s'.", $file));
			}
			
			return file_get_contents($file);
		}
		
		public function action($context = array()) {
			$template = $this->getTemplate();
			
			/*
			$context = array(
				'type'		=> '',			// Type of datasource
				'fields'	=> array(),		// Array of post data
				'errors'	=> null			// Instance of MessageStack to be filled with errors
			);
			*/
		}
		
		public function form($context = array()) {
			if ($context['type'] != 'static_xml') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement(
				'legend', __('Static XML')
			));
			
			$label = Widget::Label(__('Body'));
			$input = Widget::Textarea(
				'fields[static_xml]', 12, 50,
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