<?php

	include_once(TOOLKIT . '/class.htmlpage.php');

	$Page = new HTMLPage();

	$Page->Html->setElementStyle('html');

	$Page->Html->setDTD('<!DOCTYPE html>');
	$Page->Html->setAttribute('xml:lang', 'en');
	$Page->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
	$Page->addStylesheetToHead(URL . '/symphony/assets/basic.css', 'screen', 30);
	$Page->addStylesheetToHead(URL . '/symphony/assets/error.css', 'screen', 30);

	$Page->addHeaderToPage('HTTP/1.0 500 Server Error');
	$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
	$Page->addHeaderToPage('Symphony-Error-Type', 'database');
	if(isset($e->getAdditional()->header)) $Page->addHeaderToPage($e->getAdditional()->header);
	
	$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Database Error'))));
	

	$div = new XMLElement('div', NULL, array('id' => 'description'));
	$div->appendChild(new XMLElement('h1', __('Symphony Database Error')));
	
	$div->appendChild(new XMLElement('p', $e->getAdditional()->message));

	$div->appendChild(new XMLElement('p', '<code>'.$e->getAdditional()->error['num'].': '.$e->getAdditional()->error['msg'].'</code>'));
	if(isset($e->getAdditional()->error['query'])) $div->appendChild(new XMLElement('p', '<code>'.$e->getAdditional()->error['query'].'</code>'));
	
	$Page->Body->appendChild($div);

	$output = $Page->generate();
	header(sprintf('Content-Length: %d', strlen($output)));
	echo $output;

	exit;
