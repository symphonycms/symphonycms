<?php

/**
 * @package toolkit
 */
/**
 * This class is a specialized DatabaseTabularResult that allows retrieving
 * paginated records.
 */
class DatabaseQueryResult extends DatabaseTabularResult
{
    /**
     * The query that created this result
     * @var DatabaseQuery
     */
    private $query;

    /**
     * The requested pagination.
     * @var array
     */
    private $page;

    /**
     * Creates a new DatabaseStatementResult object, containing its $success parameter
     * and the resulting $stm statement.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @param DatabaseQuery $query
     *  The query that created this result
     * @param array $page
     *  The pagination information, if any.
     */
    public function __construct($success, PDOStatement $stm, DatabaseQuery $query, array $page = [])
    {
        parent::__construct($success, $stm);
        $this->query = $query;
        $this->page = $page;
    }

    /**
     * Wraps the current query result into a pagination result.
     * It retrieves all the information needed to be able to create pagination elements.
     *
     * @see DatabaseQuery::paginate()
     * @return DatabaseQueryPaginationResult
     *  The pagination result set
     */
    public function pagination()
    {
        $countQuery = $this->query->countProjection();
        $this->page['total-entries'] = $countQuery->execute()->integer(0);
        return new DatabaseQueryPaginationResult($this, $this->page);
    }
}
