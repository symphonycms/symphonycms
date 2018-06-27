<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an tag list Field and select Field.
 * @see FieldTagList
 * @see FieldSelect
 * @since Symphony 3.0.0
 */
class EntryQueryListAdapter extends EntryQueryFieldAdapter
{
    /**
     * Test whether the input string is a negation or null filter, i.e. `sql-null-or-not:`.
     *
     * @param string $string
     *  The string to test.
     * @return boolean
     *  true if the string is prefixed with `sql-null-or-not:`, false otherwise.
     */
    public function isFilterNotEqualOrNull($string)
    {
        if (preg_match('/^sql-null-or-not:\s*/i', $string)) {
            return true;
        }
        return false;
    }

    /**
     * Builds a basic negation (not equals) filter.
     *
     * @param string $filter
     *  The full filter string
     * @param array $columns
     *  The array of columns that need the given `$filter` applied to.
     *  The conditions will be added using `AND`.
     * @return array
     *  The filter array
     */
    public function createFilterNotEqualOrNull($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = preg_replace('/^sql-null-or-not:\s*/i', null, $filter);
        $filter = $this->field->cleanValue($filter);

        $conditions = [];
        foreach ($columns as $key => $col) {
            $col = $this->formatColumn($col, $field_id);
            $conditions[] = ['or' => [
                [$col => ['!=' => $filter]],
                [$col => null],
            ]];
        }
        // Since we allow nulls, include null relations
        $conditions[] = [$this->formatColumn('relation_id', $field_id) => null];
        return ['or' => $conditions];
    }

    /**
     * @internal Returns the columns to use when filtering
     *
     * @return array
     */
    public function getFilterColumns()
    {
        return ['value', 'handle'];
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
        } elseif ($this->isFilterNotEqualOrNull($filter)) {
            return $this->createFilterNotEqualOrNull($filter, $this->getFilterColumns());
        } elseif ($this->isFilterNotEqual($filter)) {
            return $this->createFilterNotEqual($filter, $this->getFilterColumns());
        }
        return $this->createFilterEquality($filter, $this->getFilterColumns());
    }
}
