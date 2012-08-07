<?php

	include_once(TOOLKIT . '/class.htmlpage.php');

	$Page = new HTMLPage();

	$Page->Html->setElementStyle('html');

	$Page->Html->setDTD('<!DOCTYPE html>');
	$Page->Html->setAttribute('xml:lang', 'en');
	$Page->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
	$Page->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.css', 'screen', 30);
	$Page->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.frames.css', 'screen', 31);
	$Page->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.forms.css', 'screen', 32);

	$Page->addHeaderToPage('Status', '500 Internal Server Error', 500);
	$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
	$Page->addHeaderToPage('Symphony-Error-Type', 'missing-extension');

	$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $e->getHeading())));
	$Page->Body->setAttribute('id', 'error');

	$div = new XMLElement('div', NULL, array('class' => 'frame'));
	$div->appendChild(new XMLElement('h1', $e->getHeading()));
	$div->appendChild(
		new XMLElement('p', trim($e->getMessage()))
	);

	// Build the form, what it can do is yet to be determined
	$form = new XMLElement('form', null, array('action' => SYMPHONY_URL. '/system/extensions/', 'method' => 'post'));
	$form->appendChild(
		Widget::Input('extension-missing', 'yes', 'hidden')
	);
	$actions = new XMLElement('div');
	$actions->setAttribute('class', 'actions');

	$actions->appendChild(
		Widget::Input('action[delete]', __('Uninstall extension'), 'submit', array(
			'accesskey' => 'd',
			'class' => 'button delete',
			'style' => 'margin-left: 0;',
			'title' => __('Uninstall this extension'),
		)
	));

	$form->appendChild($actions);

	// Fetch extensions
	$extensions = new DirectoryIterator(EXTENSIONS);
	$matches = array();

	// Look for folders that could be the same as the desired extension
	foreach($extensions as $extension) {
		if($extension->isDot() || $extension->isFile()) continue;

		// If we find folders that are at least a 75% match, give the user the chance to rename
		similar_text($e->getAdditional()->name, $extension->getFilename(), $percent);
		if($percent > 75) $matches[$extension->getFilename()] = $percent;
	}

	// If we've found a similar folder
	if(!empty($matches) && $e->getAdditional()->rename_failed !== true) {
		arsort($matches, SORT_NUMERIC);
		$div->appendChild(
			new XMLElement('p', __('Often the cause of this error is a misnamed extension folder. You can try renaming %s to %s, or you can uninstall the extension to continue.', array(
				'<code>' . key($matches) . '</code>',
				'<code>' . $e->getAdditional()->name . '</code>'
			)))
		);

		$form->appendChild(
			Widget::Input('existing-folder', key($matches), 'hidden')
		);
		$form->appendChild(
			Widget::Input('new-folder', $e->getAdditional()->name, 'hidden')
		);

		$button = new XMLElement('button', __('Rename folder'));
		$button->setAttributeArray(array(
			'name' => 'action[rename]',
			'class' => 'button create',
			'type' => 'submit',
			'accesskey' => 's'
		));
		$actions->appendChild($button);
	}
	else if($e->getAdditional()->rename_failed) {
		$div->appendChild(
			new XMLElement('p', __('Sorry, but Symphony was unable to rename the folder. Try uninstalling the extension to continue.'))
		);
	}
	else {
		$div->appendChild(
			new XMLElement('p', __('You can try uninstalling the extension to continue, or you might want to ask on the forums'))
		);
	}

	$div->appendChild($form);

	$Page->Body->appendChild($div);

	$output = $Page->generate();
	header(sprintf('Content-Length: %d', strlen($output)));
	echo $output;

	exit;