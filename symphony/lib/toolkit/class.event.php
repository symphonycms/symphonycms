<?php
	/**
	 * @package toolkit
	 */

	 /**
	  * The iEvent interface provides two functions, about and load that
	  * Events can implement.
	  */
	interface iEvent{
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
		 *	XMLElement with the event result or void if the action did not match
		 */
		public function load();
	}

	/**
	 * The abstract Event classes defines some base methods that all Events inherit.
	 * It has one abstract method, `__trigger()`, which Events must implement. Event
	 * execution is determined based on an action (which maps to a form action
	 * from the Frontend). A load function determines whether this Event matches
	 * the action and if so, call the Event's `__trigger()` to run the logic. On every page
	 * load, all Event's that are attached to the page will have their load function's executed.
	 * Events are called in order of their priority and if there is more than one event
	 * with the same priority, in alphabetical order. An event class is saved through the
	 * Symphony backend, which uses an event template defined in `TEMPLATE . /event.tpl`
	 * Events implement the iEvent interface, which defines the load and about functions.
	 */
	abstract Class Event implements iEvent{

		/**
		 * The end-of-line constant.
		 * @var string
		 * @deprecated This will be removed in the next version of Symphony
		 */
		const CRLF = PHP_EOL;

		/**
		 * The class that initialised the Entry, usually the EntryManager
		 * @var mixed
		 */
		protected $_Parent;

		/**
		 * Represents High Priority, that this event should run first
		 * @var integer
		 */
		const kHIGH = 3;

		/**
		 * Represents Normal Priority, that this event should run normally.
		 * This is the default Event Priority
		 * @var integer
		 */
		const kNORMAL = 2;

		/**
		 * Represents High Priority, that this event should run last
		 * @var integer
		 */
		const kLOW = 1;

		/**
		 * Holds all the environment variables which include parameters set
		 * by other Datasources or Events.
		 * @var array
		 */
		protected $_env = array();

		/**
		 * The constructor for an Event sets `$this->_Parent` and `$this->_env`
		 * from the given parameters
		 *
		 * @param Administration $parent
		 *	The Administration object that this page has been created from
		 *	passed by reference
		 * @param array $env
		 *	The environment variables from the Frontend class which includes
		 *	any params set by Symphony or Datasources or by other Events
		 */
		public function __construct(&$parent, Array $env = array()){
			$this->_Parent = $parent;
			$this->_env = $env;
		}

		/**
		 * This function is required in order to edit it in the event editor page.
		 * Do not overload this function if you are creating a custom event. It is only
		 * used by the event editor.
		 *
		 * @return boolean
		 *	 True if event can be edited, false otherwise. Defaults to false
		 */
		public static function allowEditorToParse(){
			return false;
		}

		/**
		 * This function is required in order to identify what section this event is for. It
		 * is used in the event editor. It must remain intact. Do not overload this function in
		 * custom events.
		 *
		 * @return integer
		 */
		public static function getSource(){
			return NULL;
		}

		/**
		 * Returns a HTML string of documentation for the current event. By default this will be
		 * an example of a HTML form that can populate the chosen section. Documentation is shown
		 * in the Symphony backend when a user tries to edit an event but it's `allowEditorToParse()`
		 * returns `false`. If this is not implemented by the event, a default Symphony message will
		 * appear
		 *
		 * @return string
		 */
		public static function documentation() {
			return __('This event has been customised and cannot be viewed from Symphony.');
		}

		/**
		 * Priority determines Event importance and when it should be executed.
		 * The default priority for an event is `Event::kNORMAL`, with `Event::kHIGH` and
		 * `Event::kLOW` being the other available options. Events execution is `Event::HIGH`
		 * to `Event::kNORMAL` to `Event::kLOW`. If there are more than one event at the
		 * same priority level, they are sorted alphabetically by event handle and executed
		 * in that order for that priority.
		 *
		 * @see toolkit.FrontendPage#__findEventOrder()
		 * @return integer
		 *	The available constants are `Event::kLOW`, `Event::kNORMAL` and `Event::kHIGH`.
		 *	Defaults to `Event::kNORMAL`
		 */
		public function priority(){
			return self::kNORMAL;
		}

		/**
		 * This function must be included in an event. The purpose of this function
		 * is to define the logic of this particular event. It assumes that this event
		 * has already been triggered from the load function
		 *
		 * @return mixed
		 *	Typically returns an XMLElement with the event information (success
		 *	or failure included
		 */
		abstract protected function __trigger();
	}
