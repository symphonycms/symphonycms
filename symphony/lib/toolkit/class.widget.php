<?php
/**
 * @package toolkit
 */
/**
 * Widget is a utility class that offers a number miscellaneous of
 * functions to help generate common HTML Forms elements as XMLElement
 * objects for inclusion in Symphony backend pages.
 */
class Widget
{
    /**
     * Generates a XMLElement representation of `<label>`
     *
     * @param string $name (optional)
     *  The text for the resulting `<label>`
     * @param XMLElement $child (optional)
     *  An XMLElement that this <label> will become the parent of.
     *  Commonly used with `<input>`.
     * @param string $class (optional)
     *  The class attribute of the resulting `<label>`
     * @param string $id (optional)
     *  The id attribute of the resulting `<label>`
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Label($name = null, XMLElement $child = null, $class = null, $id = null, array $attributes = null)
    {
        General::ensureType(array(
            'name' => array('var' => $name, 'type' => 'string', 'optional' => true),
            'class' => array('var' => $class, 'type' => 'string', 'optional' => true),
            'id' => array('var' => $id, 'type' => 'string', 'optional' => true)
        ));

        $obj = new XMLElement('label', ($name ? $name : null));

        if (is_object($child)) {
            $obj->appendChild($child);
        }

        if ($class) {
            $obj->setAttribute('class', $class);
        }

        if ($id) {
            $obj->setAttribute('id', $id);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        return $obj;
    }

    /**
     * Generates a XMLElement representation of `<input>`
     *
     * @param string $name
     *  The name attribute of the resulting `<input>`
     * @param string $value (optional)
     *  The value attribute of the resulting `<input>`
     * @param string $type
     *  The type attribute for this `<input>`, defaults to "text".
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Input($name, $value = null, $type = 'text', array $attributes = null)
    {
        General::ensureType(array(
            'name' => array('var' => $name, 'type' => 'string'),
            'value' => array('var' => $value, 'type' => 'string', 'optional' => true),
            'type' => array('var' => $type, 'type' => 'string', 'optional' => true),
        ));

        $obj = new XMLElement('input');
        $obj->setAttribute('name', $name);

        if ($type) {
            $obj->setAttribute('type', $type);
        }

        if (strlen($value) !== 0) {
            $obj->setAttribute('value', $value);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        return $obj;
    }

    /**
     * Generates a XMLElement representation of a `<input type='checkbox'>`. This also
     * includes the actual label of the Checkbox and any help text if required. Note that
     * this includes two input fields, one is the hidden 'no' value and the other
     * is the actual checkbox ('yes' value). This is provided so if the checkbox is
     * not checked, 'no' is still sent in the form request for this `$name`.
     *
     * @since Symphony 2.5.2
     * @param string $name
     *  The name attribute of the resulting checkbox
     * @param string $value
     *  The value attribute of the resulting checkbox
     * @param string $description
     *  This will be localisable and displayed after the checkbox when
     *  generated.
     * @param XMLElement $wrapper
     *  Passed by reference, if this is provided the elements will be automatically
     *  added to the wrapper, otherwise they will just be returned.
     * @param string $help (optional)
     *  A help message to show below the checkbox.
     * @throws InvalidArgumentException
     * @return XMLElement
     *  The markup for the label and the checkbox.
     */
    public static function Checkbox($name, $value, $description = null, XMLElement &$wrapper = null, $help = null)
    {
        General::ensureType(array(
            'name' => array('var' => $name, 'type' => 'string'),
            'value' => array('var' => $value, 'type' => 'string', 'optional' => true),
            'description' => array('var' => $description, 'type' => 'string'),
            'help' => array('var' => $help, 'type' => 'string', 'optional' => true),
        ));

        // Build the label
        $label = Widget::Label();
        if ($help) {
            $label->addClass('inline-help');
        }

        // Add the 'no' default option to the label, or to the wrapper if it's provided
        $default_hidden = Widget::Input($name, 'no', 'hidden');
        if(is_null($wrapper)) {
            $label->appendChild($default_hidden);
        }
        else {
            $wrapper->appendChild($default_hidden);
        }

        // Include the actual checkbox.
        $input = Widget::Input($name, 'yes', 'checkbox');
        if ($value === 'yes') {
            $input->setAttribute('checked', 'checked');
        }

        // Build the checkbox, then label, then help
        $label->setValue(__('%s ' . $description . ' %s', array(
            $input->generate(),
            ($help) ? ' <i>(' . $help . ')</i>' : ''
        )));

        // If a wrapper was given, add the label to it
        if(!is_null($wrapper)) {
            $wrapper->appendChild($label);
        }

        return $label;
    }

