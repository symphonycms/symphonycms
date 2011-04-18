<?php
	/**
	 * @package content
	 */
	/**
	 * The Utilities page allows Developers to create Utilities through the
	 * Symphony backend. Although most Developers will actually do this through
	 * an IDE, this allows a quick way for users to build Symphony within Symphony
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');

	Class contentBlueprintsUtilities extends AdministrationPage{

		public $_existing_file;

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->setPageType('form');
		}

		## Overload the parent 'view' function since we dont need the switchboard logic
		public function view(){

			$this->_existing_file = (isset($this->_context[1]) ? $this->_context[1] . '.xsl' : NULL);

			## Handle unknown context
			if(!in_array($this->_context[0], array('new', 'edit'))) Administration::instance()->errorPageNotFound();

			## Edit Utility context
			if($this->_context[0] == 'edit'){

				$file_abs = UTILITIES . '/' . $this->_existing_file;
				$filename = $this->_existing_file;

				if(!is_file($file_abs)) redirect(SYMPHONY_URL . '/blueprints/utilities/new/');

				$fields['name'] = $filename;
				$fields['body'] = @file_get_contents($file_abs);

				$this->Form->setAttribute('action', SYMPHONY_URL . '/blueprints/utilities/edit/' . $this->_context[1] . '/');
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
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			if(isset($this->_context[2])){
				switch($this->_context[2]){

					case 'saved':
						$this->pageAlert(
							__(
								'Utility updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Utilities</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . '/blueprints/utilities/new/',
									SYMPHONY_URL . '/blueprints/components/'
								)
							),
							Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__(
								'Utility created at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Utilities</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . '/blueprints/utilities/new/',
									SYMPHONY_URL . '/blueprints/components/'
								)
							),
							Alert::SUCCESS);
						break;

				}
			}

			$this->setTitle(__(($this->_context[0] == 'new' ? '%1$s &ndash; %2$s' : '%1$s &ndash; %2$s &ndash; %3$s'), array(__('Symphony'), __('Utilities'), $filename)));
			$this->appendSubheading(($this->_context[0] == 'new' ? __('Untitled') : $filename));

			if(!empty($_POST)) $fields = $_POST['fields'];

			$fields['body'] = General::sanitize($fields['body']);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'primary');

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', $fields['name']));
			$fieldset->appendChild((isset($this->_errors['name']) ? Widget::wrapFormElementWithError($label, $this->_errors['name']) : $label));

			$label = Widget::Label(__('Body'));
			$label->appendChild(Widget::Textarea('fields[body]', 30, 80, $fields['body'], array('class' => 'code')));
			$fieldset->appendChild((isset($this->_errors['body']) ? Widget::wrapFormElementWithError($label, $this->_errors['body']) : $label));

			$this->Form->appendChild($fieldset);

			$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utilities = $utilities['filelist'];

			if(is_array($utilities) && !empty($utilities)){

				$div = new XMLElement('div');
				$div->setAttribute('class', 'secondary');

				$p = new XMLElement('p', __('Utilities'));
				$p->setAttribute('class', 'label');
				$div->appendChild($p);

				$ul = new XMLElement('ul');
				$ul->setAttribute('id', 'utilities');

				$i = 0;
				foreach($utilities as $util){
					$li = new XMLElement('li');

					if ($i++ % 2 != 1) {
						$li->setAttribute('class', 'odd');
					}

					$li->appendChild(Widget::Anchor($util, SYMPHONY_URL . '/blueprints/utilities/edit/' . str_replace('.xsl', '', $util) . '/', NULL));
					$ul->appendChild($li);
				}

				$div->appendChild($ul);

				$this->Form->appendChild($div);

			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create Utility')), 'submit', array('accesskey' => 's')));

			if($this->_context[0] == 'edit'){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this utility'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this Utility?')));
				$div->appendChild($button);
			}

			$this->Form->appendChild($div);

		}

		public function action(){

			$this->_existing_file = (isset($this->_context[1]) ? $this->_context[1] . '.xsl' : NULL);

			if(array_key_exists('save', $_POST['action']) || array_key_exists('done', $_POST['action'])){

				$fields = $_POST['fields'];

				$this->_errors = array();

				if(!isset($fields['name']) || trim($fields['name']) == '') $this->_errors['name'] = __('Name is a required field.');

				if(!isset($fields['body']) || trim($fields['body']) == '') $this->_errors['body'] = __('Body is a required field.');
				elseif(!General::validateXML($fields['body'], $errors, false, new XSLTProcess())) $this->_errors['body'] = __('This document is not well formed. The following error was returned: <code>%s</code>', array($errors[0]['message']));

				$fields['name'] = Lang::createFilename($fields['name']);
				if(General::right($fields['name'], 4) != '.xsl') $fields['name'] .= '.xsl';

				$file = UTILITIES . '/' . $fields['name'];

				##Duplicate
				if($this->_context[0] == 'edit' && ($this->_existing_file != $fields['name'] && is_file($file)))
					$this->_errors['name'] = __('A Utility with that name already exists. Please choose another.');

				elseif($this->_context[0] == 'new' && is_file($file)) $this->_errors['name'] = __('A Utility with that name already exists. Please choose another.');

				if(empty($this->_errors)){
					if($this->_context[0] == 'new') {
						/**
						 * Just before the Utility has been created
						 *
						 * @delegate UtilityPreCreate
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/utilities/'
						 * @param string $file
						 *  The path to the Utility file
						 * @param string $contents
						 *  The contents of the `$fields['body']`, passed by reference
						 */
						Symphony::ExtensionManager()->notifyMembers('UtilityPreCreate', '/blueprints/utilities/', array('file' => $file, 'contents' => &$fields['body']));
					}
					else {
						/**
						 * Just before the Utility has been updated
						 *
						 * @delegate UtilityPreEdit
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/utilities/'
						 * @param string $file
						 *  The path to the Utility file
						 * @param string $contents
						 *  The contents of the `$fields['body']`, passed by reference
						 */
						Symphony::ExtensionManager()->notifyMembers('UtilityPreEdit', '/blueprints/utilities/', array('file' => $file, 'contents' => &$fields['body']));
					}

					##Write the file
					if(!$write = General::writeFile($file, $fields['body'], Symphony::Configuration()->get('write_mode', 'file')))
						$this->pageAlert(__('Utility could not be written to disk. Please check permissions on <code>/workspace/utilities</code>.'), Alert::ERROR);

					##Write Successful, add record to the database
					else{

						## Remove any existing file if the filename has changed
						if($this->_existing_file && $file != UTILITIES . '/' . $this->_existing_file) {
							General::deleteFile(UTILITIES . '/' . $this->_existing_file);
						}

						if($this->_context[0] == 'new') {
							/**
							 * Just after the Utility has been written to disk
							 *
							 * @delegate UtilityPostCreate
							 * @since Symphony 2.2
							 * @param string $context
							 * '/blueprints/utilities/'
							 * @param string $file
							 *  The path to the Utility file
							 */
							Symphony::ExtensionManager()->notifyMembers('UtilityPostCreate', '/blueprints/utilities/', array('file' => $file));
						}
						else {
							/**
							 * Prior to deleting the Utility
							 *
							 * @delegate UtilityPostEdit
							 * @since Symphony 2.2
							 * @param string $context
							 * '/blueprints/utilities/'
							 * @param string $file
							 *  The path to the Utility file
							 */
							Symphony::ExtensionManager()->notifyMembers('UtilityPostEdit', '/blueprints/utilities/', array('file' => $file));
						}

						redirect(SYMPHONY_URL . '/blueprints/utilities/edit/'.str_replace('.xsl', '', $fields['name']) . '/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');

					}
				}
			}

			elseif($this->_context[0] == 'edit' && @array_key_exists('delete', $_POST['action'])){

				/**
				 * Prior to deleting the Utility
				 *
				 * @delegate UtilityPreDelete
				 * @since Symphony 2.2
				 * @param string $context
				 * '/blueprints/utilities/'
				 * @param string $file
				 *  The path to the Utility file
				 */
				Symphony::ExtensionManager()->notifyMembers('UtilityPreDelete', '/blueprints/utilities/', array('file' => $this->_existing_file));

				General::deleteFile(UTILITIES . '/' . $this->_existing_file);

				redirect(SYMPHONY_URL . '/blueprints/components/');
		  	}
		}
	}
