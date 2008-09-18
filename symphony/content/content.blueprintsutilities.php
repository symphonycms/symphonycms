<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');	

	Class contentBlueprintsUtilities extends AdministrationPage{

		var $_existing_file;

		function __construct(&$parent){
			parent::__construct($parent);
			$this->setPageType('form');
		}
		
		## Overload the parent 'view' function since we dont need the switchboard logic
		function view(){
			
			$this->_existing_file = (isset($this->_context[1]) ? $this->_context[1] . '.xsl' : NULL);
			
			## Handle unknown context
			if(!in_array($this->_context[0], array('new', 'edit'))) $this->_Parent->errorPageNotFound();
			
			## Edit Utility context
			if($this->_context[0] == 'edit'){
	
				$file_abs = UTILITIES . '/' . $this->_existing_file;
				$filename = $this->_existing_file;

				if(!@is_file($file_abs)) redirect(URL . '/symphony/blueprints/utilities/new/');
				
				$fields['name'] = $filename; 
				$fields['body'] = @file_get_contents($file_abs);
				
				$this->Form->setAttribute('action', URL . '/symphony/blueprints/utilities/edit/' . $this->_context[1] . '/');							
			}
			
			else{
				
				$fields['body'] = '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template name="">

</xsl:template>

</xsl:stylesheet>';
				
			}
			

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));			
			if($formHasErrors) $this->pageAlert('An error occurred while processing this form. <a href="#error">See below for details.</a>', AdministrationPage::PAGE_ALERT_ERROR);

			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
						$this->pageAlert('{1} updated successfully. <a href="'.URL.'/symphony/{2}">Create another?</a>', AdministrationPage::PAGE_ALERT_NOTICE, array('Utility', 'blueprints/utilities/new/'));
						break;
						
					case 'created':
						$this->pageAlert('{1} created successfully. <a href="'.URL.'/symphony/{2}">Create another?</a>', AdministrationPage::PAGE_ALERT_NOTICE, array('Utility', 'blueprints/utilities/new/'));
						break;
					
				}
			}
			
			$this->setTitle('Symphony &ndash; Utilities ' . ($this->_context[0] == 'new' ? '' : "&ndash; $filename"));
			$this->appendSubheading(($this->_context[0] == 'new' ? 'Untitled' : $filename));

			if(!empty($_POST)) $fields = $_POST['fields'];

			$fields['body'] = General::sanitize(General::tabsToSpaces($fields['body'], 2));

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'primary');
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('fields[name]', $fields['name']));
			$fieldset->appendChild((isset($this->_errors['name']) ? $this->wrapFormElementWithError($label, $this->_errors['name']) : $label));

			$label = Widget::Label('Body');
			$label->appendChild(Widget::Textarea('fields[body]', 25, 50, $fields['body'], array('class' => 'code')));
			$fieldset->appendChild((isset($this->_errors['body']) ? $this->wrapFormElementWithError($label, $this->_errors['body']) : $label));
			
			$this->Form->appendChild($fieldset);

			$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utilities = $utilities['filelist'];			
			
			if(is_array($utilities) && !empty($utilities)){
			
				$div = new XMLElement('div');
				$div->setAttribute('class', 'secondary');
				
				$h3 = new XMLElement('h3', 'Utilities');
				$h3->setAttribute('class', 'label');
				$div->appendChild($h3);
				
				$ul = new XMLElement('ul');
				$ul->setAttribute('id', 'utilities');
			
				foreach($utilities as $util){
					$li = new XMLElement('li');
					$li->appendChild(Widget::Anchor($util, URL . '/symphony/blueprints/utilities/edit/' . str_replace('.xsl', '', $util) . '/', NULL));
					$ul->appendChild($li);
				}
			
				$div->appendChild($ul);
			
				$this->Form->appendChild($div);
							
			}
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($this->_context[0] == 'edit' ? 'Save Changes' : 'Create Utility'), 'submit', array('accesskey' => 's')));
			
			if($this->_context[0] == 'edit'){
				$button = new XMLElement('button', 'Delete');
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => 'Delete this utility'));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);
			
				
			
		}
		
		function action(){
			
			$this->_existing_file = (isset($this->_context[1]) ? $this->_context[1] . '.xsl' : NULL);
			
			if(array_key_exists('save', $_POST['action']) || array_key_exists('done', $_POST['action'])){

				$fields = $_POST['fields'];

				$this->_errors = array();

				if(!isset($fields['name']) || trim($fields['name']) == '') $this->_errors['name'] = 'Name is a required field.';
				
				if(!isset($fields['body']) || trim($fields['body']) == '') $this->_errors['body'] = 'Body is a required field.';
				elseif(!General::validateXML($fields['body'], $errors, false, new XSLTProcess())) $this->_errors['body'] = 'This document is not well formed. The following error was returned: <code>' . $errors[0]['message'] . '</code>';
				
				if(empty($this->_errors)){

					$fields['name'] = Lang::createFilename($fields['name']);
		            if(General::right($fields['name'], 4) != '.xsl') $fields['name'] .= '.xsl';
		
					$file = UTILITIES . '/' . $fields['name'];
					
					##Duplicate
					if($this->_context[0] == 'edit' && ($this->_existing_file != $fields['name'] && is_file($file)))
						$this->_errors['name'] = 'A Utility with that name already exists. Please choose another.';
					
					elseif($this->_context[0] == 'new' && is_file($file)) $this->_errors['name'] = 'A Utility with that name already exists. Please choose another.'; 

					##Write the file	
					elseif(!$write = General::writeFile($file, $fields['body'], $this->_Parent->Configuration->get('write_mode', 'file')))
						$this->pageAlert('Utility could not be written to disk. Please check permissions on <code>/workspace/utilities</code>.', AdministrationPage::PAGE_ALERT_ERROR); 			

					##Write Successful, add record to the database
					else{

						## Remove any existing file if the filename has changed
						if($this->_existing_file && $file != UTILITIES . '/' . $this->_existing_file) 
							General::deleteFile(UTILITIES . '/' . $this->_existing_file);

						## TODO: Fix me
						###
						# Delegate: Edit
						# Description: After saving the asset, the file path is provided.
						//$ExtensionManager->notifyMembers('Edit', getCurrentPage(), array('file' => $file));
						
						redirect(URL . '/symphony/blueprints/utilities/edit/'.str_replace('.xsl', '', $fields['name']) . '/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');

					}
				}
			}
			
			elseif($this->_context[0] == 'edit' && @array_key_exists('delete', $_POST['action'])){

				## TODO: Fix me
				###
				# Delegate: Delete
				# Description: Prior to deleting the asset file. Target file path is provided.
				//$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array('file' => WORKSPACE . '/' . $this->_existing_file_rel));

		    	General::deleteFile(UTILITIES . '/' . $this->_existing_file);

		    	redirect(URL . '/symphony/blueprints/components/');	
		  	}	
		}
	}
	
?>
