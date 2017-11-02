<?php

/**
 * @package toolkit
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
     * @throws DatabaseException
     */
    public function buildKeyDefinitionFromArray($k, $options)
    {
        if (is_string($options)) {
            $options = ['type' => $options];
        } elseif (!is_array($options)) {
            throw new DatabaseException('Key value can only be a string or an array');
        } elseif (!isset($options['type'])) {
            throw new DatabaseException('Key type must be defined.');
        }
        $type = strtolower($options['type']);
        $cols = isset($options['cols']) ? $options['cols'] : $k;
        if (!is_array($cols)) {
            $cols = [$cols];
        }
        $k = $this->asTickedString($k);
        $typeIndex = in_array($type, [
            'key', 'unique', 'primary', 'fulltext', 'index'
        ]);
        if ($typeIndex === false) {
            throw new DatabaseException("Key of type `$type` is not valid");
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
        $cols = $this->asTickedList($cols);
        return "$type $k ($cols)";
    }
}
