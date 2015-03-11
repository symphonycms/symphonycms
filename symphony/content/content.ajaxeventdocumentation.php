<?php
/**
 * @package content
 */
/**
 * The AjaxEventDocumentation returns the documentation for a particular
 * event by invoking all fields to return their documentation.
 * Accepts three parameters, `section`, `filters` and `name`.
 */
class contentAjaxEventDocumentation extends TextPage
{

    public function __construct()
    {
        parent::__construct();
        $this->addHeaderToPage('Content-Type', 'text/html');
    }

    public function view()
    {
        $name = General::sanitize($_REQUEST['name']);
        $section = General::sanitize($_REQUEST['section']);
        $filters = self::processFilters($_REQUEST['filters']);
        $rootelement = Lang::createHandle($name);
        $doc_parts = array();

        // Add Documentation (Success/Failure)
        $this->addEntrySuccessDoc($doc_parts, $rootelement, $filters);
        $this->addEntryFailureDoc($doc_parts, $rootelement, $filters);

        // Filters
        $this->addDefaultFiltersDoc($doc_parts, $rootelement, $filters);

        // Frontend Markup
        $this->addFrontendMarkupDoc($doc_parts, $rootelement, $section, $filters);
        $this->addSendMailFilterDoc($doc_parts, $filters);

        /**
         * Allows adding documentation for new filters. A reference to the $documentation
         * array is provided, along with selected filters
         *
         * @delegate AppendEventFilterDocumentation
         * @param string $context
         * '/blueprints/events/(edit|new|info)/'
         * @param array $selected
         *  An array of all the selected filters for this Event
         * @param array $documentation
         *  An array of all the documentation XMLElements, passed by reference
         * @param string $rootelment
         *  The name of this event, as a handle.
         */
        Symphony::ExtensionManager()->notifyMembers('AppendEventFilterDocumentation', '/blueprints/events/', array(
            'selected' => $filters,
            'documentation' => &$doc_parts,
            'rootelement' => $rootelement
        ));

        $documentation = join(PHP_EOL, array_map(create_function('$x', 'return rtrim($x->generate(true, 4));'), $doc_parts));
        $documentation = str_replace('\'', '\\\'', $documentation);

        $documentation = '<fieldset id="event-documentation" class="settings"><legend>' . __('Documentation') . '</legend>' . $documentation . '</fieldset>';
        $this->_Result = $documentation;
    }

    /**
     * Utilities
     */
    public static function hasMultipleFilter($filters)
    {
        if (!is_array($filters)) {
            return false;
        }

        return in_array('expect-multiple', $filters);
    }

    public static function hasSendEmailFilter($filters)
    {
        if (!is_array($filters)) {
            return false;
        }

        return in_array('send-email', $filters);
    }

