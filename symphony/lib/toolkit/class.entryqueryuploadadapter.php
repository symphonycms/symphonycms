<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an upload Field.
 * @see FieldUpload
 * @since Symphony 3.0.0
 */
class EntryQueryUploadAdapter extends EntryQueryFieldAdapter
{
    /**
     * Filter columns override
     * @var array
     */
    private $filterColumns = null;

    /**
     * @internal Returns the columns to use when filtering
     *
     * @return array
     */
    public function getFilterColumns()
    {
        if (is_array($this->filterColumns)) {
            return $this->filterColumns;
        }
        return ['file', 'size', 'mimetype'];
    }

    /**
     * @see EntryQueryFieldAdapter::filter()
     *
     * @param EntryQuery $query
     * @param array $filters
     * @param string $operator
     * @return void
     */
    public function filter(EntryQuery $query, array $filters, $operator = 'or')
    {
        if (preg_match('/^mimetype:\s*/', $filters[0])) {
            $filters[0] = preg_replace('/^mimetype:\s*/', '', $filters[0], 1);
            $this->filterColumns = ['mimetype'];
        } elseif (preg_match('/^size:\s*/', $filters[0])) {
            $filters[0] = preg_replace('/^size:\s*/', '', $filters[0], 1);
            $this->filterColumns = ['size'];
        } else {
            $this->filterColumns = ['file'];
        }
        parent::filter($query, $filters, $operator);
        $this->filterColumns = null;
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
        return $this->createFilterEquality($filter, $this->getFilterColumns());
    }

    /**
     * @internal Returns the columns to use when sorting
     *
     * @return array
     */
    public function getSortColumns()
    {
        return ['file'];
    }
}
