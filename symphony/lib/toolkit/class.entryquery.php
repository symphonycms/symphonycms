<?php

/**
 * @package toolkit
 */

/**
 * Specialized DatabaseQuery that facilitate creation of queries on the entries table.
 */
class EntryQuery extends DatabaseQuery
{
    /**
     * The requested schema. Items names must the the fields' labels.
     * @var array
     */
    private $schema = [];

    /**
     * Flag to indicate if the statement needs to add the default ORDER BY clause
     * @var boolean
     */
    private $addDefaultSort = true;

    /**
     * The requested section id. Needed to fetch the default sort.
     * @var integer
     */
    private $sectionId = 0;

    /**
     * Creates a new EntryQuery statement on table `tbl_entries` with an optional projection.
     * The table is aliased to `e`.
     * The mandatory values are already added to the projection.
     *
     * @see EntryManager::select()
     * @see EntryManager::selectCount()
     * @param Database $db
     *  The underlying database connection
     * @param string $schema
     *  The field names for which to get the data.
     *  Defaults to an empty schema.
     * @param array $projection
     *  The columns names for include in the projection.
     *  Defaults to an empty projection.
     */
    public function __construct(Database $db, array $schema = [], array $projection = [])
    {
        parent::__construct($db, $projection);
        $this->from('tbl_entries')->alias('e')->schema($schema);
    }

    /**
     * Disables the default sort
     * @return EntryQuery
     *  The current instance
     */
    public function disableDefaultSort()
    {
        $this->addDefaultSort = false;
        return $this;
    }

    /**
     * Getter for the current section id
     * @return int
     */
    public function sectionId()
    {
        return $this->sectionId;
    }

    /**
     * Adds field names to the schema.
     * The schema is kept sorted to allow better cache hits.
     *
     * @param array $schema
     *  The field names to retrieve when building entries
     * @return EntryQuery
     *  The current instance
     */
    public function schema(array $schema)
    {
        if (empty($schema)) {
            return $this;
        }
        $this->schema = array_unique(array_merge($this->schema, $schema));
        sort($this->schema, SORT_STRING);
        return $this;
    }

    /**
     * Adds all the fields name from the selected section in the schema.
     *
     * @see schema()
     * @see section()
     * @throws DatabaseStatementException
     *  If section() has not been called before
     * @return EntryQuery
     *  The current instance
     */
    public function includeAllFields()
    {
        if (!$this->sectionId) {
            throw new DatabaseStatementException('Cannot include all fields before calling section()');
        }
        return $this->schema(array_map(function ($field) {
            return $field['element_name'];
        }, FieldManager::fetchFieldsSchema($this->sectionId)));
    }

    /**
     * Adds a WHERE clause on the section id.
     *
     * @param int $section_id
     *  The section id in which to look for
     * @return EntryQuery
     *  The current instance
     */
    public function section($section_id)
    {
        $this->sectionId = General::intval($section_id);
        return $this->where(['e.section_id' => $this->sectionId]);
    }

    /**
     * Adds a WHERE clause on the entry id.
     * Prevents the default ORDER BY clause to be added.
     *
     * @see disableDefaultSort()
     * @param int $entry_id
     *  The entry id to fetch
     * @return EntryQuery
     *  The current instance
     */
    public function entry($entry_id)
    {
        $this->disableDefaultSort();
        return $this->where(['e.id' => General::intval($entry_id)]);
    }

    /**
     * Adds a WHERE clause on the entry id.
     *
     * @param array $entry_ids
     *  The entry ids to fetch
     * @return EntryQuery
     *  The current instance
     */
    public function entries(array $entry_ids)
    {
        return $this->where(['e.id' => ['in' => array_map(['General', 'intval'], $entry_ids)]]);
    }

    /**
     * Appends a INNER JOIN `tbl_entries_data_$field_id` ON entry_id clause
     *
     * @param int $field_id
     *  The field id to join with
     * @return EntryQuery
     *  The current instance
     */
    public function innerJoinField($field_id)
    {
        General::ensureType([
            'field_id' => ['var' => $field_id, 'type' => 'int'],
        ]);
        return $this
            ->innerJoin("tbl_entries_data_$field_id", "t_$field_id")
            ->on(['e.id' => "\$t_$field_id.entry_id"]);
    }

    /**
     * Appends a JOIN `tbl_entries_data_$field_id` ON entry_id clause
     *
     * @param int $field_id
     *  The field id to join with
     * @return EntryQuery
     *  The current instance
     */
    public function joinField($field_id)
    {
        General::ensureType([
            'field_id' => ['var' => $field_id, 'type' => 'int'],
        ]);
        return $this
            ->join("tbl_entries_data_$field_id", "t_$field_id")
            ->on(['e.id' => "\$t_$field_id.entry_id"]);
    }

    /**
     * Appends a LEFT JOIN `tbl_entries_data_$field_id` ON entry_id clause
     *
     * @param int $field_id
     *  The field id to join with
     * @return EntryQuery
     *  The current instance
     */
    public function leftJoinField($field_id)
    {
        General::ensureType([
            'field_id' => ['var' => $field_id, 'type' => 'int'],
        ]);
        return $this
            ->leftJoin("tbl_entries_data_$field_id", "t_$field_id")
            ->on(['e.id' => "\$t_$field_id.entry_id"]);
    }

    /**
     * Appends a OUTER JOIN `tbl_entries_data_$field_id` ON entry_id clause
     *
     * @param int $field_id
     *  The field id to join with
     * @return EntryQuery
     *  The current instance
     */
    public function outerJoinField($field_id)
    {
        General::ensureType([
            'field_id' => ['var' => $field_id, 'type' => 'int'],
        ]);
        return $this
            ->outerJoin("tbl_entries_data_$field_id", "t_$field_id")
            ->on(['e.id' => "\$t_$field_id.entry_id"]);
    }

