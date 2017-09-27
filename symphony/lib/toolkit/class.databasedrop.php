<?php

/**
 * @package toolkit
 */

final class DatabaseDrop extends DatabaseStatement
{
    public function __construct(Database $db, $table, $optimizer = null)
    {
        parent::__construct($db, 'DROP TABLE');
        if ($optimizer === 'IF EXISTS') {
            $this->unsafeAppendSQLPart('optimizer', 'IF EXISTS');
        }
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    public function table($table)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', ", $table");
        return $this;
    }
}
