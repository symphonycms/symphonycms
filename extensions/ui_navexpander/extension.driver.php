<?php

	class Extension_UI_NavExpander extends Extension {
		public function about() {
			return array(
				'name'			=> 'Nav Expander',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-03-11',
				'type'			=> array(
					'Interface',
				),
				'author'		=> array(
					'name'			=> 'Symphony Team',
					'website'		=> 'http://symphony-cms.com/',
					'email'			=> 'team@symphony-cms.com'
				),
				'description'	=> 'Enables a toggle-able alternate nav state with all items displayed.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'loadStylesheet'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'AppendElementBelowView',
					'callback'	=> 'prependButton'
				)
			);
		}
		
		public function loadStylesheet($context) {
			
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/ui_navexpander/assets/expander.js', 240);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/ui_navexpander/assets/expander.css', 'screen', 250);
			
		}
		
		public function prependButton($context) {
			
			$backend_page = Administration::instance()->Page->Form->getChildren();
            $navigation = $backend_page[2];
            
            if($_COOKIE['nav'] == 'expanded') {
				$navigation->setAttribute('class', 'expanded');
			}

            $listitem = new XMLElement('li', ($_COOKIE['nav'] == 'expanded' ? '-' : '+'), array('id' => 'nav-expand'));
            
            $navigation->prependChild($listitem);
			
		}
		
	}
