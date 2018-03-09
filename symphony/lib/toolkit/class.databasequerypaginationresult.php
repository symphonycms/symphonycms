<?php

/**
 * @package toolkit
 */
/**
 * This class hold the data created by the execution of a paginated query.
 * It also hold the original DatabaseQueryResult object created by the execution of the query.
 */
class DatabaseQueryPaginationResult
{
    /**
     * Storage for all the data
     * @var array
     */
    private $page = [
        'total-entries' => 0,
        'total-pages' => 1,
        'remaining-pages' => 0,
        'remaining-entries' => 0,
        'page' => 1,
        'size' => 1,
    ];

    /**
     * The wrapped DatabaseQueryResult
     * @var DatabaseQueryResult
     */
    private $result;

    /**
     * Creates a new, DatabaseQueryPaginationResult wrapping the $result and the $page.
     *
     * @param DatabaseQueryResult $result
     *  The query result to wrap
     * @param array $page
     *  The pagination information
     */
    public function __construct(DatabaseQueryResult $result, array $page = [])
    {
        $this->result = $result;
        $this->page = array_merge($this->page, $page);
        if ($this->totalEntries() > 0) {
            $this->page['remaining-entries'] = max(
                0,
                $this->totalEntries() - $this->currentPage() + $this->pageSize()
            );
            $this->page['total-pages'] = max(
                1,
                (int)ceil($this->totalEntries() * (1 / $this->pageSize()))
            );
            $this->page['remaining-pages'] = max(
                0,
                $this->totalPages() - $this->currentPage()
            );
        }
    }

    /**
     * @see DatabaseQueryResult::next()
     * @return array|object
     */
    public function next()
    {
        return $this->result->next();
    }

    /**
     * @see DatabaseQueryResult::rows()
     * @return array
     */
    public function rows()
    {
        return $this->result->rows();
    }

    /**
     * Returns the total number of entries, across all pages.
     *
     * @return int
     */
    public function totalEntries()
    {
        return $this->page['total-entries'];
    }

    /**
     * Returns the total number of pages
     *
     * @return int
     */
    public function totalPages()
    {
        return $this->page['total-pages'];
    }

    /**
     * Returns the number of remaining entries
     *
     * @return int
     */
    public function remainingEntries()
    {
        return $this->page['remaining-entries'];
    }

    /**
     * Returns the number of remaining pages
     *
     * @return int
     */
    public function remainingPages()
    {
        return $this->page['remaining-pages'];
    }

    /**
     * Returns the current page number, as requested
     *
     * @see DatabaseQuery::paginate()
     * @return int
     */
    public function currentPage()
    {
        return $this->page['page'];
    }

    /**
     * Returns the current page size, as requested
     *
     * @see DatabaseQuery::paginate()
     * @return int
     */
    public function pageSize()
    {
        return $this->page['size'];
    }
}
