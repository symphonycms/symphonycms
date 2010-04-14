<?php
	
	Class DocumentHeaders{
		protected $headers;
		
		public function __construct(array $headers=array()){
			$this->headers = $headers;
		}
		
		public function append($name, $value=NULL){
			$this->headers[strtolower($name)] = $name . (is_null($value) ? NULL : ":{$value}");
		}

		public function render(){
			if(!is_array($this->headers) || empty($this->headers)) return;

			foreach($this->headers as $value){
				header($value);
			}
		}
		
		public function headers(){
			return $this->headers;
		}
	}
	
	Class HTMLDocument{
		protected $Document;
		public $Html;
		public $Head;
		public $Body;
		public $Headers;
		
		public function createElement($name, $value=NULL){
			return $this->innerDocument()->createElement($name, $value);
		}
	
		public function createScriptElement($path){
			$element = $this->innerDocument()->createElement('script');
			$element->setAttribute('type', 'text/javascript');
			$element->setAttribute('src', $path);
		
			// Creating an empty text node forces <script></script>
			$element->appendChild($this->innerDocument()->createTextNode(''));
		
			return $element;
		}
	
		public function createStylesheetElement($path, $type='screen'){
			$element = $this->innerDocument()->createElement('link');
			$element->setAttribute('type', 'text/css');
			$element->setAttribute('rel', 'stylesheet');
			$element->setAttribute('media', $type);
			$element->setAttribute('href', $path);
			return $element;
		}
	
		public function __construct($version='1.0', $encoding='utf-8', DOMDocumentType $dtd=NULL){
			
			$this->Headers = new DocumentHeaders(array(
				'Content-Type', "text/html; charset={$encoding}",
			));
			
			if(is_null($dtd)){
				$dtd = DOMImplementation::createDocumentType('html');
			}
		
			$this->Document = DOMImplementation::createDocument(NULL, 'html', $dtd);
			$this->Document->version = $version;
			$this->Document->encoding = $encoding;
		
			$this->Document->preserveWhitespace = false;
			$this->Document->formatOutput = true;
		
			$this->Html = $this->Document->documentElement;

			$this->Head = $this->Document->createElement('head');
			$this->Html->appendChild($this->Head);
		
			$this->Body = $this->Document->createElement('body');
			$this->Html->appendChild($this->Body);
		}
	
		public function insertNodeIntoHead(DOMElement $element, $position=NULL){

			if(is_null($position)){
				$this->Head->appendChild($element);
				return;
			}

			$node = $this->xpath("/html/head/*[position() >= {$position}]")->item(0);
		
			if(is_null($node)){
				$this->Head->appendChild($element);
			}
			else{
				$node->parentNode->insertBefore($element, $node);
			}
		
		}
	
		public function isElementInHead($element, $attr=NULL, $nodeValue=NULL){
		
			$xpath = "/html/head/{$element}";
			if(!is_null($attr)){
				$xpath .= "/@{$attr}[contains(.,'{$nodeValue}')]";
			}
		
			$nodes = $this->xpath($xpath);
			return ($nodes->length > 0 ? true : false);
	    }
	
		public function xpath($query){
			$xpath = new DOMXPath($this->innerDocument());
			return $xpath->query($query);
		}
	
		public function innerDocument(){
			return $this->Document;
		}
	
		public function __toString(){
			return $this->Document->saveHTML();
		}
	
	}


/*	
	// USAGE EXAMPLE:
	
	$page = new HTMLDocument('1.0', 'utf-8', DOMImplementation::createDocumentType(
		"html", 
		"-//W3C//DTD HTML 4.01//EN", 
		"http://www.w3.org/TR/html4/strict.dtd"
	));

	$page->Head->appendChild(
		$page->createElement('title', 'A New Page')
	);

	$page->Html->setAttribute('lang', 'en');

	$Form = $page->createElement('form');
	$page->Body->appendChild($Form);

	$Form->setAttribute('action', 'blah.php');
	$Form->setAttribute('method', 'POST');

	$page->insertNodeIntoHead(
		$page->createStylesheetElement('./blah/styles.css', 'print')
	);

	$page->insertNodeIntoHead(	
		$page->createScriptElement('./blah/scripts.js'), 2
	);

	if($page->isElementInHead('script', 'src', 'scripts.js') == false){
		$page->insertNodeIntoHead(	
			$page->createScriptElement('./blah/scripts.js'), 2
		);
	}
	
	//Uncomment this to see output as plain text
	//$page->Headers->append('Content-Type', 'text/plain');
	
	$output = (string)$page;
	$page->Headers->append('Content-Length', strlen($output));

	$page->Headers->render();
	echo $output;
	exit();
	
*/