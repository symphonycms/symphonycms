<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an input Field.
 * @see FieldInput
 * @since Symphony 3.0.0
 */
class EntryQueryInputAdapter extends EntryQueryFieldAdapter
{
    /**
     * @internal Returns the columns to use when filtering
     *
     * @return array
     */
    public function getFilterColumns()
    {
        return ['value', 'handle'];
    }
}
