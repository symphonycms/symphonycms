<?php

/**
 * @package toolkit
 */

/**
 * Specialized DatabaseQuery that facilitate creation of queries on the pages table.
 */
class PageQuery extends DatabaseQuery
{
    /**
     * Flag to fetch all the pages types.
     * @var boolean
     */
    private $includeTypes = false;

    /**
     * Flag to indicate if the statement needs to add the default ORDER BY clause
     * @var boolean
     */
    private $addDefaultSort = true;

    /**
     * Creates a new PageQuery statement on table `tbl_pages` with an optional projection.
     * The table is aliased to `p`.
     *
     * @see PageManager::select()
     * @param Database $db
     *  The underlying database connection
     * @param array $projection
     *  The columns names for include in the projection.
     *  Defaults to an empty projection.
     */
    public function __construct(Database $db, array $projection = [])
    {
        parent::__construct($db, $projection);
        $this->from('tbl_pages')->alias('p');
    }

    /**
     * Disables the default sort
     * @return PageQuery
     *  The current instance
     */
    public function disableDefaultSort()
    {
        $this->addDefaultSort = false;
        return $this;
    }

    /**
     * Enables the fetching of the pages types
     * @return PageQuery
     *  The current instance
     */
    public function includeTypes()
    {
        $this->includeTypes = true;
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
        return ['p.*'];
    }

    /**
     * Adds a WHERE clause on the page id.
     *
     * @param int $page_id
     *  The page id to fetch
     * @return PageQuery
     *  The current instance
     */
    public function page($page_id)
    {
        return $this->where(['p.id' => General::intval($page_id)]);
    }

    /**
     * Adds a WHERE clause on the page id.
     *
     * @param array $page_ids
     *  The page ids to fetch
     * @return PageQuery
     *  The current instance
     */
    public function pages(array $page_ids)
    {
        return $this->where(['p.id' => ['in' => array_map(['General', 'intval'], $page_ids)]]);
    }

    /**
     * Adds a WHERE clause on the page handle.
     *
     * @param int $handle
     *  The page handle to fetch
     * @return PageQuery
     *  The current instance
     */
    public function handle($handle)
    {
        return $this->where(['p.handle' => $handle]);
    }

    /**
     * Adds a WHERE clause on the page parent.
     *
     * @param int $parent
     *  The page parent to fetch
     * @return PageQuery
     *  The current instance
     */
    public function parent($parent)
    {
        return $this->where(['p.parent' => $parent]);
    }

    /**
     * Adds a WHERE clause on the page path.
     *
     * @param int $path
     *  The page path to fetch
     * @return PageQuery
     *  The current instance
     */
    public function path($path)
    {
        return $this->where(['p.path' => $path]);
    }

    /**
     * Appends a ORDER BY clause using the $field parameter.
     *
     * @param string $field
     *  The field to order by with
     * @param string $direction
     *  The default direction to use.
     *  Defaults to ASC.
     * @return PageQuery
     *  The current instance
     */
    public function sort($field, $direction = 'ASC')
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
            'direction' => ['var' => $direction, 'type' => 'string'],
        ]);
        return $this->orderBy(["p.$field" => $direction]);
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
     * result from the current PageQuery.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return PageQueryResult
     *  The wrapped result
     */
    public function results($success, PDOStatement $stm)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        return new PageQueryResult($success, $stm, $this, $this->page, $this->includeTypes);
    }
}