    /**
     * Appends a RIGHT JOIN `tbl_entries_data_$field_id` ON entry_id clause
     *
     * @param int $field_id
     *  The field id to join with
     * @return EntryQuery
     *  The current instance
     */
    public function rightJoinField($field_id)
    {
        General::ensureType([
            'field_id' => ['var' => $field_id, 'type' => 'int'],
        ]);
        return $this
            ->rightJoin("tbl_entries_data_$field_id", "t_$field_id")
            ->on(['e.id' => "\$t_$field_id.entry_id"]);
    }

    /**
     * Appends a WHERE clause with one or many conditions.
     * Calling this method multiple times will join the WHERE clauses with a AND.
     *
     * @see DatabaseQuery::where()
     * @param int $field_id
     *  The field id to filter with
     * @param array $filters
     *  The where filters to apply on the field table
     * @return EntryQuery
     *  The current instance
     */
    public function whereField($field_id, array $filters)
    {
        General::ensureType([
            'field_id' => ['var' => $field_id, 'type' => 'int'],
        ]);
        foreach ($filters as $key => $filter) {
            if (!$key) {
                throw new DatabaseStatementException('Filter key can not be null');
            }
            $this->where(["t_$field_id.$key" => $filter]);
        }
        return $this;
    }

    /**
     * Appends a ORDER BY clause using the $field parameter.
     *
     * @param string $field
     *  The field to order by with
     * @param string $direction
     *  The default direction to use.g
     *  Defaults to ASC.
     * @return EntryQuery
     *  The current instance
     */
    public function sort($field, $direction = 'ASC')
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
            'direction' => ['var' => $direction, 'type' => 'string'],
        ]);
        $f = null;
        $sort = null;

        if (!$field) {
            return $this;
        }

        if ($direction === 'RAND') {
            $sort = ['e.id' => 'RAND()'];

        // Handle Creation Date
        } elseif ($field === 'system:creation-date') {
            $sort = ['e.creation_date_gmt' => $direction];

        // Handle Modification Date sorting
        } elseif ($field === 'system:modification-date') {
            $sort = ['e.modification_date_gmt' => $direction];

        // Handle sorting for System ID
        } elseif ($field === 'system:id') {
            $sort = ['e.id' => $direction];

        // Handle when the sort field is an actual Field
        } elseif (General::intval($field) > 0) {
            $f = (new FieldManager)->select()->field($field)->execute()->next();
            if ($f && $f->isSortable()) {
                $sort = $this->buildLegacySortingForField($f, $direction);
            } else {
                // Field not found or not sortable, silence the error.
                // This prevents crashing the backend for a bad reason.
                $sort = true;
            }

        // Handle legacy id filter
        // This is @deprecated
        } elseif ($field === 'id') {
            $sort = ['e.id' => $direction];
        }

        if ($f && $f->requiresSQLGrouping() && !$this->containsSQLParts('optimizer')) {
            $this->distinct();
        }

        if (is_array($sort)) {
            $this->orderBy($sort, $direction);
        } elseif (is_string($sort)) {
            $sort = $this->replaceTablePrefix($sort);
            $this->unsafe()->unsafeAppendSQLPart('order by', $sort);
        } elseif (!$sort) {
            throw new DatabaseStatementException("Invalid sort on field `$field`");
        }

        return $this;
    }

    /**
     * @internal This methods converts the output of Field::buildSortingSQL() and
     * and Field::buildSortingSelectSQL() into valid DatabaseQuery operations.
     *
     * @param Field $f
     *  The field to sort with
     * @param string $direction
     *  The sort direction
     * @return void
     */
    public function buildLegacySortingForField(Field $f, $direction)
    {
        $f->buildSortingSQL($joins, $where, $sort, $direction);
        $sortSelectClause = $f->buildSortingSelectSQL($sort, $direction);

        if ($sortSelectClause) {
            $this->projection(array_map('trim', explode(',', $sortSelectClause)));
        }
        if ($joins) {
            $joins = $this->replaceTablePrefix($joins);
            $this->unsafeAppendSQLPart('join', $joins);
        }
        if ($where) {
            $where = $this->replaceTablePrefix($where);
            $this->unsafe()->unsafeAppendSQLPart('where', "WHERE 1=1 $where");
        }
        return $sort;
    }

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * Appends any remaining part of the statement.
     * If the default sort is not disabled and their are not custom sort added,
     * it will add the default sort.
     *
     * @see DatabaseStatement::execute()
     * @return EntryQuery
     *  The current instance
     */
    public function finalize()
    {
        if ($this->addDefaultSort && !$this->containsSQLParts('order by')) {
            // Handle if the section has a default sorting field
            if ($this->sectionId) {
                $section = (new SectionManager)->select()->section($this->sectionId)->execute()->next();
                if ($section && $section->getSortingField()) {
                    $field = (new FieldManager)->select()->field($section->getSortingField())->execute()->next();
                    if ($field && $field->isSortable()) {
                        $sort = $this->buildLegacySortingForField($field, $direction);
                        $sort = $this->replaceTablePrefix($sort);
                        $this->unsafe()->unsafeAppendSQLPart('order by', $sort);
                    }
                }
            }
            // No sort specified, so just sort on system id
            if (!$this->containsSQLParts('order by')) {
                $this->sort('system:id');
            }
        }
        return $this;
    }

    /**
     * Creates a specialized version of DatabaseQueryResult to hold
     * result from the current EntryQuery.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return EntryQueryResult
     *  The wrapped result
     */
    public function results($success, PDOStatement $stm)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        return new EntryQueryResult($success, $stm, $this, $this->page, $this->schema);
    }
}
