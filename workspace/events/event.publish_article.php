<?php

	require_once(TOOLKIT . '/class.event.php');
	
	Class eventpublish_article extends Event{
		
		const ROOTELEMENT = 'publish-article';
		
		public $eParamFILTERS = array(
			'admin-only'
		);
			
		public static function about(){
			return array(
					 'name' => 'Publish Article',
					 'author' => array(
							'name' => 'Allen Chang',
							'website' => 'http://symphony.local:8888',
							'email' => 'allen@21degrees.com.au'),
					 'version' => '1.0',
					 'release-date' => '2008-01-20T00:50:58+00:00',
					 'trigger-condition' => 'action[publish-article]');	
		}

		public static function getSource(){
			return '6';
		}

		public static function allowEditorToParse(){
			return true;
		}

		public static function documentation(){
			return '
            <p>When saved successfully, the following XML will be returned:</p>
            <pre class="XML"><code>&lt;publish-article result="success" type="create | edit">
   &lt;message>Entry [created | edited] successfully.&lt;/message>
&lt;/publish-article></code></pre>
            <p>When an error occurs, the following XML will be returned:</p>
            <pre class="XML"><code>&lt;publish-article result="error">
   &lt;message>Entry encountered errors when saving.&lt;/message>
   &lt;field-name type="invalid | missing"/>
   ...
&lt;/publish-article></code></pre>
            <p>This is an example of the form markup you can use on your frontend:</p>
            <pre class="XML"><code>&lt;form method="post" action="">
   &lt;label>Title
      &lt;input name="fields[title]" />
   &lt;/label>
   &lt;label>Body
      &lt;input name="fields[body]" />
   &lt;/label>
   &lt;label>Date
      &lt;input name="fields[date]" />
   &lt;/label>
   &lt;label>Categories
      &lt;input name="fields[categories]" />
   &lt;/label>
   &lt;label>Publish
      &lt;input name="fields[publish]" />
   &lt;/label>
   &lt;input name="action[publish-article]" type="submit" value="Submit" />
&lt;/form></code></pre>
            <p>To edit an existing entry, include the entry ID value in the form. This is best as a hidden field, like so:</p>
            <pre class="XML"><code>&lt;input name="id" value="{articles/entry[1]/@id}" type="hidden" /></code></pre>';
		}
		
		public function load(){			
			if(isset($_POST['action']['publish-article'])) return $this->__trigger();
		}
		
		protected function __trigger(){
			include(TOOLKIT . '/events/event.section.php');
			return $result;
		}		

	}

?>