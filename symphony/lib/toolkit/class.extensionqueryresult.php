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
     * @see buildExtension()
     * @return Extension
     */
    protected function process($next)
    {
        return $this->buildExtension($next);
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
        if (!isset($row['id'], $row['name'])) {
            return $row;
        }

        $ex = ExtensionManager::getInstance($row['name']);
        $ex->setFields($row);
        return $row;
    }
}
