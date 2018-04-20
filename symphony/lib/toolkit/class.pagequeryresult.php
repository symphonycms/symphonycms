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
     * @param bool $includeTypes
     *  If we need to fetch all the pages types. Defaults to false.
     */
    public function __construct($success, PDOStatement $stm, $includeTypes = false)
    {
        parent::__construct($success, $stm);
        $this->includeTypes = $includeTypes;
    }

    /**
     * Retrieves the the next available record and adds additional informations if needed.
     *
     * @see buildPage()
     * @see tree()
     * @return array
     *  The next available page array.
     *  null if there are not more available records.
     */
    public function next()
    {
        $next = parent::next();
        if ($next) {
            $next = $this->buildPage($next);
        }
        return $next;
    }

    /**
     * Retrieves all available rows and structure them into a tree view.
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

    public function buildPage(array $row)
    {
        if ($this->includeTypes && !empty($row['id'])) {
            // Fetch the Page Types for each page, if required
            $row['type'] = PageManager::fetchPageTypes($row['id']);
        }
        return $row;
    }

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
