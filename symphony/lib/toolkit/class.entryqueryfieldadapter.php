<?php

/**
 * @package toolkit
 */
/**
 * EntryQueryFieldAdapter encapsulates the logic to operate on a EntryQuery in order to
 * filter or sort entries based the data it is responsible for.
 *
 * Since filtering/sorting operations often needs complex SQL statements,
 * we allow to simply use the Entry's Section schema to request the filtering/sorting.
 * This is done with by calling `EntryQuery::sort('field-name')`
 * or `EntryQuery::filter('field-name', ['val1', 'val2'])`, which will then delegate the
 * filters to SQL data structure translation to EntryQueryFieldAdapter.
 * This API is used extensively in section data sources.
 *
 * The default implementation works on a field that uses a 'value' column to be in the data table.
 * It supports exact match, 'not:', 'regexp:' and 'sql:' filtering modes.
 * It also supports 'asc', 'desc', 'random' sort directions.
 *
 * @see EntryQuery::sort()
 * @see EntryQuery::filter()
 * @see Field::getEntryQueryFieldAdapter()
 * @since Symphony 3.0.0
 */
class EntryQueryFieldAdapter
{
    /**
     * The reference field
     * @var Field
     */
    protected $field;

    /**
     * Creates a new EntryQueryFieldAdapter object, tied to the Field $field.
     *
     * @param Field $field
     *  The reference field
     */
    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    /**
     * Aliases the common columns name returned by getFilterColumns() and
     * getSortColumns() according to the aliased used in joins.
     * By default, aliases are `f{$field_id}`.
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
        return "f{$field_id}.$col";
    }

    /**
     * Test whether the input string is a regular expression, by searching
     * for the prefix of `regexp:` or `not-regexp:` in the given `$string`.
     *
     * @param string $string
     *  The string to test.
     * @return boolean
     *  true if the string is prefixed with `regexp:` or `not-regexp:`, false otherwise.
     */
    public function isFilterRegex($string)
    {
        if (preg_match('/^regexp:/i', $string) || preg_match('/^not-?regexp:/i', $string)) {
            return true;
        }
        return false;
    }

    /**
     * Builds a basic REGEXP statement given a `$filter`. This function supports
     * `regexp:` or `not-regexp:`. Users should keep in mind this function
     * uses MySQL patterns, not the usual PHP patterns, the syntax between these
     * flavours differs at times.
     *
     * @link https://dev.mysql.com/doc/refman/en/regexp.html
     * @param string $filter
     *  The full filter, eg. `regexp: ^[a-d]`
     * @param array $columns
     *  The array of columns that need the given `$filter` applied to. The conditions
     *  will be added using `OR` when using `regexp:` but they will be added using `AND`
     *  when using `not-regexp:`
     * @return array
     *  The filter array
     * @throws DatabaseStatementException
     */
    public function createFilterRegexp($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);
        $pattern = '';
        $op = '';

        if (preg_match('/^regexp:\s*/i', $filter)) {
            $pattern = preg_replace('/^regexp:\s*/i', null, $filter);
            $regex = 'regexp';
            $op = 'or';
        } elseif (preg_match('/^not-?regexp:\s*/i', $filter)) {
            $pattern = preg_replace('/^not-?regexp:\s*/i', null, $filter);
            $regex = 'not regexp';
            $op = 'and';
        } else {
            throw new DatabaseStatementException("Filter `$filter` is not a Regexp filter");
        }

        if (empty($pattern)) {
            return;
        }

