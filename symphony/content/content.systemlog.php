<?php

	Class contentSystemLog{
		
		public function build(){
			
			if(!is_file(ACTIVITY_LOG)) throw new AdministrationPageNotFoundException;
			
			header('Content-Type: text/plain');
			readfile(ACTIVITY_LOG);
			exit();
		}
		
	}
	
