<?php

class DatabaseSession
{
    /**
     * Allows the Session to close without any further logic. Acts as a
     * destructor function for the Session.
     *
     * @return boolean
     *  Always returns true
     */
    public static function close()
    {
        return true;
    }

    /**
     * Given a session's ID, remove it's row from `tbl_sessions`
     *
     * @param string $id
     *  The identifier for the Session to destroy
     * @throws DatabaseException
     * @return boolean
     *  True if the Session was deleted successfully, false otherwise
     */
    public static function destroy($session_id)
    {
        return Symphony::Database()->query(
            sprintf(
                "DELETE FROM `tbl_sessions` WHERE `session` = '%s'",
                Symphony::Database()->cleanValue($id)
            )
        );
    }

    /**
     * The garbage collector, which removes all empty Sessions, or any
     * Sessions that have expired. This has a 10% chance of firing based
     * off the `gc_probability`/`gc_divisor`.
     *
     * @param integer $max
     *  The max session lifetime.
     * @throws DatabaseException
     * @return boolean
     *  True on Session deletion, false if an error occurs
     */
    public static function gc($maxlifetime)
    {
        return Symphony::Database()->query(
            sprintf(
                "DELETE FROM `tbl_sessions` WHERE `session_expires` <= '%s'",
                Symphony::Database()->cleanValue(time() - $max)
            )
        );
    }

    /**
     * Allows the Session to open without any further logic.
     *
     * @return boolean
     *  Always returns true
     */
    public static function open()
    {
        return true;
    }

    /**
     * Given a session's ID, return it's row from `tbl_sessions`
     *
     * @param string $id
     *  The identifier for the Session to fetch
     * @return string
     *  The serialised session data
     */
    public static function read($session_id)
    {
        return Symphony::Database()->fetchVar(
            'session_data',
            0,
            sprintf(
                "SELECT `session_data` FROM `tbl_sessions` WHERE `session` = '%s' LIMIT 1",
                Symphony::Database()->cleanValue($id)
            )
        );
    }

    /**
     * Given an ID, and some data, save it into `tbl_sessions`. This uses
     * the ID as a unique key, and will override any existing data. If the
     * `$data` is deemed to be empty, no row will be saved in the database
     * unless there is an existing row.
     *
     * @param string $id
     *  The ID of the Session, usually a hash
     * @param string $data
     *  The Session information, usually a serialized object of
     * `$_SESSION[Cookie->_index]`
     * @throws DatabaseException
     * @return boolean
     *  True if the Session information was saved successfully, false otherwise
     */
    public static function write($session_id, $session_data)
    {
        // Only prevent this record from saving if there isn't already a record
        // in the database. This prevents empty Sessions from being created, but
        // allows them to be nulled.
        $session_data = Session::read($id);

        if (is_null($session_data)) {
            $empty = true;
            $unserialized_data = Session::unserialize_session($session_data);

            foreach ($unserialized_data as $d) {
                if (!empty($d)) {
                    $empty = false;
                }
            }

            if ($empty) {
                return false;
            }
        }

        $fields = array(
            'session' => $id,
            'session_expires' => time(),
            'session_data' => $data
        );

        return Symphony::Database()->insert($fields, 'tbl_sessions', true);
    }

    /**
     * Given raw session data return the unserialized array.
     * Used to check if the session is really empty before writing.
     *
     * @since Symphony 2.3.3
     * @param string $data
     *  The serialized session data
     * @return string
     *  The unserialised session data
     */
    private static function unserialize_session($data) {
        $hasBuffer = isset($_SESSION);
        $buffer = $_SESSION;
        session_decode($data);
        $session = $_SESSION;
        if($hasBuffer) {
            $_SESSION = $buffer;
        }
        else {
            unset($_SESSION);
        }

        return $session;
    }
}