    /**
     * Generates a XMLElement representation of `<textarea>`
     *
     * @param string $name
     *  The name of the resulting `<textarea>`
     * @param integer $rows (optional)
     *  The height of the `<textarea>`, using the rows attribute. Defaults to 15
     * @param integer $cols (optional)
     *  The width of the `<textarea>`, using the cols attribute. Defaults to 50.
     * @param string $value (optional)
     *  The content to be displayed inside the `<textarea>`
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Textarea($name, $rows = 15, $cols = 50, $value = null, array $attributes = null)
    {
        General::ensureType(array(
            'name' => array('var' => $name, 'type' => 'string'),
            'rows' => array('var' => $rows, 'type' => 'int'),
            'cols' => array('var' => $cols, 'type' => 'int'),
            'value' => array('var' => $value, 'type' => 'string', 'optional' => true)
        ));

        $obj = new XMLElement('textarea', $value);

        $obj->setSelfClosingTag(false);

        $obj->setAttribute('name', $name);
        $obj->setAttribute('rows', $rows);
        $obj->setAttribute('cols', $cols);

        if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        return $obj;
    }

    /**
     * Generates a XMLElement representation of `<a>`
     *
     * @param string $value
     *  The text of the resulting `<a>`
     * @param string $href
     *  The href attribute of the resulting `<a>`
     * @param string $title (optional)
     *  The title attribute of the resulting `<a>`
     * @param string $class (optional)
     *  The class attribute of the resulting `<a>`
     * @param string $id (optional)
     *  The id attribute of the resulting `<a>`
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Anchor($value, $href, $title = null, $class = null, $id = null, array $attributes = null)
    {
        General::ensureType(array(
            'value' => array('var' => $value, 'type' => 'string'),
            'href' => array('var' => $href, 'type' => 'string'),
            'title' => array('var' => $title, 'type' => 'string', 'optional' => true),
            'class' => array('var' => $class, 'type' => 'string', 'optional' => true),
            'id' => array('var' => $id, 'type' => 'string', 'optional' => true)
        ));

        $obj = new XMLElement('a', $value);
        $obj->setAttribute('href', $href);

        if ($title) {
            $obj->setAttribute('title', $title);
        }

        if ($class) {
            $obj->setAttribute('class', $class);
        }

        if ($id) {
            $obj->setAttribute('id', $id);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        return $obj;
    }

    /**
     * Generates a XMLElement representation of `<form>`
     *
     * @param string $action
     *  The text of the resulting `<form>`
     * @param string $method
     *  The method attribute of the resulting `<form>`. Defaults to "post".
     * @param string $class (optional)
     *  The class attribute of the resulting `<form>`
     * @param string $id (optional)
     *  The id attribute of the resulting `<form>`
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Form($action = null, $method = 'post', $class = null, $id = null, array $attributes = null)
    {
        General::ensureType(array(
            'action' => array('var' => $action, 'type' => 'string', 'optional' => true),
            'method' => array('var' => $method, 'type' => 'string'),
            'class' => array('var' => $class, 'type' => 'string', 'optional' => true),
            'id' => array('var' => $id, 'type' => 'string', 'optional' => true)
        ));

        $obj = new XMLElement('form');
        $obj->setAttribute('action', $action);
        $obj->setAttribute('method', $method);

        if ($class) {
            $obj->setAttribute('class', $class);
        }

        if ($id) {
            $obj->setAttribute('id', $id);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        return $obj;
    }

    /**
     * Generates a XMLElement representation of `<table>`
     * This is a simple way to create generic Symphony table wrapper
     *
     * @param XMLElement $header
     *  An XMLElement containing the `<thead>`. See Widget::TableHead
     * @param XMLElement $footer
     *  An XMLElement containing the `<tfoot>`
     * @param XMLElement $body
     *  An XMLElement containing the `<tbody>`. See Widget::TableBody
     * @param string $class (optional)
     *  The class attribute of the resulting `<table>`
     * @param string $id (optional)
     *  The id attribute of the resulting `<table>`
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Table(XMLElement $header = null, XMLElement $footer = null, XMLElement $body = null, $class = null, $id = null, Array $attributes = null)
    {
        General::ensureType(array(
            'class' => array('var' => $class, 'type' => 'string', 'optional' => true),
            'id' => array('var' => $id, 'type' => 'string', 'optional' => true)
        ));

        $obj = new XMLElement('table');

        if ($class) {
            $obj->setAttribute('class', $class);
        }

        if ($id) {
            $obj->setAttribute('id', $id);
        }

        if ($header) {
            $obj->appendChild($header);
        }

        if ($footer) {
            $obj->appendChild($footer);
        }

        if ($body) {
            $obj->appendChild($body);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        return $obj;
    }

    /**
     * Generates a XMLElement representation of `<thead>` from an array
     * containing column names and any other attributes.
     *
     * @param array $columns
     *  An array of column arrays, where the first item is the name of the
     *  column, the second is the scope attribute, and the third is an array
     *  of possible attributes.
     *  `
     *   array(
     *      array('Column Name', 'scope', array('class'=>'th-class'))
     *   )
     *  `
     * @return XMLElement
     */
    public static function TableHead(array $columns = null)
    {
        $thead = new XMLElement('thead');
        $tr = new XMLElement('tr');

        if (is_array($columns) && !empty($columns)) {
            foreach ($columns as $col) {
                $th = new XMLElement('th');

                if (is_object($col[0])) {
                    $th->appendChild($col[0]);
                } else {
                    $th->setValue($col[0]);
                }

                if ($col[1] && $col[1] != '') {
                    $th->setAttribute('scope', $col[1]);
                }

                if (is_array($col[2]) && !empty($col[2])) {
                    $th->setAttributeArray($col[2]);
                }

                $tr->appendChild($th);
            }
        }

        $thead->appendChild($tr);

        return $thead;
    }

