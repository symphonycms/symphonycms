<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an author Field.
 * @see FieldAuthor
 * @since Symphony 3.0.0
 */
class EntryQueryAuthorAdapter extends EntryQueryFieldAdapter
{
    /**
     * Since this class includes the fixed alias in column names,
     * this implementation simply returns the $col value.
     *
     * @see getFilterColumns()
     * @see getSortColumns()
     * @param string $col
     *  The column name
     * @param int $field_id
     *  The field id
     * @return string
     *  The aliased column name
     */
    public function formatColumn($col, $field_id)
    {
        return $col;
    }

    /**
     * @internal Returns the columns to use when filtering
     *
     * @return array
     */
    public function getFilterColumns()
    {
        return [
            'af.username',
            'af.first_name',
            'af.last_name',
            'af.full_name',
        ];
    }

    /**
     * Test whether the input string is a author id filter, i.e. `author-id:`.
     *
     * @param string $string
     *  The string to test.
     * @return boolean
     *  true if the string is prefixed with `author-id:`, false otherwise.
     */
    public function isFilterAuthorId($string)
    {
        if (preg_match('/^author-id:\s*/i', $string)) {
            return true;
        }
        return false;
    }

    /**
     * Builds a simply author id filter.
     *
     * @param string $filter
     *  The full filter string
     * @param array $columns
     *  The array of columns that need the given `$filter` applied to.
     *  The conditions will be added using `AND`.
     * @return array
     *  The filter array
     */
    public function createFilterAuthorId($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = preg_replace('/^author-id:\s*/i', null, $filter);
        $filter = $this->field->cleanValue($filter);
        $op = '=';

        $conditions = [];
        foreach ($columns as $key => $col) {
            $conditions[] = [$this->formatColumn($col, $field_id) => [$op => $filter]];
        }
        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['and' => $conditions];
    }

    /**
     * Appends the required WHERE clauses based on the requested filters and the reference field.
     * Filters allows developers to filter using a simplified syntax that is primarily used
     * in data sources.
     *
     * This implementation supports exact match, 'regexp:', 'sql:' and
     * 'author-id:' filtering modes.
     *
     * @see EntryQuery::filter()
     * @uses EntryQuery::whereField()
     * @param EntryQuery $query
     *  The EntryQuery to operate on
     * @param array $filters
     *  The array of all filter values
     * @param string $operator
     *  The operation to use when there are multiple filters
     * @return void
     * @throws DatabaseStatementException
     */
    public function filter(EntryQuery $query, array $filters, $operator = 'or')
    {
        General::ensureType([
            'operator' => ['var' => $operator, 'type' => 'string'],
        ]);
        $field_id = General::intval($this->field->get('id'));
        $projectionAdded = false;
        $addProjection = function () use ($projectionAdded, $query) {
            if ($projectionAdded) {
                return;
            }
            $query->projection([
                'CONCAT_WS(:af_space_char, af.first_name, af.last_name)' => 'af.full_name'
            ])->setValue('af_space_char', ' ');
            $projectionAdded = true;
        };
        $conditions = [$operator => []];
        foreach ($filters as $filter) {
            $fc = null;
            if ($this->isFilterAuthorId($filter)) {
                $fc = $this->createFilterAuthorId($filter, [parent::formatColumn('author_id', $field_id)]);
            } elseif ($this->isFilterRegex($filter)) {
                $addProjection();
                $fc = $this->createFilterRegexp($filter, $this->getFilterColumns());
            } elseif ($this->isFilterSQL($filter)) {
                $addProjection();
                $fc = $this->createFilterSQL($filter, $this->getFilterColumns());
            } else {
                $addProjection();
                $fc = $this->createFilterEquality($filter, $this->getFilterColumns());
            }
            if (is_array($fc)) {
                $conditions[$operator][] = $fc;
            }
        }
        // Remove extra ()
        if (count($conditions) === 1) {
            $conditions = current($conditions);
        }
        $query->whereField($field_id, $conditions);
    }

    /**
     * @internal Returns the columns to use when sorting
     *
     * @return array
     */
    public function getSortColumns()
    {
        return ['as.first_name', 'as.last_name'];
    }

    /**
     * Appends the required ORDER BY clauses based on the requested sort and the reference author field.
     * It will append also join the `tbl_authors` table to be able to filter on actual text values.
     * In order to make MySQL strict mode work, values used in sort are added to the projection.
     *
     * This implementation supports 'asc', 'desc', 'random' sort directions.
     *
     * @param EntryQuery $query
     *  The EntryQuery to operate on
     * @param string $direction
     *  The default direction to use.
     *  Supports ASC, DESC and RAND
     *  Defaults to ASC.
     * @return void
     * @throws DatabaseStatementExceptions
     */
    public function sort(EntryQuery $query, $direction = 'ASC')
    {
        General::ensureType([
            'direction' => ['var' => $direction, 'type' => 'string'],
        ]);
        $field_id = General::intval($this->field->get('id'));
        if ($this->isRandomOrder($direction)) {
            $query->orderBy('RAND()');
            return;
        }
        $query->leftJoinField($field_id)
            ->innerJoin('tbl_authors', 'as')
            ->on(["f{$field_id}.author_id" => '$as.id'])
            ->projection($this->getSortColumns());
        foreach ($this->getSortColumns() as $column) {
            $query->orderBy($column, $direction);
        }
        $query->orderBy('e.id', $direction);
    }
}
