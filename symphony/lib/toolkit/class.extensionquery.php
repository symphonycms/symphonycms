<?php

/**
 * @package toolkit
 */
/**
 * Specialized DatabaseQuery that facilitate creation of queries on the extensions table.
 */
class ExtensionQuery extends DatabaseQuery
{
    /**
     * Flag to indicate if the statement needs to add the default ORDER BY clause
     * @var boolean
     */
    private $addDefaultSort = true;

    /**
     * Creates a new ExtensionQuery statement on table `tbl_extensions` with an optional projection.
     * The table is aliased to `ex`.
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
        $this->from('tbl_extensions')->alias('ex');
    }

    /**
     * Disables the default sort
     * @return ExtensionQuery
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
        return ['ex.*'];
    }

    /**
     * Adds a WHERE clause on the extension id.
     *
     * @param int $extension_id
     *  The extension id to fetch
     * @return ExtensionQuery
     *  The current instance
     */
    public function extension($extension_id)
    {
        return $this->where(['ex.id' => General::intval($extension_id)]);
    }

    /**
     * Adds a WHERE clause on the status column.
     *
     * @see enabled()
     * @see disabled()
     * @param string $status
     *  The extension status to fetch
     * @return ExtensionQuery
     *  The current instance
     */
    public function status($status)
    {
        return $this->where(['ex.status' => $status]);
    }

    /**
     * Adds a WHERE clause on the status column with the value 'enabled'
     *
     * @see status()
     * @return ExtensionQuery
     *  The current instance
     */
    public function enabled()
    {
        return $this->status('enabled');
    }

    /**
     * Adds a WHERE clause on the status column with the value 'disabled'
     *
     * @see status()
     * @return ExtensionQuery
     *  The current instance
     */
    public function disabled()
    {
        return $this->status('disabled');
    }

    /**
     * Appends a ORDER BY clause using the $field parameter.
     *
     * @param string $field
     *  The field to order by with
     * @param string $direction
     *  The default direction to use.
     *  Defaults to ASC.
     * @return ExtensionQuery
     *  The current instance
     */
    public function sort($field, $direction = 'ASC')
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
            'direction' => ['var' => $direction, 'type' => 'string'],
        ]);
        return $this->orderBy(["ex.$field" => $direction]);
    }

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * Appends any remaining part of the statement.
     * If no sort is specified, it will default to the sort order.
     *
     * @see DatabaseStatement::execute()
     * @return ExtensionQuery
     *  The current instance
     */
    public function finalize()
    {
        if ($this->addDefaultSort && !$this->containsSQLParts('order by')) {
            $this->sort('name');
        }
        return parent::finalize();
    }

    /**
     * Creates a specialized version of DatabaseQueryResult to hold
     * result from the current ExtensionQuery.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return ExtensionQueryResult
     *  The wrapped result
     */
    public function results($success, PDOStatement $stm)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        return new ExtensionQueryResult($success, $stm, $this, $this->page);
    }
}