    /**
     * Generates a XMLElement representation of `<tbody>` from an array
     * containing `<tr>` XMLElements
     *
     * @see toolkit.Widget#TableRow()
     * @param array $rows
     *  An array of XMLElements of `<tr>`'s.
     * @param string $class (optional)
     *  The class attribute of the resulting `<tbody>`
     * @param string $id (optional)
     *  The id attribute of the resulting `<tbody>`
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function TableBody(array $rows, $class = null, $id = null, array $attributes = null)
    {
        General::ensureType(array(
            'class' => array('var' => $class, 'type' => 'string', 'optional' => true),
            'id' => array('var' => $id, 'type' => 'string', 'optional' => true)
        ));

        $tbody = new XMLElement('tbody');

        if ($class) {
            $tbody->setAttribute('class', $class);
        }

        if ($id) {
            $tbody->setAttribute('id', $id);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $tbody->setAttributeArray($attributes);
        }

        foreach ($rows as $row) {
            $tbody->appendChild($row);
        }

        return $tbody;
    }

    /**
     * Generates a XMLElement representation of `<tr>` from an array
     * containing column names and any other attributes.
     *
     * @param array $cells
     *  An array of XMLElements of `<td>`'s. See Widget::TableData
     * @param string $class (optional)
     *  The class attribute of the resulting `<tr>`
     * @param string $id (optional)
     *  The id attribute of the resulting `<tr>`
     * @param integer $rowspan (optional)
     *  The rowspan attribute of the resulting `<tr>`
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function TableRow(array $cells, $class = null, $id = null, $rowspan = null, Array $attributes = null)
    {
        General::ensureType(array(
            'class' => array('var' => $class, 'type' => 'string', 'optional' => true),
            'id' => array('var' => $id, 'type' => 'string', 'optional' => true),
            'rowspan' => array('var' => $rowspan, 'type' => 'int', 'optional' => true)
        ));

        $tr = new XMLElement('tr');

        if ($class) {
            $tr->setAttribute('class', $class);
        }

        if ($id) {
            $tr->setAttribute('id', $id);
        }

        if ($rowspan) {
            $tr->setAttribute('rowspan', $rowspan);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $tr->setAttributeArray($attributes);
        }

        foreach ($cells as $cell) {
            $tr->appendChild($cell);
        }

        return $tr;
    }

    /**
     * Generates a XMLElement representation of a `<td>`.
     *
     * @param XMLElement|string $value
     *  Either an XMLElement object, or a string for the value of the
     *  resulting `<td>`
     * @param string $class (optional)
     *  The class attribute of the resulting `<td>`
     * @param string $id (optional)
     *  The id attribute of the resulting `<td>`
     * @param integer $colspan (optional)
     *  The colspan attribute of the resulting `<td>`
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function TableData($value, $class = null, $id = null, $colspan = null, Array $attributes = null)
    {
        General::ensureType(array(
            'class' => array('var' => $class, 'type' => 'string', 'optional' => true),
            'id' => array('var' => $id, 'type' => 'string', 'optional' => true),
            'colspan' => array('var' => $colspan, 'type' => 'int', 'optional' => true)
        ));

        if (is_object($value)) {
            $td = new XMLElement('td');
            $td->appendChild($value);
        } else {
            $td = new XMLElement('td', $value);
        }

        if ($class) {
            $td->setAttribute('class', $class);
        }

        if ($id) {
            $td->setAttribute('id', $id);
        }

        if ($colspan) {
            $td->setAttribute('colspan', $colspan);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $td->setAttributeArray($attributes);
        }

        return $td;
    }

    /**
     * Generates a XMLElement representation of a `<time>`
     *
     * @since Symphony 2.3
     * @param string $string
     *  A string containing date and time, defaults to the current date and time
     * @param string $format (optional)
     *  A valid PHP date format, defaults to `__SYM_TIME_FORMAT__`
     * @param boolean $pubdate (optional)
     *  A flag to make the given date a publish date
     * @return XMLElement
     */
    public static function Time($string = 'now', $format = __SYM_TIME_FORMAT__, $pubdate = false)
    {
        // Parse date
        $date = DateTimeObj::parse($string);

        // Create element
        $obj = new XMLElement('time', Lang::localizeDate($date->format($format)));
        $obj->setAttribute('datetime', $date->format(DateTime::ISO8601));
        $obj->setAttribute('utc', $date->format('U'));

        // Pubdate?
        if ($pubdate === true) {
            $obj->setAttribute('pubdate', 'pubdate');
        }

        return $obj;
    }

