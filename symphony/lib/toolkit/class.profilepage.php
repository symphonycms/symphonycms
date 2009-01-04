<?php

	require_once(TOOLKIT . '/class.htmlpage.php');

	Class ProfilePage extends HTMLPage{
		
		function __buildNavigation($page){		
			
			$ul = new XMLElement('ul', NULL, array('id' => 'nav'));
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('Edit', URL . '/symphony/blueprints/pages/edit/' . $page['id'] . '/'));
			$ul->appendChild($li);

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('Debug', '?debug'));
			$ul->appendChild($li);
			
			$ul->appendChild(new XMLElement('li', 'Profile'));

			return $ul;
		}
		
		private static function __appendNavigationItem($name, $link, $active=false){
			
			$li = new XMLElement('li');
			$anchor = Widget::Anchor($name,  $link);
			if($active == true){
				$anchor->setAttribute('class', 'active');
			}			
			$li->appendChild($anchor);
			
			return $li;
						
		}
				
		function generate($page, $profiler, $database){
			
			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
			
			$this->Html->setElementStyle('html');
			$this->Html->setDTD('<!DOCTYPE html>'); //PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"
			$this->Html->setAttribute('lang', __LANG__);
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addElementToHead(new XMLElement('link', NULL, array('rel' => 'icon', 'href' => URL.'/symphony/assets/images/bookmark.png', 'type' => 'image/png')), 20); 		
			$this->addStylesheetToHead(URL . '/symphony/assets/debug.css', 'screen', 40);
			$this->addElementToHead(new XMLElement('!--[if IE]><link rel="stylesheet" href="'.URL.'/symphony/assets/legacy.css" type="text/css"><![endif]--'), 50);
			$this->addScriptToHead(URL . '/symphony/assets/admin.js', 60);			
			
			$this->setTitle('Symphony &ndash; Page Profiler &ndash; ' . $page['title']);

			$h1 = new XMLElement('h1');
			$h1->appendChild(Widget::Anchor($page['title'], '.'));
			$this->Body->appendChild($h1);
			
			$this->Body->appendChild($this->__buildNavigation($page));
			
			$jump = new XMLElement('ul', NULL, array('id' => 'jump'));
			
			$records = array();
			
			$dbstats = $database->getStatistics();
	
			$profile_group = (strlen(trim($_GET['profile'])) == 0 ? 'general' : $_GET['profile']);	
			
			$records['general'] = $profiler->retrieveGroup('General');
			if(is_array($records['general']) && !empty($records['general'])){
				$jump->appendChild(self::__appendNavigationItem('General Details', '?profile=general', ($profile_group == 'general')));
			}
			
			$records['data-sources'] = $profiler->retrieveGroup('Datasource');	
			if(is_array($records['data-sources']) && !empty($records['data-sources'])){
				$jump->appendChild(self::__appendNavigationItem('Data Source Execution', '?profile=data-sources', ($profile_group == 'data-sources')));
			}

			$records['events'] = $profiler->retrieveGroup('Event');			
			if(is_array($records['events']) && !empty($records['events'])){
				$jump->appendChild(self::__appendNavigationItem('Event Execution', '?profile=events', ($profile_group == 'events')));
			}

			$jump->appendChild(self::__appendNavigationItem('Full Page Render Statistics', '?profile=render-statistics', ($profile_group == 'render-statistics')));
			
			if(is_array($dbstats['slow-queries']) && !empty($dbstats['slow-queries'])){
				$records['slow-queries'] = array();
				foreach($dbstats['slow-queries'] as $q) $records['slow-queries'][] = array($q['time'], $q['query'], NULL, NULL, false);

				$jump->appendChild(self::__appendNavigationItem('Slow Query Details', '?profile=slow-queries', ($profile_group == 'slow-queries')));
							
			}

			
			switch($profile_group){

				case 'general':
				
					$dl = new XMLElement('dl', NULL, array('id' => 'general'));
			
					foreach($records['general'] as $r){
						$dl->appendChild(new XMLElement('dt', $r[0]));
						$dl->appendChild(new XMLElement('dd', $r[1] . ' s'));				
					}
			
					$this->Body->appendChild($dl);
							
					break;
					###

			
				case 'data-sources':
							
					$ds_total = 0;

					$dl = new XMLElement('dl', NULL, array('id' => 'data-sources'));
			
					foreach($records['data-sources'] as $r){
						$dl->appendChild(new XMLElement('dt', $r[0]));
						$dl->appendChild(new XMLElement('dd', $r[1] . ' s'));
						$ds_total += $r[1];			
					}
			
					$this->Body->appendChild($dl);
									
					break;
					###
					
				case 'events':

					$event_total = 0;

					$dl = new XMLElement('dl', NULL, array('id' => 'events'));
			
					foreach($records['events'] as $r){
						$dl->appendChild(new XMLElement('dt', $r[0]));
						$dl->appendChild(new XMLElement('dd', $r[1] . ' s'));
						$event_total += $r[1];			
					}
			
					$this->Body->appendChild($dl);

					break;
					###
			
			
				case 'render-statistics':		
				
			
					$xml_generation = $profiler->retrieveByMessage('XML Generation');
					$xsl_transformation = $profiler->retrieveByMessage('XSLT Transformation');
			
					$records = array(
				
						array('Total Database Queries', $dbstats['queries'], NULL, NULL, false),
						array('Slow Queries (> 0.09s)', count($dbstats['slow-queries']), NULL, NULL, false),
						array('Total Time Spent on Queries', $dbstats['total-query-time']),
						array('Time Triggering All Events', $event_total),
						array('Time Running All Data Sources', $ds_total),
						array('XML Generation Function', $xml_generation[1]),
						array('XSLT Generation', $xsl_transformation[1]),
						array('Output Creation Time', $profiler->retrieveTotalRunningTime()),
					);

					$dl = new XMLElement('dl', NULL, array('id' => 'render-statistics'));
			
					foreach($records as $r){
						$dl->appendChild(new XMLElement('dt', $r[0]));
						$dl->appendChild(new XMLElement('dd', $r[1] . (isset($r[4]) && $r[4] == false ? '' : ' s')));
					}
			
					$this->Body->appendChild($dl);						
					break;
					###

				case 'slow-queries':

					$dl = new XMLElement('dl', NULL, array('id' => 'slow-queries'));
			
					foreach($records as $r){
						$dl->appendChild(new XMLElement('dt', $r[0]));
						$dl->appendChild(new XMLElement('dd', $r[1] . (isset($r[4]) && $r[4] == false ? '' : ' s')));
					}
			
					$this->Body->appendChild($dl);						
					break;
					###
			}
				
			$this->Body->appendChild($jump);
			
			return parent::generate();
						
		}		
		
	}
	
