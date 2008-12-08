<?php
	
	include_once(TOOLKIT . '/class.htmlpage.php');
	
	$Page = new HTMLPage();
	
	$Page->Html->setElementStyle('html');

	$Page->Html->setDTD('<!DOCTYPE html>');
	$Page->Html->setAttribute('xml:lang', 'en');
	$Page->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
	$Page->addElementToHead(new XMLElement('link', NULL, array('rel' => 'icon', 'href' => URL.'/symphony/assets/images/bookmark.png', 'type' => 'image/png')), 20); 
	$Page->addStylesheetToHead(URL . '/symphony/assets/error.css', 'screen', 30);
	$Page->addElementToHead(new XMLElement('!--[if IE]><link rel="stylesheet" href="'.URL.'/symphony/assets/legacy.css" type="text/css"><![endif]--'), 40);

	$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
	$Page->addHeaderToPage('Symphony-Error-Type', 'xslt');
	
	$Page->setTitle('Symphony &ndash; XSLT Processing Error');

	$div = new XMLElement('div', NULL, array('id' => 'description'));
	$div->appendChild(new XMLElement('h1', 'XSLT Processing Error'));
	$div->appendChild(new XMLElement('p', 'This page could not be rendered due to the following XSLT processing errors.'));
	$Page->Body->appendChild($div);

	$ul = new XMLElement('ul', NULL, array('id' => 'details'));

	$errors_grouped = array();

	list($key, $val) = $additional['proc']->getError(false, true);

	do{
		
		if(preg_match('/^loadXML\(\)/i', $val['message']) && preg_match_all('/line:\s+(\d+)/i', $val['message'], $matches))
			$errors_grouped['xml'][] = array('line'=>$matches[1][0], 'raw'=>$val);
		
		elseif(preg_match_all('/pages\/([^.\/]+\.xsl)\s+line\s+(\d+)/i', $val['message'], $matches))
				$errors_grouped['page'][$matches[1][0]][] = array('line'=>$matches[2][0], 'raw'=>$val);
			
		elseif(preg_match_all('/utilities\/([^.\/]+\.xsl)\s+line\s+(\d+)/i', $val['message'], $matches))
			$errors_grouped['utility'][$matches[1][0]][] = array('line'=>$matches[2][0], 'raw'=>$val);
				
		else{
			$errors_grouped['general'][] = $val;
		}
				
	}while(list($key, $val) = $additional['proc']->getError());

	foreach($errors_grouped as $group => $data){
			
		switch($group){
			
			case 'general':
			
				$dl = new XMLElement('dl');
				$dt = new XMLElement('dt', '<a href="?debug" title="Show debug view">Compile</a>');
				$dl->appendChild($dt);
				
				foreach($data as $e){
					$lines[] = $e['line'];

					$dd = new XMLElement('dd', $e['message']);
					
					$dl->appendChild($dd);
				}
			
				$li = new XMLElement('li');
				$li->appendChild(new XMLElement('h2', 'General'));					
				$li->appendChild($dl);
			
				$ul->appendChild($li);
			
				break;
			
			
			case 'page':
			
				foreach($data as $filename => $errors){
					
					$dl = new XMLElement('dl');
					
					foreach($errors as $e){
						$dt = new XMLElement('dt', sprintf('<a href="%s" title="Show debug view for %s">Line %d</a>', "?debug={$filename}#line-".$e['line'], $filename, $e['line']));
						$dd = new XMLElement('dd', $e['raw']['message']);
						$dl->appendChild($dt);
						$dl->appendChild($dd);
					}
					
					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('h2', $filename));
					
					$li->appendChild($dl);
					
					$ul->appendChild($li);
				}
						
				break;
				
			case 'utility':
			
				foreach($data as $filename => $errors){
					
					$dl = new XMLElement('dl');
				
					foreach($errors as $e){					
						$dt = new XMLElement('dt', sprintf('<a href="%s" title="Show debug view for %s">Line %d</a>', "?debug=u-{$filename}#line-".$e['line'], $filename, $e['line']));						
						$dd = new XMLElement('dd', $e['raw']['message']);
						$dl->appendChild($dt);
						$dl->appendChild($dd);
					}
				
					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('h2', $filename));
					$li->appendChild($dl);
				
					$ul->appendChild($li);
				}	
						
				break;
				
			case 'xml':
		
				$dl = new XMLElement('dl');
		
				foreach($data as $e){
					$dt = new XMLElement('dt', 'Line ' . $e['line']);
					$dt = new XMLElement('dt', sprintf('<a href="?debug=xml#line-%1$d" title="Show debug view for XML">Line %1$d</a>', $e['line']));
					$dd = new XMLElement('dd', $e['raw']['message']);
					$dl->appendChild($dt);
					$dl->appendChild($dd);
				}
		
				$li = new XMLElement('li');
				$li->appendChild(new XMLElement('h2', 'XML'));	
				$li->appendChild($dl);
		
				$ul->appendChild($li);
				
				break;
		}
		
		
	}

	$Page->Body->appendChild($ul);

	print $Page->generate();

	exit();

