<?php
	
	class Extension_DS_Template_DynamicXML extends Extension {
		public function about() {
			return array(
				'name'			=> 'Data Source Template: Dynamic XML',
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
				'description'	=> 'Create data sources from XML fetched over HTTP or FTP.'
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
			$file = EXTENSIONS . '/ds_template_dynamicxml/templates/datasource.php';
			
			if (!file_exists($file)) {
				throw new Exception(sprintf("Unable to find template '%s'.", $file));
			}
			
			return file_get_contents($file);
		}
		
		public function action($context = array()) {
			/*
			$context = array(
				'type'		=> '',			// Type of datasource
				'data'		=> array(),		// Array of post data
				'errors'	=> null			// Instance of MessageStack to be filled with errors
			);
			*/
		}
		
		public function form($context = array()) {
			if ($context['type'] != 'dynamic_xml') return;
			
			$fields = $context['fields'];
			$errors = $context['errors'];
			$wrapper = $context['wrapper'];
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual ' . __('dynamic_xml'));
			$fieldset->appendChild(new XMLElement('legend', __('Dynamic XML')));	
			$label = Widget::Label(__('URL'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][url]', General::sanitize($fields['dynamic_xml']['url'])));
			if(isset($this->_errors['dynamic_xml']['url'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['url']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', __('Use <code>{$param}</code> syntax to specify dynamic portions of the URL.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Namespace Declarations <i>Optional</i>'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			
			if(is_array($fields['dynamic_xml']['namespace']['name'])){
				
				$namespaces = $fields['dynamic_xml']['namespace']['name'];
				$uri = $fields['dynamic_xml']['namespace']['uri'];
				
				for($ii = 0; $ii < count($namespaces); $ii++){
					
					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('h4', 'Namespace'));

					$group = new XMLElement('div');
					$group->setAttribute('class', 'group');

					$label = Widget::Label(__('Name'));
					$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][name][]', General::sanitize($namespaces[$ii])));
					$group->appendChild($label);

					$label = Widget::Label(__('URI'));
					$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][uri][]', General::sanitize($uri[$ii])));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);					
				}
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->appendChild(new XMLElement('h4', __('Namespace')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][name][]'));
			$group->appendChild($label);
					
			$label = Widget::Label(__('URI'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][namespace][uri][]'));
			$group->appendChild($label);
			
			$li->appendChild($group);
			$ol->appendChild($li);
			
			$div->appendChild($ol);
			$fieldset->appendChild($div);
			
			$label = Widget::Label(__('Included Elements'));
			$label->appendChild(Widget::Input('fields[dynamic_xml][xpath]', General::sanitize($fields['dynamic_xml']['xpath'])));	
			if(isset($this->_errors['dynamic_xml']['xpath'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['xpath']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', __('Use an XPath expression to select which elements from the source XML to include.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
		
			$label = Widget::Label();
			$input = Widget::Input('fields[dynamic_xml][cache]', max(1, intval($fields['dynamic_xml']['cache'])), NULL, array('size' => '6'));
			$label->setValue('Update cached result every ' . $input->generate(false) . ' minutes');
			if(isset($this->_errors['dynamic_xml']['cache'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['dynamic_xml']['cache']));
			else $fieldset->appendChild($label);		

			$label = Widget::Label();
			$input = Widget::Input('fields[dynamic_xml][timeout]', max(1, intval($fields['dynamic_xml']['timeout'])), NULL, array('type' => 'hidden'));
			$label->appendChild($input);
			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
		}
	}
	
?>
