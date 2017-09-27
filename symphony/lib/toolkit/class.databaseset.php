<?php

/**
 * @package toolkit
 */

final class DatabaseSet extends DatabaseStatement
{
    public function __construct(Database $db, $variable)
    {
        General::ensureType([
            'variable' => ['var' => $variable, 'type' => 'string'],
        ]);
        parent::__construct($db, 'SET');
        // TODO: Escape better
        $this->unsafeAppendSQLPart('variable', $variable);
    }

    public function value($value)
    {
        General::ensureType([
            'value' => ['var' => $value, 'type' => 'string'],
        ]);
        $this->unsafeAppendSQLPart('value', "= :value");
        $this->appendValues(['value' => $value]);
        return $this;
    }
}
