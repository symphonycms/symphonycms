<?php

/**
 * @package toolkit
 */
/**
 * This class hold the data created by the execution of a PageQuery object.
 * This class is also responsible for creating the tree view of the page structure.
 */
class PageQueryResult extends DatabaseQueryResult
{
    /**
     * Flag to fetch all the pages types.
     * @var boolean
     */
    private $includeTypes = false;

    /**
     * Creates a new PageQueryResult object, containing its $success parameter,
     * the resulting $stm statement and the $includeTypes flag.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @param DatabaseQuery $query
     * The query that created this result
     * @param array $page
     * The pagination information, if any.
     * @param bool $includeTypes
     *  If we need to fetch all the pages types. Defaults to false.
     */
    public function __construct($success, PDOStatement $stm, DatabaseQuery $query, array $page = [], $includeTypes = false)
    {
        parent::__construct($success, $stm, $query, $page);
        $this->includeTypes = $includeTypes;
    }

    /**
     * @see buildPage()
     * @return array
     */
    protected function process($next)
    {
        return $this->buildPage($next);
    }

    /**
     * Retrieves all available rows and structures them into a tree view.
     * Child entries are put in the children key.
     *
     * @see rows()
     * @see findChildren()
     * @return array
     *  An array of objects or arrays
     */
    public function tree()
    {
        return $this->findChildren(null, $this->rows());
    }

    /**
     * Given a $row from the database, fetches the types of page is required.
     *
     * @param array $row
     *  One result from the database
     * @return array
     *  The page row, populated with all its types in the type key
     */
    public function buildPage(array $row)
    {
        if (!isset($row['id'], $row['handle'])) {
            return $row;
        }

        if ($this->includeTypes) {
            // Fetch the Page Types for each page, if required
            $row['type'] = PageManager::fetchPageTypes($row['id']);
        }
        return $row;
    }

    /**
     * @internal Searches into the provided $pages array and returns only those
     * that have a matching parent id.
     *
     * @param int $parent_id
     *  The page id for which to find its children
     * @param array $pages
     *  All the pages to search in
     * @return array
     *  All the children of the parent page
     */
    public function findChildren($parent_id, array $pages)
    {
        if (!is_array($pages)) {
            return [];
        }
        $results = [];
        foreach ($pages as $page) {
            if (!isset($page['parent']) || !isset($page['id'])) {
                continue;
            }
            if ($page['parent'] == $parent_id) {
                $page['children'] = $this->findChildren($page['id'], $pages);
                $results[] = $page;
            }
        }
        return $results;
    }
}