    /**
     * Generates a XMLElement representation of a `<select>`. This uses
     * the private function `__SelectBuildOption()` to build XMLElements of
     * options given the `$options` array.
     *
     * @see toolkit.Widget::__SelectBuildOption()
     * @param string $name
     *  The name attribute of the resulting `<select>`
     * @param array $options (optional)
     *  An array containing the data for each `<option>` for this
     *  `<select>`. If the array is associative, it is assumed that
     *  `<optgroup>` are to be created, otherwise it's an array of the
     *  containing the option data. If no options are provided an empty
     *  `<select>` XMLElement is returned.
     *  `
     *   array(
     *    array($value, $selected, $desc, $class, $id, $attr)
     *   )
     *   array(
     *    array('label' => 'Optgroup', 'data-label' => 'optgroup', 'options' = array(
     *        array($value, $selected, $desc, $class, $id, $attr)
     *    )
     *   )
     *  `
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Select($name, array $options = null, array $attributes = null)
    {
        General::ensureType(array(
            'name' => array('var' => $name, 'type' => 'string')
        ));

        $obj = new XMLElement('select');
        $obj->setAttribute('name', $name);

        $obj->setSelfClosingTag(false);

        if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        if (!is_array($options) || empty($options)) {
            if (!isset($attributes['disabled'])) {
                $obj->setAttribute('disabled', 'disabled');
            }

            return $obj;
        }

        foreach ($options as $o) {
            //  Optgroup
            if (isset($o['label'])) {
                $optgroup = new XMLElement('optgroup');
                $optgroup->setAttribute('label', $o['label']);

                if (isset($o['data-label'])) {
                    $optgroup->setAttribute('data-label', $o['data-label']);
                }

                foreach ($o['options'] as $option) {
                    $optgroup->appendChild(
                        Widget::__SelectBuildOption($option)
                    );
                }

                $obj->appendChild($optgroup);
            } else {
                $obj->appendChild(Widget::__SelectBuildOption($o));
            }
        }

        return $obj;
    }

    /**
     * This function is used internally by the `Widget::Select()` to build
     * an XMLElement of an `<option>` from an array.
     *
     * @param array $option
     *  An array containing the data a single `<option>` for this
     *  `<select>`. The array can contain the following params:
     *      string $value
     *          The value attribute of the resulting `<option>`
     *      boolean $selected
     *          Whether this `<option>` should be selected
     *      string $desc (optional)
     *          The text of the resulting `<option>`. If omitted $value will
     *          be used a default.
     *      string $class (optional)
     *          The class attribute of the resulting `<option>`
     *      string $id (optional)
     *          The id attribute of the resulting `<option>`
     *      array $attributes (optional)
     *          Any additional attributes can be included in an associative
     *          array with the key being the name and the value being the
     *          value of the attribute. Attributes set from this array
     *          will override existing attributes set by previous params.
     *  `array(
     *      array('one-shot', false, 'One Shot', 'my-option')
     *   )`
     * @return XMLElement
     */
    private static function __SelectBuildOption($option)
    {
        @list($value, $selected, $desc, $class, $id, $attributes) = $option;

        if (!$desc) {
            $desc = $value;
        }

        $obj = new XMLElement('option', "$desc");
        $obj->setSelfClosingTag(false);
        $obj->setAttribute('value', "$value");

        if (!empty($class)) {
            $obj->setAttribute('class', $class);
        }

        if (!empty($id)) {
            $obj->setAttribute('id', $id);
        }

        if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        if ($selected) {
            $obj->setAttribute('selected', 'selected');
        }

        return $obj;

    }

