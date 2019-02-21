<?php
/**
 * @package toolkit
 */
/**
 * The `FilterableEvent` class provides methods required to process filters on
 * data entered on the frontend.
 * It is also responsible to notify the proper extension delegates.
 *
 * @since Symphony 3.0.0
 * @link http://getsymphony.com/learn/concepts/view/events/
 */
abstract class FilterableEvent extends Event
{
    /**
     * An associative array of results from the filters that have run
     * on this event.
     * @var array
     */
    public $filter_results = array();

    /**
     * An associative array of errors from the filters that have run
     * on this event.
     * @var array
     */
    public $filter_errors = array();

    /**
     * Processes all extensions attached to the `EventPreSaveFilter` delegate
     *
     * @uses EventPreSaveFilter
     *
     * @param XMLElement $result
     * @param array $fields
     * @param XMLElement $post_values
     * @param integer $entry_id
     * @return boolean
     */
    protected function processPreSaveFilters(XMLElement $result, array &$fields, XMLElement &$post_values, $entry_id = null)
    {
        $can_proceed = true;

        /**
         * Prior to saving entry from the front-end. This delegate will
         * force the Event to terminate if it populates the `$filter_results`
         * array. All parameters are passed by reference.
         *
         * @delegate EventPreSaveFilter
         * @param string $context
         * '/frontend/'
         * @param array $fields
         * @param Event $this
         * @param array $messages
         *  An associative array of array's which contain 4 values,
         *  the name of the filter (string), the status (boolean),
         *  the message (string) an optionally an associative array
         *  of additional attributes to add to the filter element.
         * @param XMLElement $post_values
         * @param integer $entry_id
         *  If editing an entry, this parameter will be an integer,
         *  otherwise null.
         */
        Symphony::ExtensionManager()->notifyMembers(
            'EventPreSaveFilter',
            '/frontend/',
            array(
                'fields' => &$fields,
                'event' => &$this,
                'messages' => &$this->filter_results,
                'post_values' => &$post_values,
                'entry_id' => $entry_id
            )
        );

        // Logic taken from `event.section.php` to fail should any `$this->filter_results`
        // be returned. This delegate can cause the event to exit early.
        if (is_array($this->filter_results) && !empty($this->filter_results)) {
            $can_proceed = true;

            foreach ($this->filter_results as $fr) {
                list($name, $status, $message, $attributes) = array_pad($fr, 4, null);

                $result->appendChild(
                    self::buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes)
                );

                if ($status === false) {
                    $can_proceed = false;
                }
            }

            if ($can_proceed !== true) {
                $result->appendChild($post_values);
                $result->setAttribute('result', 'error');
                $result->appendChild(new XMLElement('message', __('Entry encountered errors when saving.'), array(
                    'message-id' => EventMessages::FILTER_FAILED
                )));
            }
        }

        // Reset the filter results to prevent duplicates. RE: #2179
        $this->filter_results = array();
        return $can_proceed;
    }

    /**
     * Processes all extensions attached to the `EventPostSaveFilter` delegate
     *
     * @uses EventPostSaveFilter
     *
     * @param XMLElement $result
     * @param array $fields
     * @param Entry $entry
     * @return XMLElement
     */
    protected function processPostSaveFilters(XMLElement $result, array $fields, Entry $entry = null)
    {
        /**
         * After saving entry from the front-end. This delegate will not force
         * the Events to terminate if it populates the `$filter_results` array.
         * Provided with references to this object, the `$_POST` data and also
         * the error array
         *
         * @delegate EventPostSaveFilter
         * @param string $context
         * '/frontend/'
         * @param integer $entry_id
         * @param array $fields
         * @param Entry $entry
         * @param Event $this
         * @param array $messages
         *  An associative array of array's which contain 4 values,
         *  the name of the filter (string), the status (boolean),
         *  the message (string) an optionally an associative array
         *  of additional attributes to add to the filter element.
         */
        Symphony::ExtensionManager()->notifyMembers('EventPostSaveFilter', '/frontend/', array(
            'entry_id' => $entry->get('id'),
            'fields' => $fields,
            'entry' => $entry,
            'event' => &$this,
            'messages' => &$this->filter_results
        ));

        if (is_array($this->filter_results) && !empty($this->filter_results)) {
            foreach ($this->filter_results as $fr) {
                list($name, $status, $message, $attributes) = $fr;

                $result->appendChild(
                    self::buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes)
                );
            }
        }

        // Reset the filter results to prevent duplicates. RE: #2179
        $this->filter_results = array();
        return $result;
    }

    /**
     * Processes all extensions attached to the `EventFinalSaveFilter` delegate
     *
     * @uses EventFinalSaveFilter
     *
     * @param XMLElement $result
     * @param array $fields
     * @param Entry $entry
     * @return XMLElement
     */
    protected function processFinalSaveFilters(XMLElement $result, array $fields, Entry $entry = null)
    {
        /**
         * This delegate that lets extensions know the final status of the
         * current Event. It is triggered when everything has processed correctly.
         * The `$messages` array contains the results of the previous filters that
         * have executed, and the `$errors` array contains any errors that have
         * occurred as a result of this delegate. These errors cannot stop the
         * processing of the Event, as that has already been done.
         *
         *
         * @delegate EventFinalSaveFilter
         * @param string $context
         * '/frontend/'
         * @param array $fields
         * @param Event $this
         * @param array $messages
         *  An associative array of array's which contain 4 values,
         *  the name of the filter (string), the status (boolean),
         *  the message (string) an optionally an associative array
         *  of additional attributes to add to the filter element.
         * @param array $errors
         *  An associative array of array's which contain 4 values,
         *  the name of the filter (string), the status (boolean),
         *  the message (string) an optionally an associative array
         *  of additional attributes to add to the filter element.
         * @param Entry $entry
         */
        Symphony::ExtensionManager()->notifyMembers('EventFinalSaveFilter', '/frontend/', array(
            'fields'    => $fields,
            'event'     => $this,
            'messages'  => $this->filter_results,
            'errors'    => &$this->filter_errors,
            'entry'     => $entry
        ));

        if (is_array($this->filter_errors) && !empty($this->filter_errors)) {
            foreach ($this->filter_errors as $fr) {
                list($name, $status, $message, $attributes) = $fr;

                $result->appendChild(
                    self::buildFilterElement($name, ($status ? 'passed' : 'failed'), $message, $attributes)
                );
            }
        }

        // Reset the filter results to prevent duplicates. RE: #2179
        $this->filter_results = array();
        return $result;
    }

    /**
     * This method will construct XML that represents the result of
     * an Event filter.
     *
     * @param string $name
     *  The name of the filter
     * @param string $status
     *  The status of the filter, either passed or failed.
     * @param XMLElement|string $message
     *  Optionally, an XMLElement or string to be appended to this
     *  `<filter>` element. XMLElement allows for more complex return
     *  types.
     * @param array $attributes
     *  An associative array of additional attributes to add to this
     *  `<filter>` element
     * @return XMLElement
     */
    public static function buildFilterElement($name, $status, $message = null, array $attributes = null)
    {
        $filter = new XMLElement('filter', (!$message || is_object($message) ? null : $message), array('name' => $name, 'status' => $status));

        if ($message instanceof XMLElement) {
            $filter->appendChild($message);
        }

        if (is_array($attributes)) {
            $filter->setAttributeArray($attributes);
        }

        return $filter;
    }
}
