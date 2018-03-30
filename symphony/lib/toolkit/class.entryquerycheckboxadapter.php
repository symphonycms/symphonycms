<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an checkbox Field.
 * @see FieldCheckbox
 * @since Symphony 3.0.0
 */
class EntryQueryCheckboxAdapter extends EntryQueryFieldAdapter
{
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
        $default = ($this->field->get('default_state') === 'on') ? 'yes' : 'no';
        if ($this->isFilterSQL($filter)) {
            return $this->createFilterSQL($filter, $this->getFilterColumns());
        } elseif ($this->isFilterNotEqual($filter)) {
            return $this->createFilterNotEqual($filter, $this->getFilterColumns());
        } elseif ($filter === $default) {
            return $this->createFilterEqualityOrNull($filter, $this->getFilterColumns());
        }
        return $this->createFilterEquality($filter, $this->getFilterColumns());
    }
}
