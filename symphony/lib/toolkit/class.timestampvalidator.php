<?php
/**
 * @package toolkit
 */

/**
 * This class checks against the Database for the latest timestamp, to make
 * sure the data being saved is the one the user saw.
 *
 * @since Symphony 2.7.0
 */

class TimestampValidator
{
    /**
     * The name of the table to check against
     * @var string
     */
    private $table = '';

    /**
     * Creates a new TimestampValidator object, for a particular table.
     * The `tbl_` prefix is automatically added.
     *
     * @param string $table
     *   The table name
     */
    public function __construct($table)
    {
        General::ensureType(array(
            'table' => array('var' => $table, 'type' => 'string'),
        ));
        $this->table = 'tbl_' . $table;
    }

    /**
     * Checks if the modified date of the record identified with $id
     * if equal to the supplied $timestamp
     *
     * @param int|string $id
     *  The record id to check
     * @param string $timestamp
     *  The user supplied timestamp
     * @return boolean
     *  true if the $timestamp is the latest or the $id is invalid, false other wise
     */
    public function check($id, $timestamp)
    {
        $id = General::intval($id);
        if ($id < 1) {
            return false;
        }
        $result = Symphony::Database()
            ->select(['id'])
            ->from($this->table)
            ->where(['id' => $id])
            ->where(['modification_date' => $timestamp])
            ->execute()
            ->integer('id');
        return $result === $id;
    }
}
