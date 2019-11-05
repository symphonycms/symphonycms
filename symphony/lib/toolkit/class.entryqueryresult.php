<?php

/**
 * @package toolkit
 */
/**
 * This class hold the data created by the execution of a EntryQuery object.
 * This class is also responsible for creating the Entry object based on what's
 * retrieved from the database.
 */
class EntryQueryResult extends DatabaseQueryResult
{
    /**
     * The entry schema
     * @var array
     */
    private $schema = null;

    /**
     * The sections schema
     * @var array
     */
    private $sectionsSchemas = [];

    /**
     * The table lookup cache
     * @var array
     */
    private $tablesLookup = [];

    /**
     * Creates a new EntryQueryResult object, containing its $success parameter,
     * the resulting $stm statement and the schema to use to build entries
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @param DatabaseQuery $query
     * The query that created this result
     * @param array $page
     * The pagination information, if any.
     * @param array $schema
     *  The entry schema (i.e. fields) to fetch data for when building Entry object
     */
    public function __construct($success, PDOStatement $stm, DatabaseQuery $query, array $page = [], array $schema = [])
    {
        parent::__construct($success, $stm, $query, $page);
        $this->schema = $schema;
    }

    /**
     * @see buildEntry()
     * @return Entry
     */
    protected function process($next)
    {
        return $this->buildEntry($next);
    }

    /**
     * Given a $row from the database, builds a complete Entry object with it.
     *
     * @param array $row
     *  One result from the database
     * @return Entry
     *  The newly created Entry instance, populated with all its data.
     */
    public function buildEntry(array $entry)
    {
        if (!isset($entry['id'], $entry['section_id'], $entry['creation_date'], $entry['modification_date'])) {
            return $entry;
        }

        // Create UNIX timestamp, as it has always been (Re: #2501)
        $entry['creation_date'] = DateTimeObj::get('U', $entry['creation_date']);
        $entry['modification_date'] = DateTimeObj::get('U', $entry['modification_date']);

        // Fetch schema
        $schema = $this->fetchSchemaFieldIds($entry['section_id']);

        $raw = [];
        foreach ($schema as $field_id) {
            $row = null;
            try {
                $field_id = General::intval($field_id);
                $isInTableLookup = isset($this->tablesLookup[$field_id]);
                if ($field_id < 1) {
                    // Ignore invalid field id
                    continue;
                } elseif ($isInTableLookup && !$this->tablesLookup[$field_id]) {
                    // Table does not exist, from cache
                    continue;
                }
                $this->tablesLookup[$field_id] = true;
                $row = Symphony::Database()
                    ->select()
                    ->from("tbl_entries_data_$field_id")
                    ->where(['entry_id' => $entry['id']])
                    ->orderBy(['id' => 'ASC'])
                    ->execute()
                    ->rows();

            } catch (DatabaseException $e) {
                // Table does not exist, prevent causing errors again
                $this->tablesLookup[$field_id] = false;
                continue;
            }

            if (empty($row)) {
                continue;
            }

            foreach ($row as $r) {
                unset($r['id']);
                unset($r['entry_id']);

                if (!isset($raw[$field_id])) {
                    $raw[$field_id] = $r;
                } else {
                    foreach (array_keys($r) as $key) {
                        // If this field already has been set, we need to take the existing
                        // value and make it array, adding the current value to it as well
                        // There is a special check incase the the field's value has been
                        // purposely set to null in the database.
                        if ((isset($raw[$field_id][$key])
                            || is_null($raw[$field_id][$key]))
                            && !is_array($raw[$field_id][$key])) {
                            $raw[$field_id][$key] = [
                                $raw[$field_id][$key],
                                $r[$key],
                            ];

                        // This key/value hasn't been set previously, so set it
                        } elseif (!isset($raw[$field_id][$key])) {
                            $raw[$field_id] = [$r[$key]];

                        // This key has been set and it's an array, so just append
                        // this value onto the array
                        } else {
                            $raw[$field_id][$key][] = $r[$key];
                        }
                    }
                }
            }
        }

        // Actually create the object
        $obj = new Entry;
        $obj->set('id', $entry['id']);
        $obj->set('author_id', $entry['author_id']);
        $obj->set('modification_author_id', $entry['modification_author_id']);
        $obj->set('section_id', $entry['section_id']);
        $obj->set('creation_date', DateTimeObj::get('c', $entry['creation_date']));

        if (isset($entry['modification_date'])) {
            $obj->set('modification_date', DateTimeObj::get('c', $entry['modification_date']));
        } else {
            $obj->set('modification_date', $obj->get('creation_date'));
        }

        foreach ($raw as $field_id => $data) {
            $obj->setData($field_id, $data);
        }
        return $obj;
    }

    /**
     * @internal This is simply a memoized version of FieldManager::fetchFieldIDFromElementName()
     *
     * @param int $section_id
     * @return array
     *  The array of ids corresponding to the $this->schema fields name
     */
    public function fetchSchemaFieldIds($section_id)
    {
        if (empty($this->schema)) {
            return [];
        } elseif (General::intval(current($this->schema)) > 0) {
            return $this->schema;
        }
        $schemaId = md5($section_id . serialize($this->schema));
        if (empty($this->sectionsSchemas[$schemaId])) {
            $s = FieldManager::fetchFieldIDFromElementName(
                $this->schema,
                $section_id
            );
            if (!$s) {
                $s = [];
            } else {
                $s = is_array($s) ? array_values($s) : [$s];
            }
            $this->sectionsSchemas[$schemaId] = $s;
        }
        return $this->sectionsSchemas[$schemaId];
    }
}
