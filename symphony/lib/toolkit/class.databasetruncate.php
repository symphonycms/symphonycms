<?php

/**
 * @package toolkit
 */

final class DatabaseTruncate extends DatabaseStatement
{
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'TRUNCATE TABLE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }
}
