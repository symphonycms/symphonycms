<?php

/**
 * @package toolkit
 */
/**
 * This class hold the data created by the execution of a ExtensionQuery object.
 * This class is also responsible for creating the Extension object based on what's
 * retrieved from the database.
 */
class ExtensionQueryResult extends DatabaseQueryResult
{
    /**
     * Retrieves the the next available record and builds Extension object with it.
     *
     * @see buildExtension()
     * @return Extension
     *  The next available Entry object.
     *  null if there are not more available records.
     */
    public function next()
    {
        $next = parent::next();
        if ($next) {
            $next = $this->buildExtension($next);
        }
        return $next;
    }

    /**
     * Given a $row from the database, builds a complete Extension object with it.
     *
     * @param array $row
     *  One result from the database
     * @return Extension
     *  The newly created Extension instance, populated with all its data.
     */
    public function buildExtension(array $row)
    {
        if (!empty($row['name'])) {
            $ex = ExtensionManager::getInstance($row['name']);
            $ex->setFields($row);
        }
        return $row;
    }
}
