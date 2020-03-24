<?php

/**
 * @package toolkit
 */
/**
 * This class hold the data created by the execution of a SectionQuery object.
 * This class is also responsible for creating the Section object based on what's
 * retrieved from the database.
 */
class SectionQueryResult extends DatabaseQueryResult
{
    /**
     * @see buildSection()
     * @return Section
     */
    protected function process($next)
    {
        return $this->buildSection($next);
    }

    /**
     * Given a $row from the database, builds a complete Section object with it.
     * Also makes sure the date are properly set and formatted.
     *
     * @param array $row
     *  One result from the database
     * @return Section
     *  The newly created Field instance, populated with all its data.
     */
    public function buildSection(array $row)
    {
        if (!isset($row['id'], $row['creation_date'])) {
            return $row;
        }

        $obj = SectionManager::create();

        foreach ($row as $name => $value) {
            $obj->set($name, $value);
        }

        $obj->set('creation_date', DateTimeObj::get('c', $obj->get('creation_date')));

        $modDate = $obj->get('modification_date');
        if (!empty($modDate)) {
            $obj->set('modification_date', DateTimeObj::get('c', $obj->get('modification_date')));
        } else {
            $obj->set('modification_date', $obj->get('creation_date'));
        }

        return $obj;
    }
}
