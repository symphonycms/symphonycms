<?php

/**
 * @package toolkit
 */

final class DatabaseQueryResult extends DatabaseStatementResult
{
    private $offset = 0;
    private $type = PDO::FETCH_ASSOC;
    private $orientation = PDO::FETCH_ORI_NEXT;

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function type($type)
    {
        if ($type !== PDO::FETCH_ASSOC && $type !== PDO::FETCH_OBJ) {
            throw new DatabaseException('Invalid fetch type');
        }
        $this->type = $type;
        return $this;
    }

    public function orientation($orientation)
    {
        if ($type !== PDO::FETCH_ORI_NEXT && $type !== PDO::FETCH_OBJ) {
            throw new DatabaseException('Invalid fetch type');
        }
        $this->type = $type;
        return $this;
    }

    public function next()
    {
        return $this->statement()->fetch(
            $this->type,
            $this->orientation,
            $this->offset
        );
    }

    public function rows()
    {
        $rows = [];
        while ($row = $this->next()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function rowCount()
    {
        return $this->statement()->rowCount();
    }

    public function column($col)
    {
        $rows = [];
        while ($row = $this->next()) {
            if ($this->type === PDO::FETCH_OBJ) {
                $rows[] = $row->{$col};
            } else {
                $rows[] = $row[$col];
            }
        }
        return $rows;
    }

    public function columnCount()
    {
        $this->statement()->columnCount();
    }

    public function var($name)
    {
        if ($row = $this->next()) {
            if ($this->type === PDO::FETCH_OBJ) {
                return $row->{$col};
            } else {
                return $row[$col];
            }
        }
        return null;
    }
}
