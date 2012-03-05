<?php

	include_once(TOOLKIT . '/class.htmlpage.php');

	$Page = new HTMLPage();

	$Page->Html->setElementStyle('html');

	$Page->Html->setDTD('<!DOCTYPE html>');
	$Page->Html->setAttribute('xml:lang', 'en');
	$Page->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
	$Page->addStylesheetToHead(SYMPHONY_URL . '/assets/symphony.basic.css', 'screen', 30);
	$Page->addStylesheetToHead(SYMPHONY_URL . '/assets/error.css', 'screen', 30);

	$Page->addHeaderToPage('Status', '500 Internal Server Error', 500);
	$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
	$Page->addHeaderToPage('Symphony-Error-Type', 'xslt');

	$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('XSLT Processing Error'))));

	$div = new XMLElement('div', NULL, array('id' => 'description'));
	$div->appendChild(new XMLElement('h1', __('XSLT Processing Error')));
	$div->appendChild(new XMLElement('p', __('This page could not be rendered due to the following XSLT processing errors.')));
	$Page->Body->appendChild($div);

	$ul = new XMLElement('ul', NULL, array('id' => 'details'));

	$errors_grouped = array();

	list($key, $val) = $e->getAdditional()->proc->getError(false, true);

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

	}while(list($key, $val) = $e->getAdditional()->proc->getError());

	$query_string = General::sanitize($Page->__buildQueryString());
	if(strlen(trim($query_string)) > 0) $query_string = "&amp;{$query_string}";
	foreach($errors_grouped as $group => $data){

		switch($group){

			case 'general':

				$dl = new XMLElement('dl');
				$dt = new XMLElement('dt', 
					'<a href="?debug' . $query_string .'" title="'
					. __('Show debug view') . '">' . __('Compile') . '</a>'
				);
				$dl->appendChild($dt);

				foreach($data as $e){
					$lines[] = $e['line'];

					$dd = new XMLElement('dd', $e['message']);

					$dl->appendChild($dd);
				}

				$li = new XMLElement('li');
				$li->appendChild(new XMLElement('h2', __('General')));
				$li->appendChild($dl);

				$ul->appendChild($li);

				break;


			case 'page':

				foreach($data as $filename => $errors){

					$dl = new XMLElement('dl');

					foreach($errors as $e){
						$dt = new XMLElement('dt', 
							'<a href="?debug=' .  $filename . $query_string . '#line-' . $e['line'] .'" title="'
							. __('Show debug view for %s', array($filename)) . '">' . __('Line %d', array($e['line'])) . '</a>'
						);
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
						$dt = new XMLElement('dt', 
							'<a href="?debug=u-' .  $filename . $query_string . '#line-' . $e['line'] .'" title="'
							. __('Show debug view for %s', array($filename)) . '">' . __('Line %d', array($e['line'])) . '</a>'
						);
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
					$dt = new XMLElement('dt', __('Line %d', array($e['line'])));
					$dt = new XMLElement('dt', 
						'<a href="?debug=xml' . $query_string . '#line-' . $e['line'] .'" title="'
						. __('Show debug view for XML') . '">' . __('Line %d', array($e['line'])) . '</a>'
					);
					$dd = new XMLElement('dd', $e['raw']['message']);
					$dl->appendChild($dt);
					$dl->appendChild($dd);
				}

				$li = new XMLElement('li');
				$li->appendChild(new XMLElement('h2', __('XML')));
				$li->appendChild($dl);

				$ul->appendChild($li);

				break;
		}
	}

	$Page->Body->appendChild($ul);

	print $Page->generate();

	exit;
