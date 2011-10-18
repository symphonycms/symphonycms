<?php
/**
 * @package toolkit
 */
/**
 * The `AuthorManager` class is responsible for managing all Author objects
 * in Symphony. Unlike other Manager objects, Authors are stored in the
 * database in `tbl_authors` and not on the file system. CRUD methods are
 * implemented to allow Authors to be created (add), read (fetch), updated
 * (edit) and deleted (delete).
 */

class AuthorManager
{
    /**
     * An array of all the objects that the Manager is responsible for.
     * Defaults to an empty array.
     * @var array
     */
    protected static $_pool = array();

    /**
     * Given an associative array of fields, insert them into the database
     * returning the resulting Author ID if successful, or false if there
     * was an error
     *
     * @param array $fields
     *  Associative array of field names => values for the Author object
     * @throws DatabaseException
     * @return integer
     *  Returns an Author ID of the created Author on success, 0 otherwise.
     */
    public static function add(array $fields)
    {
        $inserted = Symphony::Database()
            ->insert('tbl_authors')
            ->values($fields)
            ->execute()
            ->success();

        return $inserted ? Symphony::Database()->getInsertID() : 0;
    }

    /**
     * Given an Author ID and associative array of fields, update an existing Author
     * row in the `tbl_authors` database table. Returns boolean for success/failure
     *
     * @param integer $id
     *  The ID of the Author that should be updated
     * @param array $fields
     *  Associative array of field names => values for the Author object
     *  This array does need to contain every value for the author object, it
     *  can just be the changed values.
     * @throws DatabaseException
     * @return boolean
     */
    public static function edit($id, array $fields)
    {
        return Symphony::Database()
            ->update('tbl_authors')
            ->set($fields)
            ->where(['id' => (int)$id])
            ->execute()
            ->success();
    }

    /**
     * Given an Author ID, delete an Author from Symphony.
     *
     * @param integer $id
     *  The ID of the Author that should be deleted
     * @throws DatabaseException
     * @return boolean
     */
    public static function delete($id)
    {
        return Symphony::Database()
            ->delete('tbl_authors')
            ->where(['id' => (int)$id])
            ->execute()
            ->success();
    }

    /**
     * The fetch method returns all Authors from Symphony with the option to sort
     * or limit the output. This method returns an array of Author objects.
     *
     * @param string $sortby
     *  The field to sort the authors by, defaults to 'id'
     * @param string $sortdirection
     *  Available values of ASC (Ascending) or DESC (Descending), which refer to the
     *  sort order for the query. Defaults to ASC (Ascending)
     * @param integer $limit
     *  The number of rows to return
     * @param integer $start
     *  The offset start point for limiting, maps to the LIMIT {x}, {y} MySQL functionality
     * @param string $where
     *  Any custom WHERE clauses. The `tbl_authors` alias is `a`
     * @param string $joins
     *  Any custom JOIN's
     * @throws DatabaseException
     * @return array
     *  An array of Author objects. If no Authors are found, an empty array is returned.
     */
    public static function fetch($sortby = 'id', $sortdirection = 'ASC', $limit = null, $start = null, $where = null, $joins = null)
    {
        $sortby = $sortby ?: 'id';
        $sortdirection = strtoupper($sortdirection) === 'ASC' ? 'ASC' : 'DESC';

        $sql = Symphony::Database()
            ->select(['a.*'])
            ->from('tbl_authors', 'a');

        $orderBy = [];
        foreach (explode(',', $sortby) as $sortby) {
            $sortby = trim($sortby);
            $orderBy["a.$sortby"] = $sortdirection;
        }
        $sql->orderBy($orderBy);

        if ($joins) {
            $joins = $sql->replaceTablePrefix($joins);
            $sql->unsafeAppendSQLPart('join', $joins);
        }
        if ($where) {
            $where = $sql->replaceTablePrefix($where);
            $sql->unsafe()->unsafeAppendSQLPart('where', "WHERE $where");
        }
        if ($limit) {
            $sql->limit($limit);
        }
        if ($start) {
            $sql->offset($start);
        }

        $records = $sql->execute();
        $authors = [];

        if ($records->rowCount() === 0) {
            return $authors;
        }

        while ($row = $records->next()) {
            $author = new Author;

            foreach ($row as $field => $val) {
                $author->set($field, $val);
            }

            self::$_pool[$author->get('id')] = $author;
            $authors[] = $author;
        }

        return $authors;
    }

