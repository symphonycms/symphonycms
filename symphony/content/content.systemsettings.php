<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	class contentSystemSettings extends AdministrationPage {
		public function __construct(){
			parent::__construct();

			## DEPRECATED $this->setPageType('form');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Settings'))));
		}

		## Overload the parent 'view' function since we dont need the switchboard logic
		public function __viewIndex() {
			$this->appendSubheading(__('Settings'));

			$path = URL . '/symphony/system/settings/';

			$viewoptions = array(
				'subnav'	=> array(
					'Preferences'		=>	$path,
					'Tools'				=>	$path . 'tools/'
				)
			);

			$this->appendViewOptions($viewoptions);

		    $bIsWritable = true;
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

		    if (!is_writable(CONFIG)) {
		        $this->pageAlert(__('The Symphony configuration file, <code>/manifest/config.php</code>, is not writable. You will not be able to save changes to preferences.'), Alert::ERROR);
		        $bIsWritable = false;

		    } else if ($formHasErrors) {
		    	$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

		    } else if (isset($this->_context[0]) && $this->_context[0] == 'success') {
		    	$this->pageAlert(__('Preferences saved.'), Alert::SUCCESS);
		    }

		// SETUP PAGE
			$layout = new Layout(3, '2:1:1');

		// SITE SETUP
			$helptext = 'Symphony version: ' . Symphony::Configuration()->get('version', 'symphony');
			$fieldset = Widget::Fieldset(__('Site Setup'), $helptext);

			$label = Widget::Label(__('Site Name'));
			$input = Widget::Input('settings[symphony][sitename]', Symphony::Configuration()->core()->symphony->sitename);
			$label->appendChild($input);

			$fieldset->appendChild($label);

		    // Get available languages
		    $languages = Lang::getAvailableLanguages(true);

			if(count($languages) > 1) {
			    // Create language selection
				$label = Widget::Label(__('Default Language'));

				// Get language names
				asort($languages);

				foreach($languages as $code => $name) {
					$options[] = array($code, $code == Symphony::Configuration()->core()->symphony->lang, $name);
				}
				$select = Widget::Select('settings[symphony][lang]', $options);
				unset($options);
				$label->appendChild($select);
				//$group->appendChild(new XMLElement('p', __('Users can set individual language preferences in their profiles.'), array('class' => 'help')));
				// Append language selection
				$fieldset->appendChild($label);
			}
			$layout->appendToCol($fieldset, 1);

		// REGIONAL SETTINGS

			$fieldset = Widget::Fieldset(__('Date &amp; Time Settings'));

			// Date and Time Settings
			$label = Widget::Label(__('Date Format'));
			$input = Widget::Input('settings[region][date-format]', Symphony::Configuration()->core()->region->{'date-format'});
			$label->appendChild($input);
			$fieldset->appendChild($label);

			$label = Widget::Label(__('Time Format'));
			$input = Widget::Input('settings[region][time-format]', Symphony::Configuration()->core()->region->{'time-format'});
			$label->appendChild($input);
			$fieldset->appendChild($label);

			$label = Widget::Label(__('Timezone'));

			$timezones = timezone_identifiers_list();
			foreach($timezones as $timezone) {
				$options[] = array($timezone, $timezone == Symphony::Configuration()->core()->region->timezone, $timezone);
				}
			$select = Widget::Select('settings[region][timezone]', $options);
			unset($options);
			$label->appendChild($select);
			$fieldset->appendChild($label);

			$layout->appendToCol($fieldset, 2);

		// PERMISSIONS

			$fieldset = Widget::Fieldset(__('Permissions'));

			$permissions = array(
				'0777',
				'0775',
				'0755',
				'0666',
				'0644'
			);

			$fileperms = Symphony::Configuration()->core()->symphony->{'file-write-mode'};
			$dirperms = Symphony::Configuration()->core()->symphony->{'directory-write-mode'};

			$label = Widget::Label(__('File Permissions'));
			foreach($permissions as $p) {
				$options[] = array($p, $p == $fileperms, $p);
			}
			if(!in_array($fileperms, $permissions)){
				$options[] = array($fileperms, true, $fileperms);
			}
			$select = Widget::Select('settings[symphony][file-write-mode]', $options);
			unset($options);
			$label->appendChild($select);
			$fieldset->appendChild($label);

			$label = Widget::Label(__('Directory Permissions'));
			foreach($permissions as $p) {
				$options[] = array($p, $p == $dirperms, $p);
			}
			if(!in_array($dirperms, $permissions)){
				$options[] = array($dirperms, true, $dirperms);
			}
			$select = Widget::Select('settings[symphony][directory-write-mode]', $options);
			unset($options);
			$label->appendChild($select);
			$fieldset->appendChild($label);

			$layout->appendToCol($fieldset, 3);

			###
			# Delegate: AddCustomPreferenceFieldsets
			# Description: Add Extension custom preferences. Use the $wrapper reference to append objects.
			ExtensionManager::instance()->notifyMembers('AddCustomPreferenceFieldsets', '/system/settings/', array('wrapper' => &$this->Form));

			$this->Form->appendChild($layout->generate());

			$div = $this->createElement('div');
			$div->setAttribute('class', 'actions');

			$attr = array('accesskey' => 's');
			if(!$bIsWritable) $attr['disabled'] = 'disabled';
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);
		}

		public function __viewTools() {
			$this->appendSubheading(__('Settings'));

			$path = URL . '/symphony/system/settings/';

			$viewoptions = array(
				'subnav'	=> array(
					'Preferences'		=>	$path,
					'Tools'				=>	$path . 'tools/'
				)
			);

			$this->appendViewOptions($viewoptions);

		    $bIsWritable = true;
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

		    if (!is_writable(CONFIG)) {
		        $this->pageAlert(__('The Symphony configuration file, <code>/manifest/config.php</code>, is not writable. You will not be able to save changes to preferences.'), Alert::ERROR);
		        $bIsWritable = false;

		    }

			elseif ($formHasErrors) {
		    	$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

		    }

			elseif (isset($this->_context[0]) && $this->_context[0] == 'success') {
		    	$this->pageAlert(__('Preferences saved.'), Alert::SUCCESS);
		    }

			###
			# Delegate: AddCustomToolFieldsets
			# Description: Add Extension custom tools. Use the $wrapper reference to append objects.
			ExtensionManager::instance()->notifyMembers('AddCustomToolFieldsets', '/system/settings/', array('wrapper' => &$this->Form));

			$fieldset = $this->createElement('fieldset');
			$fieldset->setAttribute('class', 'settings');

			/*
				Do not show sections or fields that have not changed.
				Add .added or .removed to the table row on the field and/or section.
				@rowspan is calculated by the number of fields + 1.
			*/
			$fieldset->setValue('
				<legend>Section Update</legend>
				<div id="sections-tool">
					<table>
						<thead>
							<tr>
								<th class="status">Status</th>
								<th>Section</th>
								<th>Content</th>
							</tr>
						</thead>
						<tbody>
							<tr class="added">
								<td class="status">Added</td>
								<td>Articles</td>
								<td><span>Input Field</span>Title</td>
							</tr>
							<tr class="removed">
								<td class="status">Removed</td>
								<td>Articles</td>
								<td><span>Textarea</span>Body</td>
							</tr>
							<tr class="removed">
								<td class="status">Removed</td>
								<td>Articles</td>
								<td><span>Checkbox</span>Publish</td>
							</tr>
							<tr class="added">
								<td class="status">Added</td>
								<td>Forum</td>
								<td>6 fields added</td>
							</tr>
							<tr class="removed">
								<td class="status">Removed</td>
								<td>Staff Members</td>
								<td>10 fields removed</td>
							</tr>
						</tbody>
					</table>
					<div>
						<p>Updates the database structure. <strong>332</strong> existing entries will be effected</p>
						<input name="action[export]" type="submit" value="Update Sections" />
					</div>
				</div>');

			$this->Form->appendChild($fieldset);

			$div = $this->createElement('div');
			$div->setAttribute('class', 'actions');

			$attr = array('accesskey' => 's');
			if(!$bIsWritable) $attr['disabled'] = 'disabled';
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);
		}

		public function action() {
			##Do not proceed if the config file is read only
		    if (!is_writable(CONFIG)) redirect(ADMIN_URL . '/system/settings/');

			###
			# Delegate: CustomActions
			# Description: This is where Extensions can hook on to custom actions they may need to provide.
			ExtensionManager::instance()->notifyMembers('CustomActions', '/system/settings/');

			if (isset($_POST['action']['save'])) {
				$settings = $_POST['settings'];

				###
				# Delegate: Save
				# Description: Saving of system preferences.
				ExtensionManager::instance()->notifyMembers('Save', '/system/settings/', array('settings' => &$settings, 'errors' => &$this->_errors));

				if (!is_array($this->_errors) || empty($this->_errors)) {

					if(is_array($settings) && !empty($settings)){
						foreach($settings as $set => $values) {
							foreach($values as $key => $val) {
								Symphony::Configuration()->set($key, $val, $set);
							}
						}
					}

					Administration::instance()->saveConfig();

					redirect(ADMIN_URL . '/system/preferences/success/');
				}
			}
		}
	}
