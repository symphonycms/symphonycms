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
     * Fetch a single author by its reset password token
     *
     * @param string $token
     * @return Author
     * @throws Exception
     *  If the token is not a string
     */
    public function fetchByPasswordResetToken($token)
    {
        if (!$token) {
            return null;
        }
        General::ensureType([
            'token' => ['var' => $token, 'type' => 'string'],
        ]);
        return $this->select()
            ->innerJoin('tbl_forgotpass')->alias('f')
            ->on(['a.id' => '$f.author_id'])
            ->where(['f.expiry' => ['>' => DateTimeObj::getGMT('c')]])
            ->where(['f.token' => $token])
            ->limit(1)
            ->execute()
            ->next();
    }

    /**
     * Fetch a single author by its auth token
     *
     * @param string $token
     * @return Author
     * @throws Exception
     *  If the token is not a string
     */
    public function fetchByAuthToken($token)
    {
        if (!$token) {
            return null;
        }
        General::ensureType([
            'token' => ['var' => $token, 'type' => 'string'],
        ]);
        return $this->select()
            ->where(['a.auth_token' => $token])
            ->limit(1)
            ->execute()
            ->next();
    }

    /**
     * The fetch method returns all Authors from Symphony with the option to sort
     * or limit the output. This method returns an array of Author objects.
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select() instead
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
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('AuthorManager::fetch()', 'AuthorManager::select()');
        }

        $sortby = $sortby ?: 'id';
        $sortdirection = strtoupper($sortdirection) === 'ASC' ? 'ASC' : 'DESC';

        $query = (new AuthorManager)->select();

        $orderBy = [];
        foreach (explode(',', $sortby) as $sortby) {
            $sortby = trim($sortby);
            $orderBy["a.$sortby"] = $sortdirection;
        }
        $query->orderBy($orderBy);

        if ($joins) {
            $joins = $query->replaceTablePrefix($joins);
            $query->unsafeAppendSQLPart('join', $joins);
        }
        if ($where) {
            $where = $query->replaceTablePrefix($where);
            $query->unsafe()->unsafeAppendSQLPart('where', "WHERE $where");
        }
        if ($limit) {
            $query->limit($limit);
        }
        if ($start) {
            $query->offset($start);
        }

        $authors = $query->execute()->rows();

        foreach ($authors as $author) {
            self::$_pool[$author->get('id')] = $author;
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

        $authors = (new AuthorManager)
            ->select()
            ->authors($id)
            ->execute()
            ->rows();

        foreach ($authors as $author) {
            self::$_pool[$author->get('id')] = $author;
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
        if (!isset(self::$_pool[$username])) {
            $author = (new AuthorManager)
                ->select()
                ->username($username)
                ->limit(1)
                ->execute()
                ->next();

            if (!$author) {
                return null;
            }

            self::$_pool[$username] = $author;
        }

        return self::$_pool[$username];
    }

    /**
     * Creates a new Author object.
     *
     * @return Author
     */
    public static function create()
    {
        return new Author;
    }

    /**
     * Factory method that creates a new AuthorQuery.
     *
     * @since Symphony 3.0.0
     * @param array $projection
     *  The projection to select.
     *  If no projection gets added, it defaults to `AuthorQuery::getDefaultProjection()`.
     * @return AuthorQuery
     */
    public function select(array $projection = [])
    {
        return new AuthorQuery(Symphony::Database(), $projection);
    }
}