    /**
     * Returns Author's that match the provided ID's with the option to
     * sort or limit the output. This function will search the
     * `AuthorManager::$_pool` for Authors first before querying `tbl_authors`
     *
     * @param integer|array $id
     *  A single ID or an array of ID's
     * @throws DatabaseException
     * @return mixed
     *  If `$id` is an integer, the result will be an Author object,
     *  otherwise an array of Author objects will be returned. If no
     *  Authors are found, or no `$id` is given, `null` is returned.
     */
    public static function fetchByID($id)
    {
        $return_single = false;

        if (is_null($id)) {
            return null;
        }

        if (!is_array($id)) {
            $return_single = true;
            $id = array((int)$id);
        }

        if (empty($id)) {
            return null;
        }

        // Get all the Author ID's that are already in `self::$_pool`
        $authors = array();
        $pooled_authors = array_intersect($id, array_keys(self::$_pool));

        foreach ($pooled_authors as $pool_author) {
            $authors[] = self::$_pool[$pool_author];
        }

        // Get all the Author ID's that are not already stored in `self::$_pool`
        $id = array_diff($id, array_keys(self::$_pool));
        $id = array_filter($id);

        if (empty($id)) {
            return ($return_single ? $authors[0] : $authors);
        }

        $records = Symphony::Database()
            ->select()
            ->from('tbl_authors')
            ->where(['id' => ['in' => $id]])
            ->execute();

        if ($records->rowCount() === 0) {
            return ($return_single ? $authors[0] : $authors);
        }

        while ($row = $records->next()) {
            $author = new Author;

            foreach ($row as $field => $val) {
                $author->set($field, $val);
            }

            self::$_pool[$author->get('id')] = $author;
            $authors[] = $author;
        }

        return ($return_single ? $authors[0] : $authors);
    }

    /**
     * Returns an Author by Username. This function will search the
     * `AuthorManager::$_pool` for Authors first before querying `tbl_authors`
     *
     * @param string $username
     *  The Author's username
     * @return Author|null
     *  If an Author is found, an Author object is returned, otherwise null.
     */
    public static function fetchByUsername($username)
    {
        if(!isset(self::$_pool[$username])) {
            $records = Symphony::Database()
                ->select()
                ->from('tbl_authors')
                ->where(['username' => $username])
                ->limit(1)
                ->execute();

            if ($records->rowCount() === 0) {
                return null;
            }

            $records = $records->next();
            $author = new Author;

            foreach ($records as $field => $val) {
                $author->set($field, $val);
            }

            self::$_pool[$username] = $author;
        }

        return self::$_pool[$username];
    }

    /**
     * This function will allow an Author to sign into Symphony by using their
     * authentication token as well as username/password.
     *
     * @param integer $author_id
     *  The Author ID to allow to use their authentication token.
     * @throws DatabaseException
     * @return boolean
     */
    public static function activateAuthToken($author_id)
    {
        if (!is_int($author_id)) {
            return false;
        }

        return Symphony::Database()
            ->update('tbl_authors')
            ->set(['auth_token_active' => 'yes'])
            ->where(['id' => $author_id])
            ->execute()
            ->success();
    }

    /**
     * This function will remove the ability for an Author to sign into Symphony
     * by using their authentication token
     *
     * @param integer $author_id
     *  The Author ID to allow to use their authentication token.
     * @throws DatabaseException
     * @return boolean
     */
    public static function deactivateAuthToken($author_id)
    {
        if (!is_int($author_id)) {
            return false;
        }

        return Symphony::Database()
            ->update('tbl_authors')
            ->set(['auth_token_active' => 'no'])
            ->where(['id' => $author_id])
            ->execute()
            ->success();
    }
}
