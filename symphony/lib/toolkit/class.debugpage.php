<?php

	require_once(TOOLKIT . '/class.htmlpage.php');
	require_once(TOOLKIT . '/class.bitterhtml.php');

	Class DebugPage extends HTMLPage{
		
		var $_full_utility_list;
		
		function __buildNavigation($page){		
			
			$ul = new XMLElement('ul', NULL, array('id' => 'nav'));
			
			$ul->appendChild(self::__appendNavigationItem('Edit', URL . '/symphony/blueprints/pages/edit/' . $page['id'] . '/'));
			
			$ul->appendChild(new XMLElement('li', __('Debug')));

			$ul->appendChild(self::__appendNavigationItem(__('Profile'), '?profile'));
			
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
		
		function __buildJump($page, $xsl, $active_link=NULL, $utilities=NULL){
			
			$ul = new XMLElement('ul', NULL, array('id' => 'jump'));
			
			$ul->appendChild(self::__appendNavigationItem(__('Params'), '?debug=params', ($active_link == 'params')));
			$ul->appendChild(self::__appendNavigationItem(__('XML'), '?debug=xml', ($active_link == 'xml' || is_null($active_link) || strlen(trim($active_link)) == 0)));
			
			$filename = basename($page['filelocation']);
			$li = self::__appendNavigationItem($filename, "?debug={$filename}", ($active_link == $filename));
			$xUtil = $this->__buildUtilityList($utilities, 1, $active_link);
			if(is_object($xUtil)) $li->appendChild($xUtil);
			$ul->appendChild($li);	

			
			$ul->appendChild(self::__appendNavigationItem(__('Result'), '?debug=result', ($active_link == 'result')));

			return $ul;
			
		}
		
		function __buildUtilityList($utilities, $level=1, $active_link=NULL){
			
			if(!is_array($utilities) || empty($utilities)) return;
			
			$ul = new XMLElement('ul');
			foreach($utilities as $u){
				
				$filename = basename($u);
				$item = self::__appendNavigationItem($filename, "?debug=u-{$filename}", ($active_link == "u-{$filename}"));
				
				$child_utilities = $this->__findUtilitiesInXSL(@file_get_contents(UTILITIES . '/' . $filename));
				
				if(is_array($child_utilities) && !empty($child_utilities)) $item->appendChild($this->__buildUtilityList($child_utilities, $level+1, $active_link));
				
				$ul->appendChild($item);
			}
			
			return $ul;
		
		}
		
		function __buildParams($params){
			
			if(!is_array($params) || empty($params)) return;
			
			$dl = new XMLElement('dl', NULL, array('id' => 'params'));
			
			foreach($params as $key => $value){				
				$dl->appendChild(new XMLElement('dt', "\${$key}"));
				$dl->appendChild(new XMLElement('dd', "'{$value}'"));
			}
			
			return $dl;
			
		}
		
		function __findUtilitiesInXSL($xsl){
			if($xsl == '') return;
			
			$utilities = NULL;

			if(preg_match_all('/<xsl:(import|include)\s*href="([^"]*)/i', $xsl, $matches)){
				$utilities = $matches[2];
			}
			
			if(!is_array($this->_full_utility_list)) $this->_full_utility_list = array();

			if(is_array($utilities) && !empty($utilities)) $this->_full_utility_list = array_merge($utilities, $this->_full_utility_list);
			
			return $utilities;
		}
		
		function __buildCodeBlock($code, $id){

			$line_numbering = new XMLElement('ol');

			$lang = new BitterLangHTML;

			$code = $lang->process(
				stripslashes($code), 4
			);
	
			$code = preg_replace(array('/^<span class="markup">/i', '/<\/span>$/i'), NULL, trim($code));
			
			$lines = preg_split('/[\r\n]+/i', $code);
			
			$value = NULL;
			
			foreach($lines as $n => $l){
				$value .= sprintf('<span id="line-%d"></span>%s', ($n + 1), $l) . General::CRLF;
				$line_numbering->appendChild(new XMLElement('li', sprintf('<a href="#line-%d">%1$d</a>', ($n + 1))));
			}
			
			$pre = new XMLElement('pre', sprintf('<code><span class="markup">%s </span></code>', trim($value)));
			
			return array($line_numbering, $pre);
			
		}
		
		function generate($page, $xml, $xsl, $output, $parameters){
			
			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
			
			$this->Html->setElementStyle('html');
			$this->Html->setDTD('<!DOCTYPE html>'); //PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"
			$this->Html->setAttribute('lang', __LANG__);
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addElementToHead(new XMLElement('link', NULL, array('rel' => 'icon', 'href' => URL.'/symphony/assets/images/bookmark.png', 'type' => 'image/png')), 20); 		
			$this->addStylesheetToHead(URL . '/symphony/assets/debug.css', 'screen', 40);
			$this->addElementToHead(new XMLElement('!--[if IE]><link rel="stylesheet" href="'.URL.'/symphony/assets/legacy.css" type="text/css"><![endif]--'), 50);
			$this->addScriptToHead(URL . '/symphony/assets/admin.js', 60);
			
			$this->setTitle(__('%s &ndash; %s &ndash; %s', array(__('Symphony'), __('Debug'), $page['title'])));
			
			$h1 = new XMLElement('h1');
			$h1->appendChild(Widget::Anchor($page['title'], '.'));
			$this->Body->appendChild($h1);
			
			$this->Body->appendChild($this->__buildNavigation($page));
			
			$utilities = $this->__findUtilitiesInXSL($xsl);

			$this->Body->appendChild($this->__buildJump($page, $xsl, $_GET['debug'], $utilities));
			
			if($_GET['debug'] == 'params'){
				$this->Body->appendChild($this->__buildParams($parameters));
			}
			
			elseif($_GET['debug'] == 'xml' || strlen(trim($_GET['debug'])) <= 0){
				$this->Body->appendChildArray($this->__buildCodeBlock($xml, 'xml'));
			}
			
			elseif($_GET['debug'] == 'result'){
				$this->Body->appendChildArray($this->__buildCodeBlock($output, 'result'));
			}
					
			else{
				
				if($_GET['debug'] == basename($page['filelocation'])){
					$this->Body->appendChildArray($this->__buildCodeBlock($xsl, basename($page['filelocation'])));
				}
				
				elseif($_GET['debug']{0} == 'u'){
					if(is_array($this->_full_utility_list) && !empty($this->_full_utility_list)){
						foreach($this->_full_utility_list as $u){
							
							if($_GET['debug'] != 'u-'.basename($u)) continue;
							
							$this->Body->appendChildArray($this->__buildCodeBlock(@file_get_contents(UTILITIES . '/' . basename($u)), 'u-'.basename($u)));
							break;
						}
					}
				}
			}

			return parent::generate();
						
		}
		
	}
	
