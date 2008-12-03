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
							'name' => 'Admin Admin',
							'website' => 'http://localhost:8888/projects/legacy/symphony-2-beta',
							'email' => 'admin@admin.com'),
					 'version' => '1.0',
					 'release-date' => '2008-12-03T04:59:38+00:00',
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
        <h3>Success and Failure XML Examples</h3>
        <p>When saved successfully, the following XML will be returned:</p>
        <pre class="XML"><code>&lt;publish-article result="success" type="create | edit">
  &lt;message>Entry [created | edited] successfully.&lt;/message>
&lt;/publish-article></code></pre>
        <p>When an error occurs during saving, due to either missing or invalid fields, the following XML will be returned:</p>
        <pre class="XML"><code>&lt;publish-article result="error">
  &lt;message>Entry encountered errors when saving.&lt;/message>
  &lt;field-name type="invalid | missing" />
  ...
&lt;/publish-article></code></pre>
        <p>The following is an example of what is returned if any filters fail:</p>
        <pre class="XML"><code>&lt;publish-article result="error">
  &lt;message>Entry encountered errors when saving.&lt;/message>
  &lt;filter type="admin-only" status="failed" />
  &lt;filter type="send-email" status="failed">Recipient username was invalid&lt;/filter>
  ...
&lt;/publish-article></code></pre>
        <h3>Example Front-end Form Markup</h3>
        <p>This is an example of the form markup you can use on your frontend:</p>
        <pre class="XML"><code>&lt;form method="post" action="" enctype="multipart/form-data">
  &lt;input name="MAX_FILE_SIZE" type="hidden" value="5242880" />
  &lt;label>Title
    &lt;input name="fields[title]" type="text" />
  &lt;/label>
  &lt;label>Body
    &lt;textarea name="fields[body]" rows="20" cols="50">&lt;/textarea>
  &lt;/label>
  &lt;label>Date
    &lt;input name="fields[date]" type="text" />
  &lt;/label>
  &lt;label>Categories
    &lt;select name="fields[categories]">
      &lt;option value="Symphony">Symphony&lt;/option>
      &lt;option value="Health">Health&lt;/option>
      &lt;option value="Fish &amp;amp; Chips">Fish &amp;amp; Chips&lt;/option>
      &lt;option value="Firefly">Firefly&lt;/option>
      &lt;option value="Entertainment">Entertainment&lt;/option>
    &lt;/select>
  &lt;/label>
  &lt;label>Publish
    &lt;input name="fields[publish]" type="checkbox" />
  &lt;/label>
  &lt;input name="action[publish-article]" type="submit" value="Submit" />
&lt;/form></code></pre>
        <p>To edit an existing entry, include the entry ID value of the entry in the form. This is best as a hidden field like so:</p>
        <pre class="XML"><code>&lt;input name="id" type="hidden" value="23" /></code></pre>
        <p>To redirect to a different location upon a successful save, include the redirect location in the form. This is best as a hidden field like so, where the value is the URL to redirect to:</p>
        <pre class="XML"><code>&lt;input name="redirect" type="hidden" value="http://localhost:8888/projects/legacy/symphony-2-beta/success/" /></code></pre>';
		}
		
		public function load(){			
			if(isset($_POST['action']['publish-article'])) return $this->__trigger();
		}
		
		protected function __trigger(){
			include(TOOLKIT . '/events/event.section.php');
			return $result;
		}		

	}

