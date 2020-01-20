<?php

/**
 * @package core
 */

 /**
  * The ArraySerializer class serialize arrays of array as valid php code, formatted
  * with our specific format, which adds comments between keys.
  * It also supports creating a valid PHP file with the serialized array declared as a variable.
  *
  * @since Symphony 3.0.0
  */
class ArraySerializer
{
    /**
     * The string representing the tab characters used to serialize the configuration
     * @var string
     */
    const TAB = '    ';

    /**
     * The storage to the array to serialize
     * @var array[]
     */
    private $array = [];

    /**
     * Creates a new ArraySerializer, with the specified array to serialize
     * @param array $array
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * @see asPHPFile()
     * @return string
     */
    public function __toString()
    {
        return $this->asPHPFile();
    }

    /**
     * Wraps the serialize() call with proper php tag an variable name.
     * @param string $variableName
     *  The name of the variable that will hold the serialized array
     * @return string
     */
    public function asPHPFile($variableName = 'settings')
    {
        $tab = static::TAB;
        $eol = PHP_EOL;
        $string = $this->serialize();

        return "<?php$eol$tab\$$variableName = $string;$eol";
    }

    /**
     * Serializes the array as a string of valid php code
     * @return string
     */
    public function serialize()
    {
        $tab = static::TAB;
        $string = 'array(';

        foreach ($this->array as $group => $data) {
            $string .= str_repeat(PHP_EOL, 3) . "$tab$tab###### ".strtoupper($group)." ######";
            $group = addslashes($group);
            $string .= PHP_EOL . "$tab$tab'$group' => ";

            $string .= $this->serializeArray($data, 3, $tab);

            $string .= ",";
            $string .= PHP_EOL . "$tab$tab########";
        }
        $string .= PHP_EOL . "$tab)";

        return $string;
    }

    /**
     * The `serializeArray` function will properly format and indent multidimensional
     * arrays using recursivity.
     *
     * `serialize()` calls `serializeArray` to use the recursive condition.
     * The keys (int) in array won't have apostrophe.
     * Array without multidimensional array will be output with normal indentation.
     * @return string
     *  A string that contains a array representation of the '$data parameter'.
     * @param array $arr
     *  A array of properties to serialize.
     * @param integer $indentation
     *  The current level of indentation.
     * @param string $tab
     *  A horizontal tab
     */
    protected function serializeArray(array $arr, $indentation = 0, $tab = self::TAB)
    {
        $tabs = '';
        $closeTabs = '';
        for ($i = 0; $i < $indentation; $i++) {
            $tabs .= $tab;
            if ($i < $indentation - 1) {
                $closeTabs .= $tab;
            }
        }
        $string = 'array(';
        foreach ($arr as $key => $value) {
            $key = addslashes($key);
            $string .= (is_numeric($key) ? PHP_EOL . "$tabs $key => " : PHP_EOL . "$tabs'$key' => ");
            if (is_array($value)) {
                if (empty($value)) {
                    $string .= 'array()';
                } else {
                    $string .= $this->serializeArray($value, $indentation + 1, $tab);
                }
            } else {
                $string .= (General::strlen($value) > 0 ? var_export($value, true) : 'null');
            }
            $string .= ",";
        }
        $string .=  PHP_EOL . "$closeTabs)";
        return $string;
    }

}
