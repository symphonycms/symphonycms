<?php

/**
 * @package toolkit
 */

/**
 * @since Symphony 3.0.0
 */
trait DatabaseKeyDefinition
{
    /**
     * @internal
     * Given a field name valid field $k, this methods build a key definition
     * SQL part from an array of options. It will use the array $options to generate
     * the a complete SQL definition part, with all its possible properties.
     *
     * @param string $k
     *  The name of the key
     * @param string|array $options
     *  All the options needed to properly create the key.
     *  When the value is a string, it is considered as the key's type.
     * @param string $options.type
     *  The SQL type of the key.
     *  Valid values are: 'key', 'unique', 'primary', 'fulltext', 'index'
     * @param string|array $options.cols
     *  The list of columns to be included in the key.
     *  If omitted, the name of the key be added as the only column in the key.
     * @return string
     *  The SQL part containing the key definition.
     * @throws DatabaseStatementException
     */
    public function buildKeyDefinitionFromArray($k, $options)
    {
        if (is_string($options)) {
            $options = ['type' => $options];
        } elseif (!is_array($options)) {
            throw new DatabaseStatementException('Key value can only be a string or an array');
        } elseif (!isset($options['type'])) {
            throw new DatabaseStatementException('Key type must be defined.');
        }
        $type = strtolower($options['type']);
        $k = $this->asTickedString($k);
        $cols = isset($options['cols']) ? $options['cols'] : $k;
        $typeIndex = in_array($type, [
            'key', 'unique', 'primary', 'fulltext', 'index'
        ]);
        if ($typeIndex === false) {
            throw new DatabaseStatementException("Key of type `$type` is not valid");
        }
        switch ($type) {
            case 'unique':
                $type = strtoupper($type) . ' KEY';
                break;
            case 'primary':
                // Use the key name as the KEY keyword
                // since the primary key does not have a name
                $k = 'KEY';
                // fall through
            default:
                $type = strtoupper($type);
                break;
        }

        // Format columns including size
        $colsFormatted = [];

        if (!is_array($cols)) {
            $cols = [$cols];
        }

        foreach ($cols as $key => $value) {
            // No Size
            if (General::intval($key) !== -1) {
                $colsFormatted[] = $this->asTickedString($value);
            // Size
            } else {
                $colsFormatted[] = $this->asTickedString($key) . '(' . $value . ')';
            }
        }
        $colsFormatted = implode(self::LIST_DELIMITER, $colsFormatted);

        return "$type $k ($colsFormatted)";
    }
}
