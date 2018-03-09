<?php

/**
 * @package toolkit
 */
/**
 * This class hold the data created by the execution of a AuthorQuery object.
 * This class is also responsible for creating the Author object based on what's
 * retrieved from the database.
 */
class AuthorQueryResult extends DatabaseQueryResult
{
    /**
     * Retrieves the the next available record and builds a Author object with it.
     *
     * @see buildAuthor()
     * @return Author
     *  The next available Author object.
     *  null if there are not more available records.
     */
    public function next()
    {
        $next = parent::next();
        if ($next) {
            $next = $this->buildAuthor($next);
        }
        return $next;
    }

    /**
     * Given a $row from the database, builds a complete Author object with it.
     *
     * @param array $row
     *  One result from the database
     * @return Author
     *  The newly created Author instance, populated with all its data.
     */
    public function buildAuthor(array $row)
    {
        if (!isset($row['id'], $row['username'])) {
            return $row;
        }
        $author = AuthorManager::create();
        $author->setFields($row);
        return $author;
    }
}
