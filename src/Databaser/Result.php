<?php

namespace SFW\Databaser;

/**
 * Database result handling.
 */
class Result implements \IteratorAggregate
{
    /**
     * Default mode for fetchAll method.
     */
    protected ?int $mode = null;

    /**
     * Names of columns in result rows.
     */
    protected array $colNames = [];

    /**
     * Json columns in result rows.
     */
    protected array $jsonCols = [];

    /**
     * Fetches all result rows as numeric array.
     */
    protected function fetchAllRows(): array
    {
        return [];
    }

    /**
     * Fetches all result rows as associative array, numeric array, or object.
     */
    public function fetchAll(?int $mode = null): array
    {
        $mode ??= $this->mode ?? \SFW\Databaser::ASSOC;

        $rows = $this->fetchAllRows();

        foreach ($rows as &$row) {
            foreach ($this->jsonCols as $i => $true) {
                if (isset($row[$i])) {
                    $row[$i] = json_decode($row[$i], true);
                }
            }

            if ($mode === \SFW\Databaser::ASSOC) {
                $row = array_combine($this->colNames, $row);
            } elseif ($mode === \SFW\Databaser::OBJECT) {
                $row = (object) array_combine($this->colNames, $row);
            }
        }

        return $rows;
    }

    /**
     * Fetches next result row as numeric array.
     */
    protected function fetchNextRow(): array|false
    {
        return false;
    }

    /**
     * Fetches next result row as numeric array.
     */
    public function fetchRow(): array|false
    {
        $row = $this->fetchNextRow();

        if ($row === false) {
            return false;
        }

        foreach ($this->jsonCols as $i => $true) {
            if (isset($row[$i])) {
                $row[$i] = json_decode($row[$i], true);
            }
        }

        return $row;
    }

    /**
     * Fetches next result row as associative array.
     */
    public function fetchAssoc(): array|false
    {
        $row = $this->fetchNextRow();

        if ($row === false) {
            return false;
        }

        foreach ($this->jsonCols as $i => $true) {
            if (isset($row[$i])) {
                $row[$i] = json_decode($row[$i], true);
            }
        }

        return array_combine($this->colNames, $row);
    }

    /**
     * Fetches next result row as object.
     */
    public function fetchObject(): object|false
    {
        $row = $this->fetchNextRow();

        if ($row === false) {
            return false;
        }

        foreach ($this->jsonCols as $i => $true) {
            if (isset($row[$i])) {
                $row[$i] = json_decode($row[$i], true);
            }
        }

        return (object) array_combine($this->colNames, $row);
    }

    /**
     * Fetches next result row column.
     */
    protected function fetchNextRowColumn(int $i): mixed
    {
        return false;
    }

    /**
     * Fetches next result row column.
     */
    public function fetchColumn(int $i = 0): mixed
    {
        $column = $this->fetchNextRowColumn($i);

        if ($column === false) {
            return false;
        }

        if (isset($column, $this->jsonCols[$i])) {
            return json_decode($column, true);
        }

        return $column;
    }

    /**
     * Fetches all result rows columns.
     */
    protected function fetchAllRowsColumns(int $i): array
    {
        return [];
    }

    /**
     * Fetches all result rows columns.
     */
    public function fetchAllColumns(int $i = 0): array
    {
        $columns = $this->fetchAllRowsColumns($i);

        if (isset($this->jsonCols[$i])) {
            foreach ($columns as &$column) {
                if (isset($column)) {
                    $column = json_decode($column, true);
                }
            }
        }

        return $columns;
    }

    /**
     * Moves internal result pointer.
     */
    public function seek(int $i = 0): self
    {
        return $this;
    }

    /**
     * Gets number of affected rows.
     */
    public function affectedRows(): int|string
    {
        return 0;
    }

    /**
     * Gets the number of result rows.
     */
    public function numRows(): int|string
    {
        return 0;
    }

    /**
     * Gets iterator with result rows.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fetchAll());
    }

    /**
     * Sets default mode for fetchAll method.
     */
    public function setMode(?int $mode): self
    {
        $this->mode = $mode;

        return $this;
    }
}
