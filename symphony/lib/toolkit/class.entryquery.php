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
     * Flag to indicate if the statement needs to add the default projection
     * @var boolean
     */
    private $addDefaultProjection = true;

    /**
     * The requested section id. Needed to fetch the default sort.
     * @var integer
     */
    private $sectionId = 0;

    /**
     * The requested fields id via joins and used in where and order by.
     * This allows limiting the number of generated joins.
     * @var array
     */
    private $fieldIds = [];

    /**
     * Creates a new EntryQuery statement on table `tbl_entries` with an optional projection.
     * The table is aliased to `e`.
     * The mandatory values are already added to the projection.
     *
     * @see EntryManager::select()
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
     * @see finalize()
     * @return EntryQuery
     *  The current instance
     */
    public function disableDefaultSort()
    {
        $this->addDefaultSort = false;
        return $this;
    }

    /**
     * Gets the default projection to use if no projection is added.
     *
     * @see DatabaseQuery::getDefaultProjection()
     * @return array
     */
    public function getDefaultProjection()
    {
        return ['e.*'];
    }

    /**
     * Disables the default projection
     * @see finalize()
     * @return EntryQuery
     *  The current instance
     */
    public function disableDefaultProjection()
    {
        $this->addDefaultProjection = false;
        return $this;
    }

    /**
     * Gets the minimal projection. Those are the absolute minimum we need to
     * be able to create Entry objects.
     *
     * @return array
     */
    public function getMinimalProjection()
    {
        return ['e.id', 'e.creation_date', 'e.modification_date'];
    }

    /**
     * Appends COUNT($col) to the projection.
     * Prevents the default sort and projection to be added.
     *
     * @uses disableDefaultSort();
     * @uses disableDefaultProjection();
     * @see DatabaseQuery::count()
     * @param string $col
     *  The column to count on.
     * @return DatabaseQuery
     */
    public function count($col = null)
    {
        $this->disableDefaultSort()->disableDefaultProjection();
        return parent::count($col);
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
     * Checks if the $field_id has already been joined.
     *
     * @see EntryQuery::fieldIds
     * @param int $field_id
     *  The requested Field id
     * @return boolean
     *  true if the field is already joined, false otherwise
     */
    public function isFieldJoined($field_id)
    {
        return !empty($this->fieldIds[$field_id]);
    }

    /**
     * Marks this Field as joined by saving its id and join type.
     *
     * @param int $field_id
     *  The requested Field id
     * @param string $join
     *  The type of joined used
     * @return void
     */
    protected function fieldJoined($field_id, $join)
    {
        $this->fieldIds[$field_id] = $join;
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
            return General::intval($field['id']);
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
     * @uses disableDefaultSort()
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
     * Appends a unique INNER JOIN `tbl_entries_data_$field_id` ON entry_id clause.
     * The joined table is aliased as `f{$field_id}`.
     * All other subsequent join calls will do nothing.
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
        if ($this->isFieldJoined($field_id)) {
            return $this;
        }
        $this->fieldJoined($field_id, 'inner join');
        return $this
            ->innerJoin("tbl_entries_data_$field_id", "f{$field_id}")
            ->on(['e.id' => "\$f{$field_id}.entry_id"]);
    }

    /**
     * Appends a unique JOIN `tbl_entries_data_$field_id` ON entry_id clause.
     * The joined table is aliased as `f{$field_id}`.
     * All other subsequent join calls will do nothing.
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
        if ($this->isFieldJoined($field_id)) {
            return $this;
        }
        $this->fieldJoined($field_id, 'join');
        return $this
            ->join("tbl_entries_data_$field_id", "f{$field_id}")
            ->on(['e.id' => "\$f{$field_id}.entry_id"]);
    }

    /**
     * Appends a unique LEFT JOIN `tbl_entries_data_$field_id` ON entry_id clause.
     * The joined table is aliased as `f{$field_id}`.
     * All other subsequent join calls will do nothing.
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
        if ($this->isFieldJoined($field_id)) {
            return $this;
        }
        $this->fieldJoined($field_id, 'left join');
        return $this
            ->leftJoin("tbl_entries_data_$field_id", "f{$field_id}")
            ->on(['e.id' => "\$f{$field_id}.entry_id"]);
    }

    /**
     * Appends a unique OUTER JOIN `tbl_entries_data_$field_id` ON entry_id clause.
     * The joined table is aliased as `f{$field_id}`.
     * All other subsequent join calls will do nothing.
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
        if ($this->isFieldJoined($field_id)) {
            return $this;
        }
        $this->fieldJoined($field_id, 'outer join');
        return $this
            ->outerJoin("tbl_entries_data_$field_id", "f{$field_id}")
            ->on(['e.id' => "\$f{$field_id}.entry_id"]);
    }

    /**
     * Appends a unique RIGHT JOIN `tbl_entries_data_$field_id` ON entry_id clause.
     * The joined table is aliased as `f{$field_id}`.
     * All other subsequent join calls will do nothing.
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
        if ($this->isFieldJoined($field_id)) {
            return $this;
        }
        $this->fieldJoined($field_id, 'right join');
        return $this
            ->rightJoin("tbl_entries_data_$field_id", "f{$field_id}")
            ->on(['e.id' => "\$f{$field_id}.entry_id"]);
    }

    /**
     * Appends a WHERE clause with one or many conditions.
     * Calling this method multiple times will join the WHERE clauses with a AND.
     * If the field as not been previously joined, it will append a left join.
     *
     * @see DatabaseQuery::where()
     * @param int $field_id
     *  The field id to filter with
     * @param array $conditions
     *  The where conditions to apply on the field table. In order to work with
     *  the generated join, keys needs to be prefixed with `f{$field_id}.` to make
     *  them unambiguous.
     * @return EntryQuery
     *  The current instance
     */
    public function whereField($field_id, array $conditions)
    {
        General::ensureType([
            'field_id' => ['var' => $field_id, 'type' => 'int'],
        ]);
        if (!$this->isFieldJoined($field_id)) {
            $this->leftJoinField($field_id);
        }
        return $this->where($conditions);
    }

    /**
     * Appends a WHERE clause using the $field parameter.
     * Calling this method multiple times will join the WHERE clauses with a AND.
     *
     * @see Field::getEntryQueryFieldAdapter()
     * @param mixed $field
     *  The field id, as a string, the field name or a Field object to filter with.
     *  Can also be a 'system:' field, i.e.
     *  'system:creation-date', 'system:modification-date', 'system:id'.
     *  If null, it simply returns.
     * @param array $values
     *  The values to filter with.
     * @param string $operator
     *  The operator to use to group all $values clauses.
     * @return EntryQuery
     *  The current instance
     */
    public function filter($field, array $values, $operator = 'or')
    {
        General::ensureType([
            'operator' => ['var' => $operator, 'type' => 'string'],
        ]);

        if (!$field) {
            return $this;
        } elseif (empty($values)) {
            return $this;
        }


        $f = null;

        // Handle filter on Creation Date
        if ($field === 'system:creation-date') {
            return $this->where([$operator => array_map(function ($v) {
                $date = (new DateRangeParser($v))->parse();
                return ['e.creation_date' => ['date' => $date]];
            }, $values)]);

        // Handle filter on Modification Date sorting
        } elseif ($field === 'system:modification-date') {
            return $this->where([$operator => array_map(function ($v) {
                $date = (new DateRangeParser($v))->parse();
                return ['e.modification_date' => ['date' => $date]];
            }, $values)]);

        // Handle filter for System ID
        } elseif ($field === 'system:id') {
            $op = '=';
            if (stripos($values[0], 'not:') === 0) {
                $values[0] = preg_replace('/^not:\s*/', null, $values[0]);
                $op = '!=';
                $operator = 'and';
            }
            // Reduce multi-dimension array
            $values = array_reduce($values, function ($memo, $v) {
                $v = array_map('trim', explode(',', $v));
                // Cast all ID's to integers. (RE: #2191)
                return array_merge($memo, array_map(function ($val) {
                    $val = General::intval($val);

                    // General::intval can return -1, so reset that to 0
                    // so there are no side effects for the following
                    // array_filter calls. RE: #2475
                    if ($val === -1) {
                        $val = 0;
                    }

                    return $val;
                }, $v));
            }, []);
            
            $sum = array_sum($values);
            $values = array_filter($values);
            
            // If the ID was cast to 0, then we need to filter on 'id' = 0,
            // which will of course return no results, but without it the
            // Datasource will return ALL results, which is not the
            // desired behaviour. RE: #1619
            if ($sum === 0) {
                $values[] = 0;
            }

            // Check if reduce produced values
            if (empty($values)) {
                return $this;
            }

            // Create conditions from values
            $conditions = array_map(function ($v) use ($op) {
                return ['e.id' => [$op => $v]];
            }, $values);

            if (count($conditions) > 1) {
                $conditions = [$operator => $conditions];
            }

            return $this->where($conditions);

        // Handle when the filter field is a field id
        } elseif (General::intval($field) > 0) {
            $f = (new FieldManager)->select()->field($field)->execute()->next();
            if ($f) {
                $field = $f->name();
            }

        // Handle when the filter field is a field name
        } elseif (is_string($field)) {
            if (!$this->sectionId) {
                throw new DatabaseStatementException('Can not filter with a field name without a section');
            }

            $f = (new FieldManager)->select()->name($field)->section($this->sectionId)->execute()->next();
            if ($f) {
                $field = $f->name();
            }

        // Handle when the filter field is a field object
        } elseif ($field instanceof Field) {
            $f = $field;
            $field = $f->name();
        }

        if (!$f) {
            throw new DatabaseStatementException("Invalid filter on field `$field`");
        } elseif (!$f->canFilter()) {
            throw new DatabaseStatementException("Field `$field` does not allow filtering");
        } elseif (!$f->getEntryQueryFieldAdapter()) {
            throw new DatabaseStatementException("Field `$field` does not have an EntryQueryFieldAdapter");
        }

        if ($f->requiresSQLGrouping() && !$this->containsSQLParts('optimizer')) {
            $this->distinct();
        }

        $f->getEntryQueryFieldAdapter()->filter($this, $values, $operator);

        return $this;
    }

    /**
     * Appends a ORDER BY clause using the $field parameter.
     *
     * @see Field::getEntryQueryFieldAdapter()
     * @param string $field
     *  The field id, as a string, to order by with.
     *  Can also be a 'system:' field, i.e.
     *  'system:creation-date', 'system:modification-date', 'system:id'.
     *  If null, it simply returns.
     * @param string $direction
     *  The default direction to use.
     *  Supports ASC, DESC and RAND
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

        if (strtoupper($direction) === 'RAND' || strtoupper($direction) === 'RANDOM') {
            $sort = ['e.id' => 'RAND()'];

        // Handle Creation Date
        } elseif ($field === 'system:creation-date') {
            $sort = ['e.creation_date' => $direction];

        // Handle Modification Date sorting
        } elseif ($field === 'system:modification-date') {
            $sort = ['e.modification_date' => $direction];

        // Handle sorting for System ID
        } elseif ($field === 'system:id') {
            $sort = ['e.id' => $direction];

        // Handle either by id or by handle when the sort field is an actual Field
        } elseif (!empty($field)) {
            if (is_string($field) && General::intval($field) === -1) {
                if (!$this->sectionId) {
                    throw new DatabaseStatementException('Can not sort with a field name without a section');
                }
                $f = (new FieldManager)
                    ->select()
                    ->name($field)
                    ->section($this->sectionId)
                    ->execute()
                    ->next();
            } elseif (General::intval($field) > 0) {
                $f = (new FieldManager)
                    ->select()
                    ->field($field)
                    ->execute()
                    ->next();
            }

            if ($f && $f->isSortable()) {
                if ($f->getEntryQueryFieldAdapter()) {
                    $f->getEntryQueryFieldAdapter()->sort($this, $direction);
                    // No need to touch the query!
                    $sort = true;
                } else {
                    $sort = $this->buildLegacySortingForField($f, $direction);
                }
            } else {
                // Field not found or not sortable, silence the error.
                // This prevents crashing the backend for a bad reason.
                $sort = true;
            }
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
     * @return string
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
     * This implementation only changes the default $col value for pagination.
     * This fixes problem when sorting by fields which includes multiple rows per entry.
     *
     * @see EntryQuery::countProjection()
     * @param string $col
     *  The column to count on. Defaults to DISTINCT(e.id)
     * @return DatabaseQuery
     */
    public function countProjection($col = 'DISTINCT(e.id)')
    {
        return parent::countProjection($col);
    }

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * Appends any remaining part of the statement.
     * If the default sort is not disabled and their are not custom sort added,
     * it will add the default sort.
     * If the default and minimal projection are not present, it will add the
     * default projection.
     *
     * @see getDefaultProjection()
     * @see getMinimalProjection()
     * @see DatabaseStatement::execute()
     * @return EntryQuery
     *  The current instance
     */
    public function finalize()
    {
        // Get a flatten projection
        $projection = $this->getSQLParts('projection');
        General::flattenArray($projection);
        $projection = array_values($projection);
        // Try to find default projections
        $hasDefault = !empty($projection) && in_array($this->asProjectionList($this->getDefaultProjection()), $projection);
        $hasCols = !empty($projection) && in_array($this->asProjectionList($this->getMinimalProjection()), $projection);
        // Add sort, if needed
        if ($this->addDefaultSort && !$this->containsSQLParts('order by')) {
            // Handle if the section has a default sorting field
            if ($this->sectionId) {
                $section = (new SectionManager)->select()->section($this->sectionId)->execute()->next();
                if ($section && $section->getSortingField()) {
                    $this->sort($section->getSortingField(), $section->getSortingOrder());
                }
            }
            // No sort specified, so just sort on system id
            if (!$this->containsSQLParts('order by')) {
                $this->sort('system:id');
            }
        }
        // Add default projection to make sure we are able to build Entry objects, if required
        if ($this->addDefaultProjection && !$hasDefault && !$hasCols) {
            $this->projection($this->getDefaultProjection());
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
