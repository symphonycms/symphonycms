<?php

/**
 * @package toolkit
 */

final class DatabaseOptimize extends DatabaseStatement
{
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'OPTIMIZE TABLE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }
}
