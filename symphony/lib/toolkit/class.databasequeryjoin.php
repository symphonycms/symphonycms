<?php

/**
 * @package toolkit
 */

/**
 * Class responsible to hold all the data needed to create a JOIN x AS y ON z clause.
 * The data needs to be encapsulated until all the operations are completed.
 * Only then is it possible to append the part in the underlying DatabaseStatement.
 */
class DatabaseQueryJoin
{
    /**
     * Reference to the underlying DatabaseQuery
     *
     * @var DatabaseQuery
     */
    private $q;

    /**
     * The requested JOIN syntax
     * @var string
     */
    private $join;

    /**
     * The name of the SQL part to add to the underlying DatabaseQuery
     * @var string
     */
    private $type;

    /**
     * The name of the alias for the joined table
     * @var string
     */
    private $alias;

    /**
     * Creates a new DatabaseQueryJoin object linked to the $q DatabaseQuery.
     *
     * @param DatabaseQuery $q
     *  The underlying DatabaseQuery, normally the object that call this function.
     * @param string $type
     *  The SQL part type to be added
     * @param string $join
     *  The requested JOIN syntax
     * @param string $alias
     *  An optional alias for the joined table
     */
    public function __construct(DatabaseQuery $q, $type, $join, $alias = null)
    {
        $this->q = $q;
        $this->join = $join;
        $this->type = $type;
        if ($alias) {
            $this->alias($alias);
        }
    }

    /**
     * Appends a AS `alias` clause.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseStatementException
     * @param string $alias
     *  The name of the alias
     * @return DatabaseQueryJoin
     *  The current instance
     */
    public function alias($alias)
    {
        if ($this->alias) {
            throw new DatabaseStatementException('DatabaseQueryJoin can not hold more than one as clause');
        }
        General::ensureType([
            'alias' => ['var' => $alias, 'type' => 'string'],
        ]);
        $alias = $this->q->asTickedString($alias);
        $this->alias = " AS $alias";
        return $this;
    }

    /**
     * Appends one an only one ON condition clause to the underlying DatabaseQuery.
     * Can only be called once in the lifetime of the object.
     * Destroys the reference to the underlying DatabaseQuery.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseQuery
     *  The underlying DatabaseQuery instance
     */
    public function on(array $conditions)
    {
        $conditions = $this->q->buildWhereClauseFromArray($conditions);
        $this->q->unsafeAppendSQLPart($this->type, "{$this->join}{$this->alias} ON $conditions");
        $q = $this->q;
        $this->q = null;
        return $q;
    }
}
