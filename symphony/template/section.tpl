<?php

	Class %s extends Section{
		public function __construct(){
			$this->_about = (object)array(
				'name' => %s,
				'handle' => %s,
				'navigation-group' => %s,
				'hidden' => %s,
				'guid' => %s
			);
		}
	}
	
	return '%1$s';