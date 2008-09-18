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
		
		function generate($page, $profiler, $database){
			
			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
			
			$this->Html->setElementStyle('html');
			$this->Html->setDTD('<!DOCTYPE html>'); //PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"
			$this->Html->setAttribute('lang', 'en');
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
			
			## General
			$records = $profiler->retrieveGroup('General');
			
			if(is_array($records) && !empty($records)){
				$li = new XMLElement('li');
				$li->appendChild(Widget::Anchor('General Details',  '#general')); 
				$jump->appendChild($li);
				
				$dl = new XMLElement('dl', NULL, array('id' => 'general'));
				
				foreach($records as $r){
					$dl->appendChild(new XMLElement('dt', $r[0]));
					$dl->appendChild(new XMLElement('dd', $r[1] . ' s'));				
				}
				
				$this->Body->appendChild($dl);
			
			}
			###

			
			## Data sources
			$records = $profiler->retrieveGroup('Datasource');			
			$ds_total = 0;
			
			if(is_array($records) && !empty($records)){
				$li = new XMLElement('li');
				$li->appendChild(Widget::Anchor('Data Source Execution',  '#data-sources')); 
				$jump->appendChild($li);
				
				$dl = new XMLElement('dl', NULL, array('id' => 'data-sources'));
				
				foreach($records as $r){
					$dl->appendChild(new XMLElement('dt', $r[0]));
					$dl->appendChild(new XMLElement('dd', $r[1] . ' s'));
					$ds_total += $r[1];			
				}
				
				$this->Body->appendChild($dl);
			
			}			
			
			###

			## Events
			$records = $profiler->retrieveGroup('Event');			
			$event_total = 0;
			
			if(is_array($records) && !empty($records)){
				$li = new XMLElement('li');
				$li->appendChild(Widget::Anchor('Event Execution',  '#events')); 
				$jump->appendChild($li);
				
				$dl = new XMLElement('dl', NULL, array('id' => 'events'));
				
				foreach($records as $r){
					$dl->appendChild(new XMLElement('dt', $r[0]));
					$dl->appendChild(new XMLElement('dd', $r[1] . ' s'));
					$event_total += $r[1];			
				}
				
				$this->Body->appendChild($dl);
			
			}			
			
			###
			
			## Full Page Render Statistics		
			$dbstats = $database->getStatistics();
			
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
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('Full Page Render Statistics',  '#render-statistics')); 
			$jump->appendChild($li);
			
			$dl = new XMLElement('dl', NULL, array('id' => 'render-statistics'));
			
			foreach($records as $r){
				$dl->appendChild(new XMLElement('dt', $r[0]));
				$dl->appendChild(new XMLElement('dd', $r[1] . (isset($r[4]) && $r[4] == false ? '' : ' s')));
			}
			
			$this->Body->appendChild($dl);						
			
			###

			## Slow Queries
			if(is_array($dbstats['slow-queries']) && !empty($dbstats['slow-queries'])){
				
				$records = array();
				
				foreach($dbstats['slow-queries'] as $q) $records[] = array($q['time'], $q['query'], NULL, NULL, false);
			
				$li = new XMLElement('li');
				$li->appendChild(Widget::Anchor('Slow Query Details',  '#slow-queries')); 
				$jump->appendChild($li);
			
				$dl = new XMLElement('dl', NULL, array('id' => 'slow-queries'));
			
				foreach($records as $r){
					$dl->appendChild(new XMLElement('dt', $r[0]));
					$dl->appendChild(new XMLElement('dd', $r[1] . (isset($r[4]) && $r[4] == false ? '' : ' s')));
				}
			
				$this->Body->appendChild($dl);						
			}
			###
			
			$this->Body->appendChild($jump);
			
			return parent::generate();
						
		}		
		
	}
	
?>