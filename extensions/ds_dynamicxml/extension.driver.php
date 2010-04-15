<?php
	require_once('lib/dynamicxmldatasource.php');

	class Extension_DS_DynamicXML extends Extension {
		public function about() {
			return array(
				'name'			=> 'Dynamic XML',
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
				'description'	=> 'Create data sources from XML fetched over HTTP or FTP.'
			);
		}

		public function prepare(array $data=NULL) {
			$datasource = new DynamicXMLDataSource;

			if(!is_null($data)){
				if(isset($data['about']['name'])) $datasource->about()->name = $data['about']['name'];

				if(isset($data['namespaces']) && is_array($data['namespaces'])){
					foreach($data['namespaces']['name'] as $index => $name){
						$datasource->parameters()->namespaces[$index] = array('uri' => $data['namespaces']['uri'][$index], 'name' => $name);
					}
				}

				if(isset($data['url'])) $datasource->parameters()->url = $data['url'];
				if(isset($data['xpath'])) $datasource->parameters()->xpath = $data['xpath'];
				if(isset($data['cache-lifetime'])) $datasource->parameters()->{'cache-lifetime'} = $data['cache-lifetime'];
			}

			return $datasource;
		}

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
			$wrapper->appendChild($fieldset);

		//	Source ------------------------------------------------------------

			$fieldset = Symphony::Parent()->Page->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(Symphony::Parent()->Page->createElement('legend', __('Source')));
			$label = Widget::Label(__('URL'));
			$label->appendChild(Widget::Input(
				'fields[url]', General::sanitize($datasource->parameters()->url)
			));

			if (isset($errors->url)) {
				$label = Widget::wrapFormElementWithError($label, $errors->url);
			}

			$fieldset->appendChild($label);

			$p = Symphony::Parent()->Page->createElement('p', __('Use <code>{$param}</code> syntax to specify dynamic portions of the URL.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			$div = Symphony::Parent()->Page->createElement('div');
			$h3 = Symphony::Parent()->Page->createElement('h3', __('Namespace Declarations'), array('class' => 'label'));
			$h3->appendChild(Symphony::Parent()->Page->createElement('i', 'Optional'));
			$div->appendChild($h3);

			$ol = Symphony::Parent()->Page->createElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');

			if(is_array($datasource->parameters()->namespaces)){
				foreach($datasource->parameters()->namespaces as $index => $namespace){

					$li = Symphony::Parent()->Page->createElement('li');
					$li->appendChild(Symphony::Parent()->Page->createElement('h4', 'Namespace'));

					$group = Symphony::Parent()->Page->createElement('div');
					$group->setAttribute('class', 'group');

					$label = Widget::Label(__('Name'));
					$label->appendChild(Widget::Input("fields[namespaces][name][{$index}]", General::sanitize($namespace['name'])));
					$group->appendChild($label);

					$label = Widget::Label(__('URI'));
					$label->appendChild(Widget::Input("fields[namespaces][uri][{$index}]", General::sanitize($namespace['uri'])));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);
				}
			}

			$li = Symphony::Parent()->Page->createElement('li');
			$li->setAttribute('class', 'template');
			$li->appendChild(Symphony::Parent()->Page->createElement('h4', __('Namespace')));

			$group = Symphony::Parent()->Page->createElement('div');
			$group->setAttribute('class', 'group');

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[namespaces][name][]'));
			$group->appendChild($label);

			$label = Widget::Label(__('URI'));
			$label->appendChild(Widget::Input('fields[namespaces][uri][]'));
			$group->appendChild($label);

			$li->appendChild($group);
			$ol->appendChild($li);

			$div->appendChild($ol);
			$fieldset->appendChild($div);

			$fieldset->appendChild(Widget::Input('automatically_discover_namespaces', 'no', 'hidden'));

			// TODO: Import this feature from the XML importer extension.

			/*
			$input = Widget::Input('automatically_discover_namespaces', 'yes', 'checkbox');
			$label = Widget::Label(__('%s Automatically discover namespaces', array($input->generate())));
			$fieldset->appendChild($label);

			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Search the source document for namespaces, any that it finds will be added to the declarations above.'));
			$fieldset->appendChild($help);
			*/

			$label = Widget::Label(__('Included Elements'));
			$label->appendChild(Widget::Input('fields[xpath]', General::sanitize($datasource->parameters()->xpath)));

			if(isset($errors->xpath)){
				$label = Widget::wrapFormElementWithError($label, $errors->xpath);
			}

			$fieldset->appendChild($label);

			$help = Symphony::Parent()->Page->createElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Use an XPath expression to select which elements from the source XML to include.'));
			$fieldset->appendChild($help);

			$input = Widget::Input('fields[cache-lifetime]', max(0, intval($datasource->parameters()->{'cache-lifetime'})));
			$input->setAttribute('size', 6);

			$label = Widget::Label(__('Update cached result every %s minutes', array((string)$input)));

			if(isset($errors->{'cache-lifetime'})){
				$label = Widget::wrapFormElementWithError($label, $errors->{'cache-lifetime'});
			}

			$fieldset->appendChild($label);
			$wrapper->appendChild($fieldset);
		}
	}