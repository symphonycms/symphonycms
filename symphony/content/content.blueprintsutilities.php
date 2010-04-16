<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.xslproc.php');

	Class contentBlueprintsUtilities extends AdministrationPage{

		private $_existing_file;

		public function __viewIndex() {
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Utilities'))));

			$this->appendSubheading(__('Utilities') . $heading, Widget::Anchor(
				__('Create New'), Administration::instance()->getCurrentPageURL() . 'new/', array(
					'title' => __('Create a new utility'),
					'class' => 'create button'
				)
			));

			$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utilities = $utilities['filelist'];

			$uTableHead = array(
				array(__('Name'), 'col')
			);

			$uTableBody = array();
			$colspan = count($uTableHead);

			if(!is_array($utilities) or empty($utilities)) {
				$uTableBody = array(Widget::TableRow(
					array(
						Widget::TableData(__('None found.'), array(
								'class' => 'inactive',
								'colspan' => $colspan
							)
						)
					), array(
						'class' => 'odd'
					)
				));
			}

			else {
				foreach($utilities as $util){
					$uRow = Widget::TableData(
						Widget::Anchor(
							$util,
							URL . '/symphony/blueprints/utilities/edit/' . str_replace('.xsl', '', $util) . '/')
						);

					$uRow->appendChild(Widget::Input("items[{$util}]", null, 'checkbox'));

					$uTableBody[] = Widget::TableRow(
						array($uRow)
					);
				}
			}

			$table = Widget::Table(
				Widget::TableHead($uTableHead), null, Widget::TableBody($uTableBody), array(
					'id' => 'utilities-list'
				)
			);

			$this->Form->appendChild($table);

			$tableActions = $this->createElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(null, false, __('With Selected...')),
				array('delete', false, __('Delete'))
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function __viewNew() {
			$this->__viewEdit();
		}

		public function __viewEdit() {
			$layout = new Layout('2', '2:1');

			$this->_existing_file = (isset($this->_context[1]) ? $this->_context[1] . '.xsl' : NULL);

			## Handle unknown context
			if(!in_array($this->_context[0], array('new', 'edit'))) throw new AdministrationPageNotFoundException;

			## Edit Utility context
			if($this->_context[0] == 'edit'){

				$file_abs = UTILITIES . '/' . $this->_existing_file;
				$filename = $this->_existing_file;

				if(!is_file($file_abs) && !is_readable($file_abs)) redirect(URL . '/symphony/blueprints/utilities/new/');

				$fields['name'] = $filename;
				$fields['body'] = file_get_contents($file_abs);

				$this->Form->setAttribute('action', URL . '/symphony/blueprints/utilities/edit/' . $this->_context[1] . '/');
			}

			else{
				$fields['body'] = file_get_contents(TEMPLATES . '/template.utility.txt');
			}

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			if(isset($this->_context[2])){
				switch($this->_context[2]){

					case 'saved':
						$this->pageAlert(
							__(
								'Utility updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Utilities</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									URL . '/symphony/blueprints/utilities/new/',
									URL . '/symphony/blueprints/utilities/'
								)
							),
							Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__(
								'Utility created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Utilities</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									URL . '/symphony/blueprints/utilities/new/',
									URL . '/symphony/blueprints/utilities/'
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

			$fieldset = Widget::Fieldset();

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', $fields['name']));
			$fieldset->appendChild((isset($this->_errors['name']) ? Widget::wrapFormElementWithError($label, $this->_errors['name']) : $label));

			$label = Widget::Label(__('Body'));
			$label->appendChild(
				Widget::Textarea('fields[template]', $fields['body'], array(
					'rows' => 30,
					'cols' => 80,
					'class'	=> 'code'
				)
			));

			$fieldset->appendChild((isset($this->_errors['body']) ? Widget::wrapFormElementWithError($label, $this->_errors['body']) : $label));

			$layout->appendToCol($fieldset, 1);

			$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utilities = $utilities['filelist'];

			if(is_array($utilities) && !empty($utilities)){

				$fieldset = Widget::Fieldset(__('Utilities'));

				$ul = $this->createElement('ul');
				$ul->setAttribute('id', 'utilities');

				$i = 0;
				foreach($utilities as $util){
					$li = $this->createElement('li');

					if ($i++ % 2 != 1) {
						$li->setAttribute('class', 'odd');
					}

					$li->appendChild(Widget::Anchor($util, URL . '/symphony/blueprints/utilities/edit/' . str_replace('.xsl', '', $util) . '/', NULL));
					$ul->appendChild($li);
				}

				$fieldset->appendChild($ul);

				$layout->appendToCol($fieldset, 2);
			}

			$this->Form->appendChild($layout->generate());

			$div = $this->createElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create Utility')), 'submit', array('accesskey' => 's')));

			if($this->_context[0] == 'edit'){
				$div->appendChild(
					$this->createElement('button', __('Delete'), array(
						'name' => 'action[delete]',
						'class' => 'confirm delete',
						'title' => __('Delete this utility')
					))
				);
			}

			$this->Form->appendChild($div);
		}

		public function __actionNew() {
			$this->__actionEdit();
		}

		public function __actionEdit(){

			$this->_existing_file = (isset($this->_context[1]) ? $this->_context[1] . '.xsl' : NULL);

			if(array_key_exists('save', $_POST['action']) || array_key_exists('done', $_POST['action'])){

				$fields = $_POST['fields'];

				$this->_errors = array();

				if(!isset($fields['name']) || trim($fields['name']) == '') $this->_errors['name'] = __('Name is a required field.');

				if(!isset($fields['body']) || trim($fields['body']) == '') $this->_errors['body'] = __('Body is a required field.');
				elseif(!General::validateXML($fields['body'], $errors)) $this->_errors['body'] = __('This document is not well formed. The following error was returned: <code>%s</code>', array($errors[0]->message));

				if(empty($this->_errors)){

					$fields['name'] = Lang::createFilename($fields['name']);
		            if(General::right($fields['name'], 4) != '.xsl') $fields['name'] .= '.xsl';

					$file = UTILITIES . '/' . $fields['name'];

					##Duplicate
					if($this->_context[0] == 'edit' && ($this->_existing_file != $fields['name'] && is_file($file)))
						$this->_errors['name'] = __('A Utility with that name already exists. Please choose another.');

					elseif($this->_context[0] == 'new' && is_file($file)) $this->_errors['name'] = __('A Utility with that name already exists. Please choose another.');

					##Write the file
					elseif(!$write = General::writeFile($file, $fields['body'], Symphony::Configuration()->get('write_mode', 'file')))
						$this->pageAlert(__('Utility could not be written to disk. Please check permissions on <code>/workspace/utilities</code>.'), Alert::ERROR);

					##Write Successful, add record to the database
					else{

						## Remove any existing file if the filename has changed
						if($this->_existing_file && $file != UTILITIES . '/' . $this->_existing_file)
							General::deleteFile(UTILITIES . '/' . $this->_existing_file);

						## TODO: Fix me
						###
						# Delegate: Edit
						# Description: After saving the asset, the file path is provided.
						//ExtensionManager::instance()->notifyMembers('Edit', getCurrentPage(), array('file' => $file));

						redirect(URL . '/symphony/blueprints/utilities/edit/'.str_replace('.xsl', '', $fields['name']) . '/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');

					}
				}
			}

			elseif($this->_context[0] == 'edit' && array_key_exists('delete', $_POST['action'])){

				## TODO: Fix me
				###
				# Delegate: Delete
				# Description: Prior to deleting the asset file. Target file path is provided.
				//ExtensionManager::instance()->notifyMembers('Delete', getCurrentPage(), array('file' => WORKSPACE . '/' . $this->_existing_file_rel));

		    	General::deleteFile(UTILITIES . '/' . $this->_existing_file);

		    	redirect(URL . '/symphony/blueprints/components/');
		  	}
		}

		protected function __actionDelete($utils, $redirect) {
			$success = true;

			if(!is_array($utils)) $utils = array($utils);

			foreach ($utils as $util) {
				General::deleteFile(UTILITIES . '/' . $util);
			}

			if($success) redirect($redirect);
		}

		public function __actionIndex() {
			$checked = array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						$this->__actionDelete($checked, URL . '/symphony/blueprints/utilities/');
						break;
				}
			}
		}

	}

?>
