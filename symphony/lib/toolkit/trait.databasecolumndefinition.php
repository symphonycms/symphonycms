<?php

/**
 * @package toolkit
 */

/**
 * @since Symphony 3.0.0
 */
trait DatabaseColumnDefinition
{
    /**
     * The default charset option value for this statement
     *
     * @var string
     */
    private $charset;

    /**
     * The default collate option value for this statement
     *
     * @var string
     */
    private $collate;

    /**
     * Sets the charset to use in this table.
     *
     * @param string $charset
     *  The charset to use
     * @return DatabaseColumnDefinition
     *  The current instance
     */
    public function charset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Set the default collate for all textual columns being altered.
     *
     * @param string $collate
     *  The collate to use by default
     * @return DatabaseColumnDefinition
     *  The current instance
     */
    public function collate($collate)
    {
        $this->collate = $collate;
        return $this;
    }

    /**
     * @internal
     * This method checks if the $key index is not empty in the $options array.
     * If it is not empty, it will return its value. If is it, it will lookup a
     * member variable $key on the current instance and return its value if not empty.
     * If both storage are empty, it returns null
     *
     * @see DatabaseColumnDefinition::buildColumnDefinitionFromArray()
     * @param array $options
     * @param string|int $key.
     * @return mixed
     */
    protected function getOption(array $options, $key)
    {
        return !empty($options[$key]) ? $options[$key] : (!empty($this->{$key}) ? $this->{$key} : null);
    }

    /**
     * @internal
     * Given a field name valid field $k, this methods build a column definition
     * SQL part from an array of options. It will use the array $options to generate
     * the a complete SQL definition part, with all its possible properties.
     *
     * This method is mostly used for CREATE and ALTER statements.
     *
     * @see validateFieldName()
     * @see getOption()
     * @see DatabaseCreate
     * @see DatabaseAlter
     * @param string $k
     *  The name of the field
     * @param string|array $options
     *  All the options needed to properly create the column.
     *  The method `getOption()` is used to get the value of the field.
     *  When the value is a string, it is considered as the column's type.
     * @param string $options.type
     *  The SQL type of the column.
     * @param string $options.charset
     *  The charset to use with this column. Only used for character based columns.
     * @param string $options.collate
     *  The collate to use with this column. Only used for character based columns.
     * @param bool $options.null
     *  If the column should accept NULL. Defaults to false, i.e. NOT NULL.
     * @param string|int $options.default
     *  The default value of the column.
     * @param bool $options.signed
     *  If the column should be signed. Only used for number based columns.
     *  Defaults to false, i.e. UNSIGNED.
     * @param bool $options.auto
     *  If the column should use AUTO_INCREMENT. Only used for integer based columns.
     *  Defaults to false.
     * @return string
     *  The SQL part containing the column definition.
     * @throws DatabaseStatementException
     */
    public function buildColumnDefinitionFromArray($k, $options)
    {
        if (is_string($options)) {
            $options = ['type' => $options];
        } elseif (!is_array($options)) {
            throw new DatabaseStatementException('Field value can only be a string or an array');
        } elseif (!isset($options['type'])) {
            throw new DatabaseStatementException('Field type must be defined.');
        }

        $type = strtolower($options['type']);

        $charset = $this->getOption($options, 'charset');
        if ($charset) {
            $charset = ' CHARACTER SET ' . $charset;
        }
        $collate = $this->getOption($options, 'collate');
        if ($collate) {
            $collate = ' COLLATE ' . $collate;
        }

        $notNull = !isset($options['null']) || $options['null'] === false;
        $null = $notNull ? ' NOT NULL' : ' DEFAULT NULL';
        $default = $notNull && isset($options['default']) ?
            " DEFAULT " . ($this->asPlaceholderString($k, $options['default']) .  '_default') :
            '';
        if ($default) {
            $this->appendValues(["{$k}_default" => $options['default']]);
        }

        $unsigned = !isset($options['signed']) || $options['signed'] === false;
        $stringOptions = $charset . $collate . $null . $default;

        if (strpos($type, 'varchar') === 0 || strpos($type, 'char') === 0 || strpos($type, 'text') === 0) {
            $type .= $stringOptions;
        } elseif (strpos($type, 'enum') === 0) {
            if (isset($options['values']) && is_array($options['values'])) {
                $type .= "(" . implode(
                    self::LIST_DELIMITER,
                    General::array_map(function ($key, $value) use ($k) {
                        $key = $k . ($key + 1);
                        $this->appendValues([$key => $value]);
                        return $this->asPlaceholderString($key, $value);
                    }, $options['values'])
                ) . ")";
            }
            $type .= $stringOptions;
        } elseif (strpos($type, 'int') === 0) {
            if ($unsigned) {
                $type .= ' unsigned';
            }
            $type .= $null . $default;
            if (isset($options['auto']) && $options['auto']) {
                $type .= ' AUTO_INCREMENT';
            }
        } elseif (strpos($type, 'datetime') === 0) {
            $type .= $null . $default;
        }
        $k = $this->asTickedString($k);
        return "$k $type";
    }
}
