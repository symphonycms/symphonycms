<?php
	/**
	 * @package toolkit
	 */
	/**
	 * The Alert class drives the standard Symphony notices that
	 * appear at the top of the backend pages to alert the user of
	 * something. Their are three default alert styles, notice, error
	 * and success.
	 */
	Class Alert{

		/**
		 * Represents a notice, usually used for non blocking alerts,
		 * just to inform that user that something has happened and
		 * they need to aware of it
		 * @var string
		 */
		const NOTICE = 'notice';

		/**
		 * Represents an error, used when something has gone wrong during
		 * the previous action. It is blocking, in that the action has
		 * not completed successfully.
		 * @var string
		 */
		const ERROR = 'error';

		/**
		 * Represents success, used when an action has completed successfully
		 * with no errors
		 * @var string
		 */
		const SUCCESS = 'success';

		/**
		 * The message for this Alert, this text will be displayed to the user
		 * @var string
		 */
		private $message;

		/**
		 * The Alert constant to represent the style that this alert should
		 * take on. Defaults to `Alert::NOTICE`.
		 * @var string
		 */
		private $type;

		/**
		 * Constructor for the Alert class initialises some default
		 * variables
		 *
		 * @param string $message
		 *  This text will be displayed to the user
		 * @param string $type
		 *  The type of alert this is. Defaults to NOTICE, available
		 *  values are `Alert::NOTICE`, `Alert::ERROR`, `Alert::SUCCESS`
		 */
		public function __construct($message, $type = self::NOTICE){
			$this->message = $message;
			$this->type = $type;
		}

		/**
		 * Magic accessor function to get the private variables from
		 * an Alert instance
		 *
		 * @param string $name
		 *  The name of the variable, message or type are the valid
		 *  values
		 * @return string
		 */
		public function __get($name){
			return $this->{"$name"};
		}

		/**
		 * Magic setter function to set the private variables of
		 * an Alert instance
		 *
		 * @param string $name
		 *  The name of the variable, message or type are the valid values
		 * @param string $value
		 *  The value of the variable that is being set
		 */
		public function __set($name, $value){
			$this->{"$name"} = $value;
		}

		/**
		 * Magic isset function to check if a variable is set by ensuring
		 * it's not null
		 *
		 * @param string $name
		 *  The name of the variable to check, message or type are the valid
		 *  values
		 * @return boolean
		 *  True when set, false when not set.
		 */
		public function __isset($name){
			return isset($this->{"$name"});
		}

		/**
		 * Generates as XMLElement representation of this Alert
		 *
		 * @return XMLElement
		 */
		public function asXML(){

			$p = new XMLElement('p', $this->message);
			$p->setAttribute('class', 'notice');

			if($this->type != self::NOTICE){
				$p->setAttribute('class', 'notice ' . $this->type);
			}

			return $p;
		}

	}