    /**
     * Generates a XMLElement representation of a `<fieldset>` containing
     * the "With selected…" menu. This uses the private function `__SelectBuildOption()`
     * to build `XMLElement`'s of options given the `$options` array.
     *
     * @since Symphony 2.3
     * @see toolkit.Widget::__SelectBuildOption()
     * @param array $options (optional)
     *  An array containing the data for each `<option>` for this
     *  `<select>`. If the array is associative, it is assumed that
     *  `<optgroup>` are to be created, otherwise it's an array of the
     *  containing the option data. If no options are provided an empty
     *  `<select>` XMLElement is returned.
     *  `
     *   array(
     *    array($value, $selected, $desc, $class, $id, $attr)
     *   )
     *   array(
     *    array('label' => 'Optgroup', 'options' = array(
     *        array($value, $selected, $desc, $class, $id, $attr)
     *    )
     *   )
     *  `
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Apply(array $options = null)
    {
        $fieldset = new XMLElement('fieldset', null, array('class' => 'apply'));
        $div = new XMLElement('div');
        $div->appendChild(Widget::Label(__('Actions'), null, 'accessible', null, array(
            'for' => 'with-selected'
        )));
        $div->appendChild(Widget::Select('with-selected', $options, array(
            'id' => 'with-selected'
        )));
        $fieldset->appendChild($div);
        $fieldset->appendChild(new XMLElement('button', __('Apply'), array('name' => 'action[apply]', 'type' => 'submit')));

        return $fieldset;
    }

    /**
     * Will wrap a `<div>` around a desired element to trigger the default
     * Symphony error styling.
     *
     * @since Symphony 2.3
     * @param XMLElement $element
     *  The element that should be wrapped with an error
     * @param string $message
     *  The text for this error. This will be appended after the $element,
     *  but inside the wrapping `<div>`
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    public static function Error(XMLElement $element, $message)
    {
        General::ensureType(array(
            'message' => array('var' => $message, 'type' => 'string')
        ));

        $div = new XMLElement('div');
        $div->setAttributeArray(array('class' => 'invalid'));

        $div->appendChild($element);
        $div->appendChild(new XMLElement('p', $message));

        return $div;
    }

    /**
     * Generates a XMLElement representation of a Symphony drawer widget.
     * A widget is identified by it's `$label`, and it's contents is defined
     * by the `XMLElement`, `$content`.
     *
     * @since Symphony 2.3
     * @param string $id
     *  The id attribute for this drawer
     * @param string $label
     *  A name for this drawer
     * @param XMLElement $content
     *  An XMLElement containing the HTML that should be contained inside
     *  the drawer.
     * @param string $default_state
     *  This parameter defines whether the drawer will be open or closed by
     *  default. It defaults to closed.
     * @param string $context
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params, except the `id` attribute.
     * @return XMLElement
     */
    public static function Drawer($id = '', $label = '', XMLElement $content = null, $default_state = 'closed', $context = '', array $attributes = array())
    {
        $id = Lang::createHandle($id);

        $contents = new XMLElement('div', $content, array(
            'class' => 'contents'
        ));
        $contents->setElementStyle('html');

        $drawer = new XMLElement('div', $contents, $attributes);
        $drawer->setAttribute('data-default-state', $default_state);
        $drawer->setAttribute('data-context', $context);
        $drawer->setAttribute('data-label', $label);
        $drawer->setAttribute('data-interactive', 'data-interactive');
        $drawer->addClass('drawer');
        $drawer->setAttribute('id', 'drawer-' . $id);

        return $drawer;
    }

    /**
     * Generates a XMLElement representation of a Symphony calendar.
     *
     * @since Symphony 2.6
     * @param boolean $time
     *  Wheather or not to display the time, defaults to true
     * @return XMLElement
     */
    public static function Calendar($time = true)
    {
        $calendar = new XMLElement('div');
        $calendar->setAttribute('class', 'calendar');

        $date = DateTimeObj::convertDateToMoment(DateTimeObj::getSetting('date_format'));
        if($date) {
            if ($time === true) {
                $separator = DateTimeObj::getSetting('datetime_separator');
                $time = DateTimeObj::convertTimeToMoment(DateTimeObj::getSetting('time_format'));
        
                $calendar->setAttribute('data-calendar', 'datetime');
                $calendar->setAttribute('data-format', $date . $separator . $time);
            } else {
                $calendar->setAttribute('data-calendar', 'date');
                $calendar->setAttribute('data-format', $date);
            }
        }

        return $calendar;
    }
}
