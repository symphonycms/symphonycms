<?php
/**
 * @package toolkit
 */

/**
 * The abstract Event classes defines some base methods that all Events inherit.
 * It has one abstract method, `__trigger()`, which Events must implement. Event
 * execution is determined based on an action (which maps to a form action
 * from the Frontend). A load function determines whether this Event matches
 * the action and if so, call the Event's `__trigger()` to run the logic. On every page
 * load, all Event's that are attached to the page will have their load function's executed.
 * Events are called in order of their priority and if there is more than one event
 * with the same priority, in alphabetical order. An event class is saved through the
 * Symphony backend, which uses an event template defined in `TEMPLATE . /event.tpl`
 * Events implement the iEvent interface, which defines the load and about functions.
 */
abstract class Event
{
    /**
     * Represents High Priority, that this event should run first
     * @var integer
     */
    const kHIGH = 3;

    /**
     * Represents Normal Priority, that this event should run normally.
     * This is the default Event Priority
     * @var integer
     */
    const kNORMAL = 2;

    /**
     * Represents High Priority, that this event should run last
     * @var integer
     */
    const kLOW = 1;

    /**
     * Holds all the environment variables which include parameters set
     * by other Datasources or Events.
     * @var array
     */
    protected $_env = array();

    /**
     * The constructor for an Event sets `$this->_env` from the given parameters
     *
     * @param array $env
     *  The environment variables from the Frontend class which includes
     *  any params set by Symphony or Datasources or by other Events
     */
    public function __construct(array $env = null)
    {
        $this->_env = $env;
    }

    /**
     * This function is required in order to edit it in the event editor page.
     * Do not overload this function if you are creating a custom event. It is only
     * used by the event editor.
     *
     * @return boolean
     *   True if event can be edited, false otherwise. Defaults to false
     */
    public static function allowEditorToParse()
    {
        return false;
    }

    /**
     * This function is required in order to identify what section this event is for. It
     * is used in the event editor. It must remain intact. Do not overload this function in
     * custom events.
     *
     * @return integer
     */
    public static function getSource()
    {
        return null;
    }

    /**
     * Returns a string of HTML or an XMLElement of documentation for the current event.
     * By default this will be an example of a HTML form that can populate the chosen section and
     * any filter information. Documentation is shown in the Symphony backend when a user tries to
     * edit an event but it's `allowEditorToParse()` returns `false`. If this is not implemented by
     * the event, a default Symphony message will appear.
     *
     * @return string|XMLElement
     */
    public static function documentation()
    {
        return __('This event has been customised and cannot be viewed from Symphony.');
    }

    /**
     * Returns the path to the email-notification-template by looking at the
     * `WORKSPACE/template/` directory, then at the `TEMPLATES`
     * directory for the convention `notification.*.tpl`. If the template
     * is not found, false is returned
     *
     * @param string $language
     *  Language used in system
     * @return mixed
     *  String, which is the path to the template if the template is found,
     *  false otherwise
     */
    public static function getNotificationTemplate($language)
    {
        $langformat = '%s/email.entrycreated.%s.tpl';
        $defaultformat = '%s/email.entrycreated.tpl';

        if (file_exists($template = sprintf($langformat, WORKSPACE . '/template', $language))) {
            return $template;
        } elseif (file_exists($template = sprintf($defaultformat, WORKSPACE . '/template'))) {
            return $template;
        } elseif (file_exists($template = sprintf($langformat, TEMPLATE, $language))) {
            return $template;
        } elseif (file_exists($template = sprintf($defaultformat, TEMPLATE))) {
            return $template;
        } else {
            return false;
        }
    }


    /**
     * Priority determines Event importance and when it should be executed.
     * The default priority for an event is `Event::kNORMAL`, with `Event::kHIGH` and
     * `Event::kLOW` being the other available options. Events execution is `Event::kHIGH`
     * to `Event::kNORMAL` to `Event::kLOW`. If there are more than one event at the
     * same priority level, they are sorted alphabetically by event handle and executed
     * in that order for that priority.
     *
     * @see toolkit.FrontendPage#__findEventOrder()
     * @return integer
     *  The available constants are `Event::kLOW`, `Event::kNORMAL` and `Event::kHIGH`.
     *  Defaults to `Event::kNORMAL`
     */
    public function priority()
    {
        return self::kNORMAL;
    }

    /**
     * This function must be included in an event. The purpose of this function
     * is to define the logic of this particular event. It assumes that this event
     * has already been triggered from the load function
     *
     * @since Symphony 2.3
     * @return XMLElement
     *  Returns an `XMLElement` with the event information (success or failure included)
     */
    protected function __trigger()
    {
        return $this->execute();
    }
}
