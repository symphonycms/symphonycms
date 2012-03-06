<?php

	include_once(TOOLKIT . '/class.htmlpage.php');

	$Page = new HTMLPage();

	$Page->Html->setElementStyle('html');

	$Page->Html->setDTD('<!DOCTYPE html>');
	$Page->Html->setAttribute('xml:lang', 'en');
	$Page->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
	$Page->addStylesheetToHead(SYMPHONY_URL . '/assets/symphony.basic.css', 'screen', 30);
	$Page->addStylesheetToHead(SYMPHONY_URL . '/assets/symphony.frames.css', 'screen', 31);

	$Page->addHeaderToPage('Status', '500 Internal Server Error', 500);
	$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
	$Page->addHeaderToPage('Symphony-Error-Type', 'xslt');

	$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('XSLT Processing Error'))));
	$Page->Body->setAttribute('id', 'fatalerror');

	$div = new XMLElement('div', NULL, array('class' => 'frame'));
	$ul = new XMLElement('ul');
	$li = new XMLElement('li');
	$li->appendChild(new XMLElement('h1', __('XSLT Processing Error')));
	$li->appendChild(new XMLElement('p', __('This page could not be rendered due to the following XSLT processing errors.')));
	$ul->appendChild($li);

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
				$error = new XMLElement('li', '<header>' . __('General') . '<a class="button" href="?debug' . $query_string .'" title="' . __('Show debug view') . '">' . __('Debug') . '</a></header>');
				$content = new XMLElement('div', null, array('class' => 'content'));
				$list = new XMLElement('ul');

				foreach($data as $index => $e){
					
					// Highlight error
					$class = array();
					if(strpos($data[$index + 1]['message'], '^') !== false) {
						$class = array('class' => 'error');
					}
	
					if(strpos($e['message'], '^') === false) {
						$parts = explode('(): ', $e['message']);

						// Function
						if(strpos($data[$index - 1]['message'], $parts[0]) === false) {
							$list->appendChild(
								new XMLElement(
									'li', 
									'<code><em>' . $parts[0] . '():</em></code>'
								)
							);
						}
						
						// Error
						if(!empty($class)) {
							$position = explode('(): ', $data[$index + 1]['message']);
							$length = max(0, strlen($position[1]) - 2);
							$list->appendChild(
								new XMLElement(
									'li', 
									'<code>&#160;&#160;&#160;&#160;' . str_replace(' ', '&#160;', htmlspecialchars(substr($parts[1], 0, $length)) . '<b>' . htmlspecialchars(substr($parts[1], $length, 1)) . '</b>' . htmlspecialchars(substr($parts[1], $length + 1))) . '</code>', 
									$class
								)
							);
						}
						
						// Message
						else {
							foreach(explode(' : ', $parts[1]) as $message) {
								$list->appendChild(
									new XMLElement(
										'li', 
										'<code>&#160;&#160;&#160;&#160;' . str_replace(' ', '&#160;', $message) . '</code>', 
										$class
									)
								);
							}
						}
					}
				}

				$content->appendChild($list);
				$error->appendChild($content);
				$ul->appendChild($error);

				break;


			case 'page':
				foreach($data as $filename => $errors){
					$error = new XMLElement('li', '<header>' . $filename . '<a class="button" href="?debug=/workspace/pages/' .  $filename . $query_string .'" title="' . __('Show debug view') . '">' . __('Debug') . '</a></header>');
					$content = new XMLElement('div', null, array('class' => 'content'));
					$list = new XMLElement('ul');

					foreach($errors as $e){
						$parts = explode('(): ', $e['raw']['message']);
					
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code><em>' . $parts[0] . '</em></code>'
							)
						);
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code>&#160;&#160;&#160;&#160;' . $parts[1] . '</code>'
							)
						);
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code>&#160;&#160;&#160;&#160;<a href="?debug=/workspace/pages/' .  $filename . $query_string . '#line-' . $e['line'] .'" title="'
							. __('Show debug view for %s', array($filename)) . '">' . __('Show line %d in debug view', array($e['line'])) . '</a></code>'
							)
						);
					}

					$content->appendChild($list);
					$error->appendChild($content);
					$ul->appendChild($error);
				}

				break;

			case 'utility':
				foreach($data as $filename => $errors){
					$error = new XMLElement('li', '<header>' . $filename . '<a class="button" href="?debug=/workspace/utilities/' .  $filename . $query_string .'" title="' . __('Show debug view') . '">' . __('Debug') . '</a></header>');
					$content = new XMLElement('div', null, array('class' => 'content'));
					$list = new XMLElement('ul');

					foreach($errors as $e){
						$parts = explode('(): ', $e['raw']['message']);
					
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code><em>' . $parts[0] . '</em></code>'
							)
						);
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code>&#160;&#160;&#160;&#160;' . $parts[1] . '</code>'
							)
						);
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code>&#160;&#160;&#160;&#160;<a href="?debug=/workspace/utilities/' .  $filename . $query_string . '#line-' . $e['line'] .'" title="'
							. __('Show debug view for %s', array($filename)) . '">' . __('Show line %d in debug view', array($e['line'])) . '</a></code>'
							)
						);
					}

					$content->appendChild($list);
					$error->appendChild($content);
					$ul->appendChild($error);
				}

				break;

			case 'xml':
				foreach($data as $filename => $errors){
					$error = new XMLElement('li', '<header>XML <a class="button" href="?debug=xml' . $query_string .'" title="' . __('Show debug view') . '">' . __('Debug') . '</a></header>');
					$content = new XMLElement('div', null, array('class' => 'content'));
					$list = new XMLElement('ul');

					foreach($errors as $e){
						$parts = explode('(): ', $e['raw']['message']);
					
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code><em>' . $parts[0] . '</em></code>'
							)
						);
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code>&#160;&#160;&#160;&#160;' . $parts[1] . '</code>'
							)
						);
						$list->appendChild(
							new XMLElement(
								'li', 
								'<code>&#160;&#160;&#160;&#160;<a href="?debug=xml' . $query_string . '#line-' . $e['line'] .'" title="'
							. __('Show debug view for XML', array($filename)) . '">' . __('Show line %d in debug view', array($e['line'])) . '</a></code>'
							)
						);
					}

					$content->appendChild($list);
					$error->appendChild($content);
					$ul->appendChild($error);
				}

				break;
		}
	}

	$div->appendChild($ul);
	$Page->Body->appendChild($div);

	print $Page->generate();

	exit;
