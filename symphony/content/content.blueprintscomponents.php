<?php

	/**
	 * @package content
	 */

	/**
	 * The Components page lists Datasources, Events and XSLT Utilities
	 * in this Symphony installation
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');

	Class contentBlueprintsComponents extends AdministrationPage{

		public function view(){
			$this->setPageType('forms');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Components'))));

			$this->appendSubheading(__('Components'));

			$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utilities = $utilities['filelist'];

			$ul = new XMLElement('ul');
			$ul->setAttribute('id', 'components');
			$ul->setAttribute('class', 'triple group');

			### EVENTS ###

			$EventManager = new EventManager($this->_Parent);
			$events = $EventManager->listAll();

			$li = new XMLElement('li');
			$h3 = new XMLElement('h3', '<span>' . __('Events') . '</span>');
			$h3->appendChild(Widget::Anchor(__('Create New'), SYMPHONY_URL . '/blueprints/events/new/', __('Create a new event'), 'create button', NULL, array('accesskey' => 'e')));
			$li->appendChild($h3);

			$list = new XMLElement('ul');
			$list->setSelfClosingTag(false);

			if(is_array($events) && !empty($events)){
				foreach($events as $e){
					$item = new XMLElement('li');
					$item->appendChild(Widget::Anchor($e['name'], SYMPHONY_URL . '/blueprints/events/'.($e['can_parse'] ? 'edit' : 'info').'/' . strtolower($e['handle']) . '/', 'event.' . $e['handle'] . '.php'));
					$list->appendChild($item);
				}
			}

			$li->appendChild($list);
			$ul->appendChild($li);

			######

			### DATASOURCES ###

			$DSManager = new DatasourceManager($this->_Parent);
			$datasources = $DSManager->listAll();

			$li = new XMLElement('li');

			$h3 = new XMLElement('h3', '<span>' . __('Data Sources') . '</span>');
			$h3->appendChild(Widget::Anchor(__('Create New'), SYMPHONY_URL . '/blueprints/datasources/new/', __('Create a new data source'), 'create button', NULL, array('accesskey' => 'd')));
			$li->appendChild($h3);

			$list = new XMLElement('ul');
			$list->setSelfClosingTag(false);

			if(is_array($datasources) && !empty($datasources)){
				foreach($datasources as $ds){

					$item = new XMLElement('li');

					if($ds['can_parse'])
						$item->appendChild(Widget::Anchor($ds['name'], SYMPHONY_URL . '/blueprints/datasources/edit/' . strtolower($ds['handle']) . '/', 'data.' . $ds['handle'] . '.php'));

					else{
						$item->appendChild(Widget::Anchor($ds['name'], SYMPHONY_URL . '/blueprints/datasources/info/' . strtolower($ds['handle']) . '/', 'data.' . $ds['handle'] . '.php'));
					}

					$list->appendChild($item);

				}
			}

			$li->appendChild($list);
			$ul->appendChild($li);

			######

			### UTILITIES ###
			$li = new XMLElement('li');

			$h3 = new XMLElement('h3', '<span>' . __('Utilities') . '</span>');
			$h3->appendChild(Widget::Anchor(__('Create New'), SYMPHONY_URL . '/blueprints/utilities/new/', __('Create a new utility'), 'create button', NULL, array('accesskey' => 'u')));
			$li->appendChild($h3);

			$list = new XMLElement('ul');
			$list->setSelfClosingTag(false);

			if(is_array($utilities) && !empty($utilities)){
				foreach($utilities as $u){
					$item = new XMLElement('li');
					$item->appendChild(Widget::Anchor($u, SYMPHONY_URL . '/blueprints/utilities/edit/' . str_replace('.xsl', '', $u) . '/'));
					$list->appendChild($item);
				}
			}

			$li->appendChild($list);
			$ul->appendChild($li);

			######

			$this->Form->appendChild($ul);

		}

	}
