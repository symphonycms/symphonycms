<?php

	/**
	 * @package interface
	 */
	/**
	 * This interface describes the minimum a new Event type needs to
	 * provide to be able to be used by Symphony
	 *
	 * @since Symphony 2.3.1
	 */
	Interface iEvent {

		/**
		 * Returns the human readable name of this event type. This is
		 * displayed in the event selection options.
		 *
		 * @return string
		 */
		public static function getName();

		/**
		 * Returns the absolute path to the template that this template will
		 * use to save instances of this event in the `events` folder.
		 *
		 * @return string
		 */
		public static function getTemplate();

		/**
		 * Returns the `__CLASS__` on the provided event, this is often
		 * used as a way to namespace settings in forms and provide a unique
		 * handle for this event type
		 *
		 * @return string
		 */
		public static function getSource();

		/**
		 * This function returns all the settings of the current event
		 * instance.
		 *
		 * @return array
		 *  An associative array of settings for this event where the
		 *  key is `getClass` and the value is an associative array of settings,
		 *  key being the setting name, value being, the value
		 */
		public function settings();

		/**
		 * This function is invoked by the Event Editor and allows this
		 * event to provide HTML so that it can be created or edited.
		 * It is expected that this function will also handle the display
		 * of error messages.
		 *
		 * @see settings()
		 * @param XMLElement $wrapper
		 *  An XMLElement for the HTML to be appended to. This is usually
		 *  `AdministrationPage->Form`.
		 * @param array $errors
		 *  If there are any errors, this variable will be an associative
		 *  array, key being the setting handle.
		 * @param array $settings
		 *  An associative array of settings. This may be null on create, but
		 *  will be populated with the event's settings on edit using
		 *  `settings()`.
		 * @param string $handle
		 *  If the event already exists (so it's being edited), the handle
		 *  of the event will be passed to this function.
		 * @return
		 */
		public static function buildEditor(XMLElement $wrapper, array &$errors = array(), array $settings = null, $handle = null);

		/**
		 * Given an array of settings, validate them, adding any errors
		 * to the `$errors` variable which is passed by reference. `$errors`
		 * should be formatted as an associative array
		 *
		 * @param array $settings
		 *  An associative array of settings
		 * @param array $errors
		 *  Passed as an empty array, can be populated with any validation errors
		 * @return boolean
		 *  True if the event is valid, false otherwise.
		 *  If false it is expected that `$errors` are populated.
		*/
		public static function validate(array &$settings, array &$errors);

		/**
		 * Given the settings and any existing event parameters, return the contents
		 * of this event that can be saved to the filesystem.
		 *
		 * @param array $settings
		 *  An associative array of settings for this event, where the key
		 *  is the name of the setting. These are user defined through the event
		 *  Editor.
		 * @param array $params
		 *  An associative array of parameters for this event, where the key
		 *  is the name of the parameter.
		 * @param string $template
		 *  The template file, which has already been altered by Symphony to remove
		 *  any named tokens (ie. `<!-- CLASS NAME -->`).
		 * @return string
		 *  The completed template, ready to be saved.
		 */
		public static function prepare(array $fields, array $parameters, $template);

		/**
		 * Return an associative array of meta information about this event such
		 * creation date, who created it and the name.
		 *
		 * @return array
		 */
		public static function about();

		/**
		 * The load functions determines whether an event will be executed or not
		 * by comparing the Event's action with the `$_POST` data. This function will
		 * be called every time a page is loaded that an event is attached too. If the
		 * action does exist, it typically calls the `__trigger()` method, otherwise void.
		 *
		 * @return mixed
		 *  XMLElement with the event result or void if the action did not match
		 */
		public function load();

		/**
		 * This function actually executes the event, and returns the result of the
		 * event as an `XMLElement` so that the `FrontendPage` class can add to
		 * a page's XML.
		 *
		 * @param array $param_pool
		 *  An associative array of parameters that have been evaluated prior to
		 *  this event's execution.
		 * @return XMLElement
		 *  This event should return an `XMLElement` object.
		 */
		public function execute();

	}