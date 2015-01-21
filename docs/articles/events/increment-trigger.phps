<?php
protected function __trigger(){
	$result = new XMLElement(self::ROOTELEMENT);
	$entry_manager = new EntryManager(Symphony::Engine());
	$field_manager = $entry_manager->fieldManager;
	$status = Field::__OK__;

	// Check that we have an `$entry_id` set otherwise fail
	$entry_id = (is_numeric($_POST['id'])) ? $_POST['id'] : null;
	if(is_null($entry_id)) {
		$result->setAttribute('result', 'error');
		$result->appendChild(new XMLElement(
			'message', 'No Entry ID specified'
		));
		return $result;
	}

	// Retrieve our current Entry using the EntryManager
	// EntryManager returns an array of entries, so we'll want the first
	// one using `current()`.
	$entry = $entry_manager->fetch($entry_id);
	$entry = current($entry);

	// Get all the entry's data, which is an associative array of field ID => data
	$entry_data = $entry->getData();

	// Get a Field instance for the `count` field as we need to add data to it
	$count_field = $field_manager->fetch(
		$field_manager->fetchFieldIDFromElementName('count')
	);
	// We are using the `max` for readonly work, so just get the Field ID
	$max_field_id = $field_manager->fetchFieldIDFromElementName('max');

	// Get the current entry data
	$current_count = $entry_data[$count_field->get('id')]['value'];
	$max_count = $entry_data[$max_field_id]['value'];

	// 1. Check that `count` is less than our `max`, otherwise return
	if($current_count >= $max_count) {
		$result->setAttribute('result', 'error');
		$result->appendChild(new XMLElement(
			'message', 'Count has reached it\'s max'
		));
		return $result;
	}

	// 2. If `count` is less, increment `count` by 1
	$new_count = $current_count + 1;
	$entry->setData(
		$count_field->get('id'),
		// I'm deliberately ignoring the `$status` result here for simplicity
		// and just assuming everything will be ok. This means that if your
		// data is coming from the user you should be running it against
		// `Field->checkPostFieldData` first
		$count_field->processRawFieldData($new_count, $status)
	);

	// 3. If `count` now equals our `max`, send email an email
	if($new_count == $max_count) {
		// Get our Email field ID (readonly)
		$email_field_id = $field_manager->fetchFieldIDFromElementName('email');
		// Get our Email Response Field (writing)
		$email_response_field = $field_manager->fetch(
			$field_manager->fetchFieldIDFromElementName('email-response')
		);

		// Create our Email instance from the Core Email API
		$email = Email::create();
		$email_sent = true;

		// Try to send our email
		// For more Core Email API information, check michael-e's guide
		// https://github.com/michael-e/core-email-api-docs/blob/master/developer-documentation.markdown
		try{
			$email->recipients = array(
				$entry_data[$email_field_id]['value']
			);

			$email->text_plain = 'Surprise, our counter reached ' . $new_count . ', now you can dance!';
			$email->subject = 'Our Counter reached ' . $new_count . '!';

			$email->send();
		}
		// If something goes wrong, lets save the Exception to the Email Response
		catch(EmailException $ex) {
			$email_sent = false;
			$entry->setData(
				$email_response_field->get('id'),
				$email_response_field->processRawFieldData($ex->getMessage(), $status)
			);
		}

		// Everything went swell, save 'Email sent' to our Email Response field
		if($email_sent) {
			$entry->setData(
				$email_response_field->get('id'),
				$email_response_field->processRawFieldData('Email sent', $status)
			);
		}
	}

	// Update our Entry record, again keeping this very simple and not checking for errors
	if($entry->commit()) {
		$result->setAttribute('result', 'success');
		$result->appendChild(new XMLElement(
			'message', 'Count is at ' . $new_count
		));
	}
	else {
		$result->setAttribute('result', 'error');
	}

	// This line is essential for the Event XML to appear in your `?debug`
	return $result;
}
?>