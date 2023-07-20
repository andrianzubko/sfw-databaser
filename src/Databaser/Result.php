<?php

namespace SFW\Databaser;

/**
 * Database result handling.
 */
abstract class Result implements \IteratorAggregate
{
    /**
     * Result rows.
     */
    protected array $rows;

    /**
     * Names of columns in result rows.
     */
    protected array $names = [];

    /**
     * Internal result pointer.
     */
    protected int $i = 0;

    /**
     * Fetches all result rows as an associative array (default), a numeric array, or object.
     */
    public function fetchAll(bool $num = false, bool $assoc = false, bool $object = false): array
    {
        $rows = [];

        foreach ($this->rows as $row) {
            if ($num) {
                $rows[] = $row;
            } elseif ($object) {
                $rows[] = (object) array_combine($this->names, $row);
            } else {
                $rows[] = array_combine($this->names, $row);
            }
        }

        return $rows;
    }

    /**
     * Fetch the next row of a result set as an object.
     */
    public function fetchObject(): object|false
    {
        $row = $this->rows[$this->i++] ?? false;

        return $row === false ? false : (object) array_combine($this->names, $row);
    }

    /**
     * Fetch the next row of a result set as an associative array.
     */
    public function fetchAssoc(): array|false
    {
        $row = $this->rows[$this->i++] ?? false;

        return $row === false ? false : array_combine($this->names, $row);
    }

    /**
     * Fetch the next row of a result set as an numeric array.
     */
    public function fetchRow(): array|false
    {
        return $this->rows[$this->i++] ?? false;
    }

    /**
     * Fetch a single column from the next row of a result set.
     */
    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->rows[$this->i++] ?? false;

        return $row === false ? false : $row[$column] ?? false;
    }

    /**
     * Move internal result pointer.
     */
    public function seek(int $i = 0): void
    {
        $this->i = $i;
    }

    /**
     * Returns number of affected rows.
     */
    abstract public function affectedRows(): int|string;

    /**
     * Returns the number of rows in a result.
     */
    public function numRows(): int|string
    {
        return count($this->rows);
    }

    /**
     * Gets result set iterator.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fetchAll());
    }
}
