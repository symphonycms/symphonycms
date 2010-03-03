<?php

	Class contentSystemLog{
		
		public function build(){
			
			if(!is_file(ACTIVITY_LOG)) Administration::instance()->errorPageNotFound();
			
			header('Content-Type: text/plain');
			readfile(ACTIVITY_LOG);
			exit();
		}
		
	}
	
