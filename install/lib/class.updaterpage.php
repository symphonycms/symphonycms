<?php

    /**
     * @package content
     */

    Class UpdaterPage extends InstallerPage
    {

        public function __construct($template, $params = array())
        {
            parent::__construct($template, $params);

            $this->_template = $template;
            $this->_page_title = __('Update Symphony');
        }

        protected function __build($version = VERSION, XMLElement $extra = null)
        {
            parent::__build(
                // Replace the installed version with the updated version
                isset($this->_params['version'])
                    ? $this->_params['version']
                    : Symphony::Configuration()->get('version', 'symphony')
            );

            // Add Release Notes for the latest migration
            if(isset($this->_params['release-notes'])){
                $nodeset = $this->Form->getChildrenByName('h1');
                $h1 = end($nodeset);
                if($h1 instanceof XMLElement) {
                    $h1->appendChild(
                        new XMLElement(
                            'em',
                            Widget::Anchor(__('Release Notes'), $this->_params['release-notes'])
                        )
                    );
                }
            }
        }

        protected function viewUptodate()
        {
            $h2 = new XMLElement('h2', __('Symphony is already up-to-date'));
            $p = new XMLElement('p', __('It appears that Symphony has already been installed at this location and is up to date.'));

            $this->Form->appendChild($h2);
            $this->Form->appendChild($p);
        }

        protected function viewReady()
        {
            $h2 = new XMLElement('h2', __('Updating Symphony'));
            $p = new XMLElement('p', __('This script will update your existing Symphony installation to version %s.', array('<code>' . $this->_params['version'] . '</code>')));

            $this->Form->appendChild($h2);
            $this->Form->appendChild($p);

            if(!is_writable(CONFIG)) {
                $this->Form->appendChild(
                    new XMLElement('p', __('Please check that your configuration file is writable before proceeding'), array('class' => 'warning'))
                );
            }

            if(!empty($this->_params['pre-notes'])){
                $h2 = new XMLElement('h2', __('Pre-Installation Notes:'));
                $dl = new XMLElement('dl');

                foreach($this->_params['pre-notes'] as $version => $notes){
                    $dl->appendChild(new XMLElement('dt', $version));
                    foreach($notes as $note) {
                        $dl->appendChild(new XMLElement('dd', $note));
                    }
                }

                $this->Form->appendChild($h2);
                $this->Form->appendChild($dl);
            }

            $submit = new XMLElement('div', null, array('class' => 'submit'));
            $submit->appendChild(Widget::input('action[update]', __('Update Symphony'), 'submit'));

            $this->Form->appendChild($submit);
        }

        protected function viewFailure()
        {
            $h2 = new XMLElement('h2', __('Updating Failure'));
            $p = new XMLElement('p', __('An error occurred while updating Symphony.'));

            // Attempt to get update information from the log file
            try {
                $log = file_get_contents(INSTALL_LOGS . '/update');
            }
            catch (Exception $ex) {
                $log_entry = Symphony::Log()->popFromLog();
                if(isset($log_entry['message'])) {
                    $log = $log_entry['message'];
                }
                else {
                    $log = 'Unknown error occurred when reading the update log';
                }
            }

            $code = new XMLElement('code', $log);

            $this->Form->appendChild($h2);
            $this->Form->appendChild($p);
            $this->Form->appendChild(
                new XMLElement('pre', $code)
            );
        }

        protected function viewSuccess()
        {
            $this->Form->setAttribute('action', SYMPHONY_URL);

            $h2 = new XMLElement('h2', __('Updating Complete'));
            $this->Form->appendChild($h2);

            if(!empty($this->_params['post-notes'])){
                $dl = new XMLElement('dl');

                foreach($this->_params['post-notes'] as $version => $notes){
                    if($notes) {
                        $dl->appendChild(new XMLElement('dt', $version));
                        foreach($notes as $note) {
                            $dl->appendChild(new XMLElement('dd', $note));
                        }
                    }
                }

                $this->Form->appendChild($dl);
            }

            $this->Form->appendChild(
                new XMLElement('p',
                    __('And the crowd goes wild! A victory dance is in order; and look, your mum is watching. She\'s proud.', array(Symphony::Configuration()->get('sitename', 'general')))
                )
            );
            $this->Form->appendChild(
                new XMLElement('p',
                    __('Your mum is also nagging you about removing that %s directory before you log in.', array('<code>' . basename(INSTALL_URL) . '</code>'))
                )
            );

            $submit = new XMLElement('div', null, array('class' => 'submit'));
            $submit->appendChild(Widget::input('submit', __('Complete'), 'submit'));

            $this->Form->appendChild($submit);
        }

    }
