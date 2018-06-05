<?php

/**
 * @package toolkit
 */
/**
 * Specialized DatabaseQuery that facilitate creation of queries on the fields table.
 */
class FieldQuery extends DatabaseQuery
{
    /**
     * Flag to indicate if the statement needs to add the default ORDER BY clause
     * @var boolean
     */
    private $addDefaultSort = true;

    /**
     * Creates a new FieldQuery statement on table `tbl_fields` with an optional projection.
     * The table is aliased to `f`.
     *
     * @see ExtensionManager::select()
     * @param Database $db
     *  The underlying database connection
     * @param array $projection
     *  The columns names for include in the projection.
     *  Defaults to an empty projection.
     */
    public function __construct(Database $db, array $projection = [])
    {
        parent::__construct($db, $projection);
        $this->from('tbl_fields')->alias('f');
    }

    /**
     * Disables the default sort
     * @return FieldQuery
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
        return ['f.*'];
    }

    /**
     * Adds a WHERE clause on the section id.
     *
     * @param int $section_id
     *  The section id for which to look for
     * @return FieldQuery
     *  The current instance
     */
    public function section($section_id)
    {
        return $this->where(['f.parent_section' => General::intval($section_id)]);
    }

    /**
     * Adds a WHERE clause on the field id.
     *
     * @param int $field_id
     *  The field id to fetch
     * @return FieldQuery
     *  The current instance
     */
    public function field($field_id)
    {
        return $this->where(['f.id' => General::intval($field_id)]);
    }

    /**
     * Adds a WHERE clause on the field id.
     *
     * @param array $field_ids
     *  The field ids to fetch
     * @return FieldQuery
     *  The current instance
     */
    public function fields(array $field_ids)
    {
        return $this->where(['f.id' => ['in' => array_map(['General', 'intval'], $field_ids)]]);
    }

    /**
     * Adds a WHERE clause on the field name.
     *
     * @param string $name
     *  The field name to fetch
     * @return FieldQuery
     *  The current instance
     */
    public function name($name)
    {
        return $this->where(['f.element_name' => $name]);
    }

    /**
     * Adds a WHERE clause on the field type.
     *
     * @param string $type
     *  The field type to fetch
     * @return FieldQuery
     *  The current instance
     */
    public function type($type)
    {
        return $this->where(['f.type' => $type]);
    }

    /**
     * Adds a WHERE clause on the field location.
     *
     * @param string $location
     *  The field location to fetch
     * @return FieldQuery
     *  The current instance
     */
    public function location($location)
    {
        return $this->where(['f.location' => $location]);
    }

    /**
     * Appends a ORDER BY clause using the $field parameter.
     *
     * @param string $field
     *  The field to order by with
     * @param string $direction
     *  The default direction to use.
     *  Defaults to ASC.
     * @return FieldQuery
     *  The current instance
     */
    public function sort($field, $direction = 'ASC')
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
            'direction' => ['var' => $direction, 'type' => 'string'],
        ]);
        return $this->orderBy(["f.$field" => $direction]);
    }

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * Appends any remaining part of the statement.
     * If no sort is specified, it will default to the sort order.
     *
     * @see DatabaseStatement::execute()
     * @return FieldQuery
     *  The current instance
     */
    public function finalize()
    {
        if ($this->addDefaultSort && !$this->containsSQLParts('order by')) {
            $this->sort('sortorder');
        }
        return parent::finalize();
    }

    /**
     * Creates a specialized version of DatabaseQueryResult to hold
     * result from the current FieldQuery.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return FieldQueryResult
     *  The wrapped result
     */
    public function results($success, PDOStatement $stm)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        return new FieldQueryResult($success, $stm, $this, $this->page);
    }
}
