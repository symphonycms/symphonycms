<?php

/**
 * @package toolkit
 */
/**
 * This class hold the data created by the execution of a FieldQuery object.
 * This class is also responsible for creating the Field object based on what's
 * retrieved from the database.
 */
class FieldQueryResult extends DatabaseQueryResult
{
    /**
     * The restriction flag, either Field::__FIELD_ALL__, Field::__TOGGLEABLE_ONLY__,
     * Field::__UNTOGGLEABLE_ONLY__, Field::__FILTERABLE_ONLY__ or Field::__UNFILTERABLE_ONLY__.
     * @var int
     */
    private $restriction = null;

    /**
     * Retrieves the the next available record and builds a Field object with it.
     *
     * @see buildField()
     * @see restrict()
     * @return Field
     *  The next available Field object.
     *  null if there are not more available records.
     */
    public function next()
    {
        $next = parent::next();
        if ($next) {
            // If this fields does not match the restriction, fetch the next one.
            if ($this->restriction && !($this->restriction === Field::__FIELD_ALL__
                || ($this->restriction === Field::__TOGGLEABLE_ONLY__ && $next->canToggle())
                || ($this->restriction === Field::__UNTOGGLEABLE_ONLY__ && !$next->canToggle())
                || ($this->restriction === Field::__FILTERABLE_ONLY__ && $next->canFilter())
                || ($this->restriction === Field::__UNFILTERABLE_ONLY__ && !$next->canFilter()))) {
                return $this->next();
            }
        }
        return $next;
    }

    /**
     * @see buildField()
     * @return Extension
     */
    protected function process($next)
    {
        return $this->buildField($next);
    }

    /**
     * Sets the restriction flag, either Field::__FIELD_ALL__, Field::__TOGGLEABLE_ONLY__,
     * Field::__UNTOGGLEABLE_ONLY__, Field::__FILTERABLE_ONLY__ or Field::__UNFILTERABLE_ONLY__.
     *
     * @param int $restriction
     *  The restriction flag
     * @return FieldQueryResult
     *  The current instance
     */
    public function restrict($restriction)
    {
        $this->restriction = General::intval($restriction);
        return $this;
    }

    /**
     * Given a $row from the database, builds a complete Field object with it.
     *
     * @param array $row
     *  One result from the database
     * @return Field
     *  The newly created Field instance, populated with all its data.
     */
    public function buildField(array $row)
    {
        if (!isset($row['id'], $row['type'])) {
            return $row;
        }

        // We already have this field in our static store
        if ($if = FieldManager::getInitializedField($row['id'])) {
            // Update its data
            $if->setArray($row);
            return $if;
        }

        // We don't have an instance of this field, so let's set one up
        $field = FieldManager::create($row['type']);
        $field->setArray($row);
        // If the field has said that's going to have associations, then go find the
        // association setting value. In future this check will be most robust with
        // an interface, but for now, this is what we've got. RE: #2082
        if ($field->canShowAssociationColumn()) {
            $field->set('show_association', SectionManager::getSectionAssociationSetting($row['id']));
        }

        // Get the context for this field from our previous queries.
        try {
            $context = Symphony::Database()
                ->select()
                ->from("tbl_fields_{$row['type']}")
                ->where(['field_id' => $row['id']])
                ->limit(1)
                ->execute()
                ->next();
            if (is_array($context)) {
                unset($context['id']);
                $field->setArray($context);
            }
        } catch (Exception $e) {
            throw new Exception(__(
                'Settings for field %s could not be found in table tbl_fields_%s.',
                array($row['id'], $row['type'])
            ), $e->getCode(), $e);
        }

        // Save in static store
        FieldManager::setInitializedField($field);

        return $field;
    }
}
