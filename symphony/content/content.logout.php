<?php
	/**
	 * @package content
	 */
	/**
	 * The default Logout page will redirect the user
	 * to the Homepage of `URL`
	 */
	Class contentLogout extends HTMLPage{

		public function build(){
			$this->view();
		}
	
		public function view(){
			Administration::instance()->logout();
			redirect(URL);
		}
	
	}
