<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an textarea Field.
 * @see FieldTextarea
 * @since Symphony 3.0.0
 */
class EntryQueryTextareaAdapter extends EntryQueryFieldAdapter
{
    /**
     * Builds a full text MATCH() filter
     *
     * @param string $filter
     *  The full filter string
     * @param array $columns
     *  The array of columns that needed to implement the given `$filter`.
     *  The conditions for each column will be added using `OR`.
     * @return array
     *  The filter array
     */
    public function createFilterFulltextMatch($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);
        $op = 'boolean';

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
        }
        return $this->createFilterFulltextMatch($filter, $this->getFilterColumns());
    }
}
