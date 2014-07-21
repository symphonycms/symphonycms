<?php
/**
 * @package  core
 */

if (!interface_exists('SessionHandlerInterface')) {
    interface SessionHandlerInterface
    {
        public function close();
        public function destroy($session_id);
        public function gc($maxlifetime);
        public function open($save_path, $name);
        public function read($session_id);
        public function write($session_id, $session_data);
    }
}

/**
 * Database Session Handler
 * Expects the database to be wrapped by Respect\Relational
 */
class DatabaseSessionHandler implements SessionHandlerInterface
{
    /**
     * Instance of Database
     * @var Redis
     */
    protected $database;

    /**
     * Stored settings
     * @var array
     */
    protected $settings;

    /**
     * Array of data statuses
     * @var array
     */
    protected $statuses;

    /**
     * Constructor
     * @param array $settings
     */
    public function __construct($database, array $settings = array())
    {
        $this->database = $database;

        $this->settings = array_merge([
            'session_name' => 'symphony_session',
            'session_lifetime' => ini_get('session.gc_maxlifetime'),
        ], $settings);

        if (is_string($this->settings['session_lifetime'])) {
            $this->settings['session_lifetime'] = intval($this->settings['session_lifetime']);
        }

        $this->statuses = array();
    }

    /**
     * Allows the Session to close without any further logic. Acts as a
     * destructor function for the Session.
     *
     * @return boolean
     *  Always returns true
     */
    public function close()
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
    public function destroy($session_id)
    {
        $key = $this->key($session_id);

        return $this->database->query(sprintf(
            "DELETE
            FROM `tbl_sessions`
            WHERE `session` = '%s'",
            $this->database->cleanValue($session_id)
        ));
    }

    /**
     * The garbage collector, which removes all empty Sessions, or any
     * Sessions that have expired. This has a chance of firing based
     * on the `gc_probability`/`gc_divisor` which is set in the Symphony configuration.
     *
     * @param integer $maxlifetime
     *  The max session lifetime.
     * @throws DatabaseException
     * @return boolean
     *  True on Session deletion, false if an error occurs
     */
    public function gc($maxlifetime)
    {
        return $this->database->query(sprintf(
            "DELETE
            FROM `tbl_sessions`
            WHERE `session_expires` <= '%s'",
            $this->database->cleanValue(time() + $maxlifetime)
        ));
    }

    /**
     * Allows the Session to open without any further logic.
     *
     * @return boolean
     *  Always returns true
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Given a session's ID, return it's row from `tbl_sessions`
     *
     * @param string $session_id
     *  The identifier for the Session to fetch
     * @return string
     *  The serialised session data
     */
    public function read($session_id)
    {
        $key = $this->key($session_id);

        $session_data = $this->database->fetchVar(
            'session_data',
            0,
            sprintf(
                "SELECT `session_data`
                FROM `tbl_sessions`
                WHERE `session` = '%s'
                LIMIT 1",
                $this->database->cleanValue($session_id)
            )
        );

        if (is_null($session_data)) {
            return '';
        }

        $this->statuses[$key] = md5($session_data);

        return $session_data;
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
     *  The Session information, usually a serialized object of data.
     * @throws DatabaseException
     * @return boolean
     *  True if the Session information was saved successfully, false otherwise
     */
    public function write($session_id, $session_data)
    {
        $key = $this->key($session_id);

        if (!isset($this->statuses[$key]) || (isset($this->statuses[$key]) && $this->statuses[$key] != md5($session_data))) {
            $expires = (time() + $this->settings['session_lifetime']); // Recored the actual second it expires from the epoch

            $data = array(
                'session' => $key,
                'session_expires' => $expires,
                'session_data' => $session_data
            );

            return $this->database->insert($data, 'tbl_sessions', true);
        }

        return false;
    }

    /**
     * Generate a Redis storage key
     * @param  string $session_id
     * @return string
     */
    protected function key($session_id)
    {
        return sprintf(
            "%s:%s",
            $this->settings['session_name'],
            $session_id
        );
    }
}
