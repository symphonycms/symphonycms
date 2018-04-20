<?php

/**
 * @package toolkit
 */
/**
 * Specialized DatabaseQuery that facilitate creation of queries on the authors table.
 */
class AuthorQuery extends DatabaseQuery
{
    /**
     * Creates a new AuthorQuery statement on table `tbl_authors` with an optional projection.
     * The table is aliased to `a`.
     *
     * @see AuthorManager::select()
     * @see AuthorManager::selectCount()
     * @param Database $db
     *  The underlying database connection
     * @param array $projection
     *  The columns names for include in the projection.
     *  Defaults to an empty projection.
     */
    public function __construct(Database $db, array $projection = [])
    {
        parent::__construct($db, $projection);
        $this->from('tbl_authors')->alias('a');
    }

    /**
     * Adds a WHERE clause on the author id.
     *
     * @param int $author_id
     *  The author id for which to look for
     * @return AuthorQuery
     *  The current instance
     */
    public function author($author_id)
    {
        return $this->where(['a.id' => General::intval($author_id)]);
    }

    /**
     * Adds a WHERE clause on the author id.
     *
     * @param int $author_ids
     *  The author ids for which to look for
     * @return AuthorQuery
     *  The current instance
     */
    public function authors($author_ids)
    {
        return $this->where(['a.id' => ['in' => array_map(['General', 'intval'], $author_ids)]]);
    }

    /**
     * Adds a WHERE clause on the author username.
     *
     * @param string $username
     *  The author username to fetch
     * @return AuthorQuery
     *  The current instance
     */
    public function username($username)
    {
        return $this->where(['a.username' => $username]);
    }

    /**
     * Adds a WHERE clause on the author email.
     *
     * @param string $email
     *  The author email to fetch
     * @return AuthorQuery
     *  The current instance
     */
    public function email($email)
    {
        return $this->where(['a.email' => $email]);
    }

    /**
     * Appends a ORDER BY clause using the $field parameter.
     *
     * @param string $field
     *  The field to order by with
     * @param string $direction
     *  The default direction to use.
     *  Defaults to ASC.
     * @return AuthorQuery
     *  The current instance
     */
    public function sort($field, $direction = 'ASC')
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
            'direction' => ['var' => $direction, 'type' => 'string'],
        ]);
        return $this->orderBy(["a.$field" => $direction]);
    }

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * Appends any remaining part of the statement.
     * If no sort is specified, it will default to the sort order.
     *
     * @see DatabaseStatement::execute()
     * @return AuthorQuery
     *  The current instance
     */
    public function finalize()
    {
        if (!$this->containsSQLParts('order by')) {
            $this->sort('id');
        }
        return $this;
    }

    /**
     * Creates a specialized version of DatabaseQueryResult to hold
     * result from the current AuthorQuery.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return AuthorQueryResult
     *  The wrapped result
     */
    public function results($success, PDOStatement $stm)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        return new AuthorQueryResult($success, $stm);
    }
}