    public static function processFilters($filters)
    {
        $filter_names = array();

        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $filter) {
                $filter_names[] = $filter['value'];
            }
        }

        return $filter_names;
    }

    public static function processDocumentationCode($code)
    {
        return new XMLElement('pre', '<code>' . str_replace('<', '&lt;', str_replace('&', '&amp;', trim((is_object($code) ? $code->generate(true) : $code)))) . '</code>', array('class' => 'XML'));
    }

    public function addEntrySuccessDoc(array &$doc_parts, $rootelement, $filters)
    {
        $doc_parts[] = new XMLElement('h3', __('Success and Failure XML Examples'));
        $doc_parts[] = new XMLElement('p', __('When saved successfully, the following XML will be returned:'));

        if ($this->hasMultipleFilter($filters)) {
            $code = new XMLElement($rootelement);
            $entry = new XMLElement('entry', null, array('index' => '0', 'result' => 'success' , 'type' => 'create | edit'));
            $entry->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));

            $code->appendChild($entry);
        } else {
            $code = new XMLElement($rootelement, null, array('result' => 'success' , 'type' => 'create | edit'));
            $code->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));
        }

        $doc_parts[] = self::processDocumentationCode($code);
    }

    public function addEntryFailureDoc(array &$doc_parts, $rootelement, $filters)
    {
        $doc_parts[] = new XMLElement('p', __('When an error occurs during saving, due to either missing or invalid fields, the following XML will be returned.'));

        if ($this->hasMultipleFilter($filters)) {
            $doc_parts[] = new XMLElement('p', __('Notice that it is possible to get mixtures of success and failure messages when using the ‘Allow Multiple’ option.'));
            $code = new XMLElement($rootelement);

            $entry = new XMLElement('entry', null, array('index' => '0', 'result' => 'error'));
            $entry->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
            $entry->appendChild(new XMLElement('field-name', null, array('type' => 'invalid | missing')));
            $code->appendChild($entry);

            $entry = new XMLElement('entry', null, array('index' => '1', 'result' => 'success' , 'type' => 'create | edit'));
            $entry->appendChild(new XMLElement('message', __('Entry [created | edited] successfully.')));
            $code->appendChild($entry);
        } else {
            $code = new XMLElement($rootelement, null, array('result' => 'error'));
            $code->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
            $code->appendChild(new XMLElement('field-name', null, array('type' => 'invalid | missing')));
        }

        $code->setValue('...');
        $doc_parts[] = self::processDocumentationCode($code);
    }

    public function addDefaultFiltersDoc(array &$doc_parts, $rootelement, $filters)
    {
        if (is_array($filters) && !empty($filters)) {
            $doc_parts[] = new XMLElement('p', __('The following is an example of what is returned if any options return an error:'));

            $code = new XMLElement($rootelement, null, array('result' => 'error'));
            $code->appendChild(new XMLElement('message', __('Entry encountered errors when saving.')));
            $code->appendChild(new XMLElement('filter', null, array('name' => 'admin-only', 'status' => 'failed')));
            $code->appendChild(new XMLElement('filter', __('Recipient not found'), array('name' => 'send-email', 'status' => 'failed')));
            $code->setValue('...');

            $doc_parts[] = self::processDocumentationCode($code);
        }
    }

    public function addFrontendMarkupDoc(array &$doc_parts, $rootelement, $section, $filters)
    {
        $multiple = $this->hasMultipleFilter($filters);
        $doc_parts[] = new XMLElement('h3', __('Example Front-end Form Markup'));
        $doc_parts[] = new XMLElement('p', __('This is an example of the form markup you can use on your frontend:'));
        $container = new XMLElement('form', null, array('method' => 'post', 'action' => '{$current-url}/', 'enctype' => 'multipart/form-data'));
        $container->appendChild(Widget::Input('MAX_FILE_SIZE', (string)min(ini_size_to_bytes(ini_get('upload_max_filesize')), Symphony::Configuration()->get('max_upload_size', 'admin')), 'hidden'));

        if (is_numeric($section)) {
            $section = SectionManager::fetch($section);
            if ($section instanceof Section) {
                $section_fields = $section->fetchFields();
                if (is_array($section_fields) && !empty($section_fields)) {
                    foreach ($section_fields as $f) {
                        if ($f->getExampleFormMarkup() instanceof XMLElement) {
                            $container->appendChild($f->getExampleFormMarkup());
                        }
                    }
                }
            }
        }

        $container->appendChild(Widget::Input('action['.$rootelement.']', __('Submit'), 'submit'));
        $code = $container->generate(true);

        $doc_parts[] = self::processDocumentationCode(($multiple ? str_replace('fields[', 'fields[0][', $code) : $code));

        $doc_parts[] = new XMLElement('p', __('To edit an existing entry, include the entry ID value of the entry in the form. This is best as a hidden field like so:'));
        $doc_parts[] = self::processDocumentationCode(Widget::Input('id' . ($multiple ? '[0]' : null), '23', 'hidden'));

        $doc_parts[] = new XMLElement('p', __('To redirect to a different location upon a successful save, include the redirect location in the form. This is best as a hidden field like so, where the value is the URL to redirect to:'));
        $doc_parts[] = self::processDocumentationCode(Widget::Input('redirect', URL.'/success/', 'hidden'));
    }

    public function addSendMailFilterDoc(array &$doc_parts, $filters)
    {
        if ($this->hasSendEmailFilter($filters)) {
            $doc_parts[] = new XMLElement('h3', __('Send Notification Email'));
            $doc_parts[] = new XMLElement('p',
                __('Upon the event successfully saving the entry, this option takes input from the form and send an email to the desired recipient.')
                . ' <strong>'
                . __('It currently does not work with ‘Allow Multiple’')
                . '</strong>. '
                . __('The following are the recognised fields:')
            );

            $doc_parts[] = self::processDocumentationCode(
                'send-email[sender-email] // '.__('Optional').PHP_EOL.
                'send-email[sender-name] // '.__('Optional').PHP_EOL.
                'send-email[reply-to-email] // '.__('Optional').PHP_EOL.
                'send-email[reply-to-name] // '.__('Optional').PHP_EOL.
                'send-email[subject]'.PHP_EOL.
                'send-email[body]'.PHP_EOL.
                'send-email[recipient] // '.__('list of comma-separated author usernames.'));

            $doc_parts[] = new XMLElement('p', __('All of these fields can be set dynamically using the exact field name of another field in the form as shown below in the example form:'));
            $doc_parts[] = self::processDocumentationCode('<form action="" method="post">
<fieldset>
<label>'.__('Name').' <input type="text" name="fields[author]" value="" /></label>
<label>'.__('Email').' <input type="text" name="fields[email]" value="" /></label>
<label>'.__('Message').' <textarea name="fields[message]" rows="5" cols="21"></textarea></label>
<input name="send-email[sender-email]" value="fields[email]" type="hidden" />
<input name="send-email[sender-name]" value="fields[author]" type="hidden" />
<input name="send-email[reply-to-email]" value="fields[email]" type="hidden" />
<input name="send-email[reply-to-name]" value="fields[author]" type="hidden" />
<input name="send-email[subject]" value="You are being contacted" type="hidden" />
<input name="send-email[body]" value="fields[message]" type="hidden" />
<input name="send-email[recipient]" value="fred" type="hidden" />
<input id="submit" type="submit" name="action[save-contact-form]" value="Send" />
</fieldset>
</form>'
);
        }
    }
}
