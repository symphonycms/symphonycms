<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of UPDATE statements.
 */
final class DatabaseUpdate extends DatabaseStatement
{
    use DatabaseWhereDefinition;

    /**
     * By default, disable DatabaseWhereDefinition's ability to transform == null syntax.
     * `set()` needs it disable, `where()` needs it enabled.
     *
     * @var boolean
     */
    private $enableIsNullSyntax = false;

    /**
     * Creates a new DatabaseUpdate statement on table $table.
     *
     * @see Database::update()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'UPDATE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    /**
     * Returns the parts statement structure for this specialized statement.
     *
     * @see DatabaseStatement::getStatementStructure()
     * @return array
     */
    protected function getStatementStructure()
    {
        return [
            'statement',
            'table',
            'values',
            'where',
        ];
    }

    /**
     * Gets the proper separator string for the given $type SQL part type, when
     * generating a formatted SQL statement.
     *
     * @see DatabaseStatement::getSeparatorForPartType()
     * @param string $type
     *  The SQL part type.
     * @return string
     *  The string to use to separate the formatted SQL parts.
     */
    public function getSeparatorForPartType($type)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'string'],
        ]);
        if (in_array($type, ['values', 'where'])) {
            return self::FORMATTED_PART_DELIMITER;
        }
        return self::STATEMENTS_DELIMITER;
    }

    /**
     * Appends one and only one SET clause, with one or multiple values.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $values
     *  The values to set. Array keys are used as column names and values are substituted
     *  by SQL parameters.
     * @return DatabaseUpdate
     *  The current instance
     */
    public function set(array $values)
    {
        $this->enableIsNullSyntax = false;
        $v = $this->buildWhereClauseFromArray([self::VALUES_DELIMITER => $values]);
        $this->unsafeAppendSQLPart('values', "SET $v");
        return $this;
    }

    /**
     * Appends one or multiple WHERE clauses.
     * Calling this method multiple times will join the WHERE clauses with a AND.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseUpdate
     *  The current instance
     */
    public function where(array $conditions)
    {
        $this->enableIsNullSyntax = true;
        $op = $this->containsSQLParts('where') ? 'AND' : 'WHERE';
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "$op $where");
        return $this;
    }
}
