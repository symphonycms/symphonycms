<?php
/**
 * @package toolkit
 */
/**
 * FilteringWidget is a utility class to generate filtering duplicators
 */
class FilteringWidget extends Widget
{
    /**
     * Generates a Filtering Duplicator.
     *
     * @since Symphony 2.6.x
     * @param Section $section
     *  The section for which the filters need to be created
     * @param string $inputArray
     *  The input array string within which the filters have to make part of, wrapper for the input name
     * @param array $filters
     *  The array of filters which are currently active.
     * @return XMLElement
     */
    public static function FilteringDuplicator($section, $inputArray=null, $filters = array())
    {
        $div = new XMLElement('div');
        $div->setAttribute('class', 'frame filters-duplicator');
        $div->setAttribute('data-interactive', 'data-interactive');

        $ol = new XMLElement('ol');
        $ol->setAttribute('data-add', __('Add filter'));
        $ol->setAttribute('data-remove', __('Clear filter'));
        $ol->setAttribute('data-empty', __('No filters applied yet.'));

        self::__createFieldFilters($ol, $section, $inputArray, $filters);
        self::__createSystemDateFilters($ol, $inputArray, $filters);

        $div->appendChild($ol);
        return $div;
    }

    private static function __createFieldFilters(&$wrapper, $section, $inputArray, $filters)
    {
        foreach ($section->fetchFilterableFields() as $field) {
            if (!$field->canPublishFilter()) {
                continue;
            }

            // var_dump($field->get('element_name'));
            // var_dump($filters);die;

            $filter = $filters[$field->get('element_name')];

            // Filter data
            $data = array();
            $data['type'] = $field->get('element_name');
            $data['name'] = $field->get('label');
            $data['filter'] = $filter;
            $data['instance'] = 'unique';
            $data['search'] = $field->fetchSuggestionTypes();
            $data['operators'] = $field->fetchFilterableOperators();
            $data['comparisons'] = self::__createFilterComparisons($data);
            $data['query'] = self::__getFilterQuery($data);
            $data['field-id'] = $field->get('id');

            // Add existing filter
            if (isset($filter)) {
                self::__createFilter($wrapper, $data, $inputArray);
            }

            // Add filter template
            $data['instance'] = 'unique template';
            $data['query'] = '';
            self::__createFilter($wrapper, $data, $inputArray);
        }
    }

    private static function __createSystemDateFilters(&$wrapper, $inputArray, $filters)
    {
        $dateField = new FieldDate;

        $fields = array(
            array(
                'type' => 'system:creation-date',
                'label' => __('System Creation Date')
            ),
            array(
                'type' => 'system:modification-date',
                'label' => __('System Modification Date')
            )
        );

        foreach ($fields as $field) {
            $filter = $filters[$field['type']];

            // Filter data
            $data = array();
            $data['type'] = $field['type'];
            $data['name'] = $field['label'];
            $data['filter'] = $filter;
            $data['instance'] = 'unique';
            $data['search'] = $dateField->fetchSuggestionTypes();
            $data['operators'] = $dateField->fetchFilterableOperators();
            $data['comparisons'] = self::__createFilterComparisons($data);
            $data['query'] = self::__getFilterQuery($data);

            // Add existing filter
            if (isset($filter)) {
                self::__createFilter($wrapper, $data, $inputArray);
            }

            // Add filter template
            $data['instance'] = 'unique template';
            $data['query'] = '';
            self::__createFilter($wrapper, $data, $inputArray);
        }
    }

    private static function __createFilter(&$wrapper, $data, $inputArray)
    {

        if (!empty($inputArray)){
            $inputName = $inputArray . '[' . $data['type'] . ']';
            $inputComparison = $inputArray . '[' . $data['type'] . '-comparison' . ']';
        } else {                
            $inputName = $data['type'];
            $inputComparison = $data['type'] . '-comparison';
        }

        $li = new XMLElement('li');
        $li->setAttribute('class', $data['instance'] . ' locked');
        $li->setAttribute('data-type', $data['type']);

        // Header
        $li->appendChild(new XMLElement('header', $data['name'], array(
            'data-name' => $data['name']
        )));

        // Settings
        $div = new XMLElement('div', null, array('class' => 'two columns'));

        // Comparisons
        $label = Widget::Label();
        $label->setAttribute('class', 'column secondary');

        $select = Widget::Select($inputComparison, $data['comparisons'], array(
            'class' => 'comparison'
        ));

        $label->appendChild($select);
        $div->appendChild($label);

        // Query
        $label = Widget::Label();
        $label->setAttribute('class', 'column primary');

        $input = Widget::Input($inputName, $data['query'], 'text', array(
            'placeholder' => __('Type and hit enter to apply filter…'),
            'autocomplete' => 'off'
        ));
        $input->setAttribute('class', 'filter');
        $label->appendChild($input);

        self::__createFilterSuggestions($label, $data);

        $div->appendChild($label);
        $li->appendChild($div);
        $wrapper->appendChild($li);
    }

    private static function __createFilterComparisons($data)
    {
        // Default comparison
        $comparisons = array();

        // Custom field comparisons
        foreach ($data['operators'] as $operator) {
            $filter = trim($operator['filter']);

            $comparisons[] = array(
                $filter,
                (!empty($filter) && strpos($data['filter'], $filter) === 0),
                __($operator['title'])
            );
        }

        return $comparisons;
    }

    private static function __createFilterSuggestions(&$wrapper, $data)
    {
        $ul = new XMLElement('ul');
        $ul->setAttribute('class', 'suggestions');
        $ul->setAttribute('data-field-id', $data['field-id']);
        $ul->setAttribute('data-associated-ids', '0');
        $ul->setAttribute('data-search-types', implode($data['search'], ','));

        // Add default filter help
        $operator = array(
            'filter' => 'is',
            'help' => __('Find values that are an exact match for the given string.')
        );
        self::__createFilterHelp($ul, $operator);

        // Add custom filter help
        foreach ($data['operators'] as $operator) {
            self::__createFilterHelp($ul, $operator);
        }

        $wrapper->appendChild($ul);
    }

    private static function __createFilterHelp(&$wrapper, $operator) {
        if(empty($operator['help'])) {
            return;
        }

        $li = new XMLElement('li', __('Comparison mode') . ': ' . $operator['help'], array(
            'class' => 'help',
            'data-comparison' => trim($operator['filter'])
        ));

        $wrapper->appendChild($li);
    }

    private static function __getFilterQuery($data)
    {
        $query = $data['filter'];

        foreach ($data['operators'] as $operator) {
            $filter = trim($operator['filter']);

            if (!empty($filter) && strpos($data['filter'], $filter) === 0) {
                $query = substr($data['filter'], strlen($filter));
            }
        }

        return (string)$query;
    }
}
