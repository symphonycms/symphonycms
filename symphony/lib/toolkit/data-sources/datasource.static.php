<?php

	include_once(TOOLKIT . '/class.xsltprocess.php');

	if(!General::validateXML($this->dsSTATIC, $errors, false, new XsltProcess)) {
		$result->appendChild(
			new XMLElement('error', __('XML is invalid.'))
		);

		$messages = new XMLElement('messages');
		foreach($errors as $e) {
			if(strlen(trim($e['message'])) == 0) continue;
			$messages->appendChild(new XMLElement('item', General::sanitize($e['message'])));
		}
		$result->appendChild($messages);
	}
	else {
		$result->setValue($this->dsSTATIC);
	}

?>