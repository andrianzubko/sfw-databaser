<?php

namespace SFW\Databaser;

/**
 * Database result handling.
 */
class Result implements \IteratorAggregate
{
    /**
     * Integer type for types conversion.
     */
    protected const INT = 1;

    /**
     * Float type for types conversion.
     */
    protected const FLOAT = 2;

    /**
     * Boolean type for types conversion.
     */
    protected const BOOL = 3;

    /**
     * Json type for types conversion.
     */
    protected const JSON = 4;

    /**
     * Default mode for fetchAll method.
     */
    protected ?int $mode = null;

    /**
     * Names of columns in result rows.
     */
    protected array $colNames = [];

    /**
     * Types of columns in result rows.
     */
    protected array $colTypes = [];

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

        $rows = [];

        foreach ($this->fetchAllRows() as $row) {
            $rows[] = match ($mode) {
                \SFW\Databaser::ASSOC => array_combine($this->colNames,
                    $this->convertRow($row)
                ),
                \SFW\Databaser::OBJECT => (object) array_combine($this->colNames,
                    $this->convertRow($row)
                ),
                default => $this->convertRow($row)
            };
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

        return $this->convertRow($row);
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

        return array_combine($this->colNames, $this->convertRow($row));
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

        return (object) array_combine($this->colNames, $this->convertRow($row));
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

        if (isset($column, $this->colTypes[$i])) {
            return $this->convertColumn($column, $this->colTypes[$i]);
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

        if (isset($this->colTypes[$i])) {
            foreach ($columns as $j => $column) {
                if ($column !== null) {
                    $columns[$j] = $this->convertColumn($column, $this->colTypes[$i]);
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
     * Gets column names of result rows.
     */
    public function getColNames(): array
    {
        return $this->colNames;
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

    /**
     * Converts row values to native PHP types.
     */
    private function convertRow(array $row): array
    {
        foreach ($this->colTypes as $i => $type) {
            if ($row[$i] !== null) {
                $row[$i] = match ($type) {
                    self::INT => (int) $row[$i],
                    self::FLOAT => (float) $row[$i],
                    self::BOOL => ($row[$i] === 't'),
                    self::JSON => json_decode($row[$i], true),
                    default => $row[$i]
                };
            }
        }

        return $row;
    }

    /**
     * Converts column value to native PHP types.
     */
    private function convertColumn(mixed $column, int $type): mixed
    {
        return match ($type) {
            self::INT => (int) $column,
            self::FLOAT => (float) $column,
            self::BOOL => ($column === 't'),
            self::JSON => json_decode($column, true),
            default => $column
        };
    }
}
