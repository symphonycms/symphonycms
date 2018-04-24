<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an date Field.
 * @see FieldDate
 * @since Symphony 3.0.0
 */
class EntryQueryDateAdapter extends EntryQueryFieldAdapter
{
    /**
     * Cleans the filter string by removing extra chars
     *
     * @param string $string
     *  The filter to clean
     * @return string
     *  The clean filter
     */
    public function cleanFilterString($string)
    {
        $string = trim($string, ' -/.');
        $string = str_replace('/', '-', $string);
        return urldecode($string);
    }

    /**
     * Builds a date filter, using the DateRangeParser class.
     *
     * @uses DateRangeParser
     * @param string $filter
     *  The full filter string
     * @param array $columns
     *  The array of columns that need the given `$filter` applied to.
     *  The conditions will be added using `AND`.
     * @return array
     */
    public function createFilterDateRange($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->cleanFilterString($filter);
        $filter = (new DateRangeParser($filter))->parse();
        $op = 'date';

        $conditions = [];
        foreach ($columns as $key => $col) {
            $conditions[] = [$this->formatColumn($col, $field_id) => [$op => $filter]];
        }
        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['or' => $conditions];
    }

    /**
     * @see EntryQueryFieldAdapter::filterSingle()
     *
     * @param EntryQuery $query
     * @param string $filter
     * @return array
     */
    protected function filterSingle(EntryQuery $query, $filter)
    {
        General::ensureType([
            'filter' => ['var' => $filter, 'type' => 'string'],
        ]);
        if ($this->isFilterRegex($filter)) {
            return $this->createFilterRegexp($filter, $this->getFilterColumns());
        } elseif ($this->isFilterSQL($filter)) {
            return $this->createFilterSQL($filter, $this->getFilterColumns());
        } elseif ($this->isFilterNotEqual($filter)) {
            return $this->createFilterNotEqual($filter, $this->getFilterColumns());
        }
        return $this->createFilterDateRange($filter, $this->getFilterColumns());
    }
}