        $conditions = [];
        foreach ($columns as $key => $col) {
            $conditions[] = [$this->formatColumn($col, $field_id) => [$regex => $pattern]];
        }
        if (count($conditions) < 2) {
            return $conditions;
        }
        return [$op => $conditions];
    }

    /**
     * Test whether the input string is a NULL/NOT NULL SQL clause, by searching
     * for the prefix of `sql:` in the given `$string`, followed by `(NOT )? NULL`
     *
     * @param string $string
     *  The string to test.
     * @return boolean
     *  true if the string is prefixed with `sql:`, false otherwise.
     */
    public function isFilterSQL($string)
    {
        if (preg_match('/^sql:\s*(NOT )?NULL$/i', $string)) {
            return true;
        }
        return false;
    }

    /**
     * Builds a basic NULL/NOT NULL SQL statement given a `$filter`.
     *  This function supports `sql: NULL` or `sql: NOT NULL`.
     *
     * @param string $filter
     *  The full filter, eg. `sql: NULL`
     * @param array $columns
     *  The array of columns that need the given `$filter` applied to.
     *  The conditions will be added using `OR`.
     * @return array
     *  The filter array
     * @throws DatabaseStatementException
     */
    public function createFilterSQL($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);
        $op = '';

        if (preg_match('/^sql:\s*NOT NULL$/i', $filter)) {
            $op = '!=';
        } elseif (preg_match('/^sql:\s*NULL$/i', $filter)) {
            $op = '=';
        } else {
            throw new DatabaseStatementException("Filter `$filter` is not a SQL filter");
        }

        $conditions = [];
        foreach ($columns as $key => $col) {
            $conditions[] = [$this->formatColumn($col, $field_id) => [$op => null]];
        }
        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['or' => $conditions];
    }

    /**
     * Builds an equality filter.
     *
     * @see createFilterEqualityOrNull()
     * @param string $filter
     *  The full filter string
     * @param array $columns
     *  The array of columns that needed to implement the given `$filter`.
     *  The conditions for each column will be added using `OR`.
     * @return array
     *  The filter array
     */
    public function createFilterEquality($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);
        $op = '=';

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
     * Builds an equal or null filter.
     *
     * @see createFilterEquality()
     * @param string $filter
     *  The full filter string
     * @param array $columns
     *  The array of columns that needed to implement the given `$filter`.
     *  The conditions for each column will be added using `OR`.
     * @return array
     *  The filter array
     */
    public function createFilterEqualityOrNull($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);
        $op = '=';

        $conditions = [];
        foreach ($columns as $key => $col) {
            $col = $this->formatColumn($col, $field_id);
            $conditions[] = ['or' => [
                [$col => [$op => $filter]],
                [$col => [$op => null]],
            ]];
        }
        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['or' => $conditions];
    }

    /**
     * Test whether the input string is a negation filter, i.e. `not:`.
     *
     * @param string $string
     *  The string to test.
     * @return boolean
     *  true if the string is prefixed with `not:`, false otherwise.
     */
    public function isFilterNotEqual($string)
    {
        if (preg_match('/^not:\s*/i', $string)) {
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
    public function createFilterNotEqual($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = preg_replace('/^not:\s*/i', null, $filter);
        $filter = $this->field->cleanValue($filter);
        $op = '!=';

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
     * @internal Returns the columns to use when filtering
     *
     * @return array
     */
    public function getFilterColumns()
    {
        return ['value'];
    }

    /**
     * Appends the required WHERE clauses based on the requested filters and the reference field.
     * Filters allows developers to filter using a simplified syntax that is primarily used
     * in data sources.
     *
     * This default implementation supports exact match, 'regexp:' and 'sql:' filtering modes.
     *
     * @see EntryQuery::filter()
     * @uses EntryQuery::whereField()
     * @uses filterSingle()
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
        if (empty($filters)) {
            return;
        }
        $field_id = General::intval($this->field->get('id'));
        $conditions = [];
        // `not:` needs a special treatment because 'and' operation are using the 'or' notation (,)
        // In order to make the filter work, we must change 'or' to 'and'
        // and propagate the prefix to all other un-prefix $filters
        if ($operator === 'or' && preg_match('/not[^\:]*:/i', $filters[0])) {
            $operator = 'and';
            $prefix = current(explode(':', $filters[0], 2));
            foreach ($filters as $i => $filter) {
                if ($i === 0) {
                    continue;
                }
                if (strpos($filter, ':') === false) {
                    $filters[$i] = "$prefix: $filter";
                } else {
                    break;
                }
            }
        }

        foreach ($filters as $filter) {
            $fc = $this->filterSingle($query, $filter);
            if (is_array($fc) && !empty($fc)) {
                $conditions[] = $fc;
            }
        }
        if (empty($conditions)) {
            return;
        }
        // Prevent adding unnecessary () when dealing with a single condition
        if (count($conditions) > 1) {
            $conditions = [$operator => $conditions];
        }
        $query->whereField($field_id, $conditions);
    }

    /**
     * Converts a single string filter into a where conditions array.
     * This is the function that responsible to handle all the filtering modes
     * supported by the Field.
     *
     * @uses formatColumn()
     * @param EntryQuery $query
     *  The EntryQuery to operate on
     * @param string $filter
     *  A filter string
     * @return array
     *  The conditions array
     * @throws DatabaseStatementException
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
     * Determine if the requested $order is random or not.
     *
     * @param string $order
     *  the sorting direction.
     * @return boolean
     *  true if the $order is either 'rand' or 'random'
     */
    protected function isRandomOrder($order)
    {
        return in_array(strtolower($order), ['random', 'rand']);
    }

    /**
     * @internal Returns the columns to use when sorting
     *
     * @return array
     */
    public function getSortColumns()
    {
        return ['value'];
    }

    /**
     * Appends the required ORDER BY clauses based on the requested sort and the reference field.
     * In order to make MySQL strict mode work, values used in sort are added to the projection.
     *
     * This default implementation supports 'asc', 'desc', 'random' sort directions.
     *
     * @uses formatColumn()
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
            ->projection(array_map(function ($col) use ($field_id) {
                return $this->formatColumn($col, $field_id);
            }, $this->getSortColumns()));
        foreach ($this->getSortColumns() as $col) {
            $query->orderBy($this->formatColumn($col, $field_id), $direction);
        }
        $query->orderBy('e.id', $direction);
    }
}
