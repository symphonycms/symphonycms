<?php

	/**
	* Check logs and configuration
	*
	* Check for read and write access, getting, setting and removing
	*/
	class SymphonyTestLogsAndConfiguration extends UnitTestCase {
		public function setUp() {

		}

		public function tearDown() {

		}

		public function testConfiguration() {
			$conf = Symphony::Configuration();
			$value = uniqid();
			$group = 'test-' . $value;

			$this->assertTrue(file_exists(CONFIG));
			$this->assertTrue(is_readable(CONFIG));
			$this->assertTrue(is_writable(CONFIG));

			$this->assertEqual(null, $conf->set('test-value', $value, $group));
			$this->assertEqual($value, $conf->get('test-value', $group));
			$this->assertEqual(null, $conf->remove('test-value', $group));
		}

		public function testLogging() {
			$log = Symphony::Log();
			$message = 'test-log-message-' . uniqid();

			$this->assertTrue(file_exists(ACTIVITY_LOG));
			$this->assertTrue(is_readable(ACTIVITY_LOG));
			$this->assertTrue(is_writable(ACTIVITY_LOG));

			$log->writeToLog($message, true);

			$data = file_get_contents(ACTIVITY_LOG);

			$this->assertTrue(strpos($data, $message) !== -1);
		}
	}

?>