<?php

/**
 * @package toolkit
 */

final class DatabaseCreate extends DatabaseStatement
{
    private $charset;
    private $collate;
    private $engine;

    public function __construct(Database $db, $table, $optimizer = null)
    {
        parent::__construct($db, 'CREATE TABLE');
        if ($optimizer === 'IF NOT EXISTS') {
            $this->unsafeAppendSQLPart('optimizer', 'IF NOT EXISTS');
        }
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    public function charset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    public function collate($collate)
    {
        $this->collate = $collate;
        return $this;
    }

    public function engine($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    protected function getOption(array $options, $key)
    {
        return (isset($options[$key]) && !empty($options[$key]) ? $options[$key] : $this->{$key});
    }

    public function fields(array $fields)
    {
        if ($this->getOpenParenthesisCount() === 0) {
            $this->appendOpenParenthesis();
        }
        $fields = implode(self::LIST_DELIMITER, General::array_map(function ($k, $field) {
            return $this->buildColumnDefinitionFromArray($k, $field);
        }, $fields));
        $this->unsafeAppendSQLPart('fields', $fields);
        return $this;
    }

    public function keys(array $keys)
    {
        $preambule = '';
        if ($this->getOpenParenthesisCount() === 0) {
            $this->appendOpenParenthesis();
        } else {
            $preambule = self::LIST_DELIMITER;
        }
        $keys = $preambule . implode(self::LIST_DELIMITER, General::array_map(function ($key, $options) {
            return $this->buildKeyDefinitionFromArray($key, $options);
        }, $keys));
        $this->unsafeAppendSQLPart('keys', $keys);
        return $this;
    }

    public function finalize()
    {
        parent::finalize();
        if ($this->engine) {
            $this->unsafeAppendSQLPart('engine', "ENGINE={$this->engine}");
        }
        if ($this->charset) {
            $this->unsafeAppendSQLPart('charset', "DEFAULT CHARSET={$this->charset}");
        }
        if ($this->collate) {
            $this->unsafeAppendSQLPart('collate', "COLLATE={$this->collate}");
        }
        return $this;
    }
}
