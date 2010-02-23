<?php

	Class ViewException extends Exception {}

	Class ViewFilterIterator extends FilterIterator{
		public function __construct(){
			parent::__construct(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(VIEWS), RecursiveIteratorIterator::SELF_FIRST));
		}
		
		// Only return folders, and only those that have a 'X.config.xml' file within. This characterises a View.
		public function accept(){
			if($this->getInnerIterator()->isDir() == false) return false;
			preg_match('/\/?([^\/]+)$/', $this->getInnerIterator()->getPathname(), $match); //Find the view handle
			return (file_exists(sprintf('%s/%s.config.xml', $this->getInnerIterator()->getPathname(), $match[1])));
		}
	}	

	Class View{
		
		const ERROR_VIEW_NOT_FOUND = 0;
		const ERROR_FAILED_TO_LOAD = 1;
		const ERROR_DOES_NOT_ACCEPT_PARAMETERS = 2;
		const ERROR_TOO_MANY_PARAMETERS = 3;
		
		const ERROR_MISSING_OR_INVALID_FIELDS = 4;
		const ERROR_FAILED_TO_WRITE = 5;

		private $_about;
		private $_path;
		private $_parent;
		private $_parameters;
		private $_template;
		private $_handle;
		private $_guid;

		public function __construct(){
			$this->_about = new StdClass;
			$this->_parameters = new StdClass;
			$this->_path = $this->_parent = $this->_template = $this->_handle = $this->_guid = NULL;
		}
		
		public function about(){
			return $this->_about;
		}
		
		public function __get($name){
			if($name == 'path') return $this->_path;
			elseif($name == 'template') return $this->_template;
			elseif($name == 'handle') return $this->_handle;
			elseif($name == 'guid') return $this->_guid;			
			return $this->_about->$name;
		}
	
		public function __set($name, $value){
			if($name == 'path') $this->_path = $value;
			elseif($name == 'template') $this->_template = $value;
			elseif($name == 'handle') $this->_handle = $value;	
			elseif($name == 'guid') $this->_guid = $value;		
			else $this->_about->$name = $value;
		}

		public static function loadFromPath($path, array $params=NULL){

			$view = new View;
			
			$view->path = trim($path, '/');
			
			preg_match('/\/?([^\/]+)$/', $path, $match); //Find the view handle
			$view->handle = $match[1];
			$pathname = sprintf('%s/%s/%s.config.xml', VIEWS, $view->path, $view->handle);
			
			if(!file_exists($pathname)){
				throw new ViewException(__('View, %s, could not be found.', array($pathname)), self::ERROR_VIEW_NOT_FOUND);
			}
			
			$doc = @simplexml_load_file($pathname);
			
			if(!($doc instanceof SimpleXMLElement)){
				throw new ViewException(__('Failed to load view configuration file: %s', array($pathname)), self::ERROR_FAILED_TO_LOAD);
			}

			foreach($doc as $name => $value){
				if(isset($value->item)){
					$stack = array();
					foreach($value->item as $item){
						array_push($stack, (string)$item);
					}
					$view->$name = $stack;
				}
				else $view->$name = (string)$value;
			}
			
			if(isset($doc->attributes()->guid)){
				$view->guid = (string)$doc->attributes()->guid;
			}
			else{
				$view->guid = uniqid();
			}
			
			if(!is_null($params)){
				
				if(!is_array($view->{'url-parameters'}) || count($view->{'url-parameters'}) <= 0){
					throw new ViewException(__('This view does not accept parameters.', array($pathname)), self::ERROR_DOES_NOT_ACCEPT_PARAMETERS);
				}
				
				if(count($params) > count($view->{'url-parameters'})){
					throw new ViewException(__('Too many parameters supplied.', array($pathname)), self::ERROR_TOO_MANY_PARAMETERS);
				}
				
				foreach($params as $index => $p){
					$view->setParameter($view->{'url-parameters'}[$index], $p);
				}
			}
			
			$template = sprintf('%s/%s/%s.xsl', VIEWS, $view->path, $view->handle);
			if(file_exists($template) && is_readable($template)){
				$view->template = file_get_contents($template);
			}
			
			return $view;
		}
		
		public function setParameter($name, $value){
			$this->_parameters->$name = $value;
		}
		
		public static function loadFromFieldsArray($fields){

			$view = new self;
			
			foreach($fields as $name => $value){
				$view->$name = $value;
			}
			
			return $view;
		}
		
		public static function findFromType($type){
			$views = array();
			foreach(new ViewIterator as $v){
				if(@in_array($type, $v->types)){
					$views[] = $v;
				}
			}
			return $views;
		}
		
		public static function loadFromURL($path){
			$parts = preg_split('/\//', $path, -1, PREG_SPLIT_NO_EMPTY);
			$view = NULL;
			
			while(!empty($parts)){
				
				$p = array_shift($parts);
				
				if(!is_dir(VIEWS . $view . "/{$p}")){
					array_unshift($parts, $p);
					break;
				}
				
				$view = $view . "/{$p}";
				
			}
			
			return self::loadFromPath($view, (!empty($parts) ? $parts : NULL));
		}
		
		public static function buildPageTitle(View $v){

			$title = $v->title;
			
			$current = $v->parent();
			
			while(!is_null($current)){
				$title = sprintf('%s: %s', $current->title, $title);
				$current = $current->parent();
			}
			
			return $title;
		}
		
		public static function save(self $view, $path, MessageStack &$messages){
			
			print_r($view); die($path);
			
			$pathname = sprintf('%s/%s/%s.config.xml', VIEWS, $path, $view->handle);
			if(file_exists($pathname)){
				$existing = self::loadFromPath($path);
				if($existing->guid != $view->guid){
					$messages->append('handle', 'A view with that handle already exists.');
					return false;
				}
				unset($existing);
			}
			
			if(strlen(trim($view->template)) == 0){
				$messages->append('template', 'Template is required, and cannot be empty.');
			}
			elseif(!General::validateXML($view->template, $errors, false, new XSLTProcess())) {
				$messages->append('template', __('This document is not well formed. The following error was returned: <code>%s</code>', array($errors[0]['message'])));
			}
			
			if($messages->length() > 0){
				throw new ViewException(__('View could not be saved. Validation failed.'), self::ERROR_MISSING_OR_INVALID_FIELDS);
			}
			
			// Save the template file
			if(!General::writeFile(sprintf('%s/%s/%s.xsl', VIEWS, $path, $view->handle), $view->template, Symphony::Configuration()->get('file_write_mode', 'symphony'))){
				throw new ViewException(__('Template could not be written to disk. Please check permissions on <code>/workspace/views</code>.'), self::ERROR_FAILED_TO_WRITE);
			}
			
			return true;
		}
		
		public function __toString(){
			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->formatOutput = true;
			
			$root = $doc->createElement('view');
			$doc->appendChild($root);
			
			if(!isset($this->guid) || is_null($this->guid)){
				$this->guid = uniqid();
			}
			
			$root->setAttribute('guid', $this->guid);
			
			$root->appendChild($doc->createElement('title', General::sanitize($this->title)));
			
			if(is_array($this->{'url-parameters'}) && count($this->{'url-parameters'}) > 0){
				$url_parameters = $doc->createElement('url-parameters');
				foreach($this->{'url-parameters'} as $p){
					$url_parameters->appendChild($doc->createElement('item', General::sanitize($p)));
				}
				$root->appendChild($url_parameters);
			}
			
			if(is_array($this->events) && count($this->events) > 0){
				$events = $doc->createElement('events');
				foreach($this->events as $p){
					$events->appendChild($doc->createElement('item', General::sanitize($p)));
				}
				$root->appendChild($events);
			}
			
			if(is_array($this->{'data-sources'}) && count($this->{'data-sources'}) > 0){
				$data_sources = $doc->createElement('data-sources');
				foreach($this->{'data-sources'} as $p){
					$data_sources->appendChild($doc->createElement('item', General::sanitize($p)));
				}
				$root->appendChild($data_sources);
			}						

			return $doc->saveXML();
		}
		
		public function parent(){
			if($this->_path == $this->handle) return NULL;
			elseif(!($this->_parent instanceof self)){
				$this->_parent = self::loadFromPath(rtrim($this->_path, "/{$this->handle}"));
			} 
			return $this->_parent;
		}
	}


	Class ViewIterator implements Iterator{

		private $_iterator;
		private $_length;
		private $_position;

		public function __construct(){
			$this->_iterator = new ViewFilterIterator;
			$this->_length = $this->_position = 0;
			foreach($this->_iterator as $f){
				$this->_length++;
			}
			$this->_iterator->rewind();
		}

		public function current(){
			$path = str_replace(VIEWS, NULL, $this->_iterator->current()->getPathname());
			
			if(!($this->_current instanceof self) || $this->_current->path != $path){
				$this->_current = View::loadFromPath($path);
			}
			
			return $this->_current;
		}
					
		public function innerIterator(){
			return $this->_iterator;
		}

		public function next(){
			$this->_position++;
			$this->_iterator->next();
		}

		public function key(){
			return $this->_iterator->key();
		}

		public function valid(){
			return $this->_position < $this->_length;
		}

		public function rewind(){
			$this->_position = 0;
			$this->_iterator->rewind();
		}

		public function position(){
			return $this->_position;
		}

		public function length(){
			return $this->_length;
		}

	}
