<?php
/**
 * @package toolkit
 */
/**
 * The Author class represents a Symphony Author object. Authors are
 * the backend users in Symphony.
 */
class Author
{
    /**
     * An associative array of information relating to this author where
     * the keys map directly to the `tbl_authors` columns.
     * @var array
     */
    private $_fields = array();

    /**
     * An array of all the sections an author can have access to. Defaults
     * to null. This is currently unused by Symphony.
     * @var array
     */
    private $_accessSections = null;

    /**
     * Stores a key=>value pair into the Author object's `$this->_fields` array.
     *
     * @param string $field
     *  Maps directly to a column in the `tbl_authors` table.
     * @param string $value
     *  The value for the given $field
     */
    public function set($field, $value)
    {
        $this->_fields[trim($field)] = trim($value);
    }

    /**
     * Retrieves the value from the Author object by field from `$this->_fields`
     * array. If field is omitted, all fields are returned.
     *
     * @param string $field
     *  Maps directly to a column in the `tbl_authors` table. Defaults to null
     * @return mixed
     *  If the field is not set or is empty, returns null.
     *  If the field is not provided, returns the `$this->_fields` array
     *  Otherwise returns a string.
     */
    public function get($field = null)
    {
        if (is_null($field)) {
            return $this->_fields;
        }

        if (!isset($this->_fields[$field]) || $this->_fields[$field] == '') {
            return null;
        }

        return $this->_fields[$field];
    }

    /**
     * Given a field, remove it from `$this->_fields`
     *
     * @since Symphony 2.2.1
     * @param string $field
     *  Maps directly to a column in the `tbl_authors` table. Defaults to null
     */
    public function remove($field = null)
    {
        if (!is_null($field)) {
            return;
        }

        unset($this->_fields[$field]);
    }

    /**
     * Returns boolean if the current Author is the original creator
     * of this Symphony installation.
     *
     * @return boolean
     */
    public function isPrimaryAccount()
    {
        return ($this->get('primary') === 'yes');
    }

    /**
     * Returns boolean if the current Author is of the developer
     * user type.
     *
     * @return boolean
     */
    public function isDeveloper()
    {
        return ($this->get('user_type') == 'developer');
    }

    /**
     * Returns boolean if the current Author is of the manager
     * user type.
     *
     * @since  2.3.3
     * @return boolean
     */
    public function isManager()
    {
        return ($this->get('user_type') == 'manager');
    }

    /**
     * Returns boolean if the current Author is of the author
     * user type.
     *
     * @since  2.4
     * @return boolean
     */
    public function isAuthor()
    {
        return ($this->get('user_type') == 'author');
    }

    /**
     * Returns boolean if the current Author's authentication token
     * is active or not.
     *
     * @return boolean
     */
    public function isTokenActive()
    {
        return ($this->get('auth_token_active') === 'yes' ? true : false);
    }

    /**
     * A convenience method that returns an Authors full name
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->get('first_name') . ' ' . $this->get('last_name');
    }

    /**
     * Creates an author token using the `Cryptography::hash` function and the
     * current Author's username and password. The default hash function
     * is SHA1
     *
     * @see toolkit.Cryptography#hash()
     * @see toolkit.General#substrmin()
     *
     * @return string
     */
    public function createAuthToken()
    {
        return General::substrmin(SHA1::hash($this->get('username') . $this->get('password')), 8);
    }

    /**
     * Prior to saving an Author object, the validate function ensures that
     * the values in `$this->_fields` array are correct. As of Symphony 2.3
     * Authors must have unique username AND email address. This function returns
     * boolean, with an `$errors` array provided by reference to the callee
     * function.
     *
     * @param array $errors
     * @return boolean
     */
    public function validate(&$errors)
    {
        $errors = array();
        $current_author = null;

        if (is_null($this->get('first_name'))) {
            $errors['first_name'] = __('First name is required');
        }

        if (is_null($this->get('last_name'))) {
            $errors['last_name'] = __('Last name is required');
        }

        if ($this->get('id')) {
            $current_author = Symphony::Database()->fetchRow(0, sprintf(
                "SELECT `email`, `username`
                FROM `tbl_authors`
                WHERE `id` = %d",
                $this->get('id')
            ));
        }

        // Check that Email is provided
        if (is_null($this->get('email'))) {
            $errors['email'] = __('E-mail address is required');

            // Check Email is valid
        } elseif (!General::validateString($this->get('email'), $validators['email'])) {
            $errors['email'] = __('E-mail address entered is invalid');

            // Check that if an existing Author changes their email address that
            // it is not already used by another Author
        } elseif ($this->get('id')) {
            if (
                $current_author['email'] !== $this->get('email') &&
                Symphony::Database()->fetchVar('count', 0, sprintf(
                    "SELECT COUNT(`id`) as `count`
                    FROM `tbl_authors`
                    WHERE `email` = '%s'",
                    General::sanitize($this->get('email'))
                )) != 0
            ) {
                $errors['email'] = __('E-mail address is already taken');
            }

            // Check that Email is not in use by another Author
        } elseif (Symphony::Database()->fetchVar('id', 0, sprintf(
            "SELECT `id`
            FROM `tbl_authors`
            WHERE `email` = '%s'
            LIMIT 1",
            General::sanitize($this->get('email'))
        ))) {
            $errors['email'] = __('E-mail address is already taken');
        }

        // Check the username exists
        if (is_null($this->get('username'))) {
            $errors['username'] = __('Username is required');

        // Check that if it's an existing Author that the username is not already
        // in use by another Author if they are trying to change it.
        } elseif ($this->get('id')) {
            if (
                $current_author['username'] !== $this->get('username') &&
                Symphony::Database()->fetchVar('count', 0, sprintf(
                    "SELECT COUNT(`id`) as `count`
                    FROM `tbl_authors`
                    WHERE `username` = '%s'",
                    General::sanitize($this->get('username'))
                )) != 0
            ) {
                $errors['username'] = __('Username is already taken');
            }

            // Check that the username is unique
        } elseif (Symphony::Database()->fetchVar('id', 0, sprintf(
            "SELECT `id`
            FROM `tbl_authors`
            WHERE `username` = '%s'
            LIMIT 1",
            General::sanitize($this->get('username'))
        ))) {
            $errors['username'] = __('Username is already taken');
        }

        if (is_null($this->get('password'))) {
            $errors['password'] = __('Password is required');
        }

        return (empty($errors) ? true : false);
    }

    /**
     * This is the insert method for the Author. This takes the current
     * `$this->_fields` values and adds them to the database using either the
     * `AuthorManager::edit` or `AuthorManager::add` functions. An
     * existing user is determined by if an ID is already set.
     *
     * @see toolkit.AuthorManager#add()
     * @see toolkit.AuthorManager#edit()
     * @return integer|boolean
     *  When a new Author is added or updated, an integer of the Author ID
     *  will be returned, otherwise false will be returned for a failed update.
     */
    public function commit()
    {
        if (!is_null($this->get('id'))) {
            $id = $this->get('id');
            $this->remove('id');

            if (AuthorManager::edit($id, $this->get())) {
                $this->set('id', $id);
                return $id;
            } else {
                return false;
            }
        } else {
            return AuthorManager::add($this->get());
        }
    }
}
