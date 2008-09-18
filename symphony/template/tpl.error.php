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
	$Page->addHeaderToPage('Symphony-Error-Type', 'generic');	
	if(isset($additional['header'])) $Page->addHeaderToPage($additional['header']);

	$Page->setTitle('Symphony &ndash; ' . $heading);
	
	$div = new XMLElement('div', NULL, array('id' => 'description'));
	$div->appendChild(new XMLElement('h1', $heading));
	$div->appendChild((is_object($errstr) ? $errstr : new XMLElement('p', trim($errstr))));
	$Page->Body->appendChild($div);

	print $Page->generate();

	exit();
	
?>