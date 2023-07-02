<?php

namespace SFW\Databaser;

/**
 * MySQL result handling.
 */
class MysqlResult extends Result
{
    /**
     * Columns are returned into the array having the fieldname as the array index.
     */
    public const ASSOC = MYSQLI_ASSOC;

    /**
     * Columns are returned into the array having an enumerated index.
     */
    public const NUM = MYSQLI_NUM;

    /**
     * Columns are returned into the array having both a numerical index and the fieldname as the associative index.
     */
    public const BOTH = MYSQLI_BOTH;

    /**
     * Passing parameters to properties.
     */
    public function __construct(protected \mysqli_result $result, protected int|string $affectedRows) {}

    /**
     * Fetches all result rows as an associative array, a numeric array, or both.
     */
    public function fetchAll(int $mode = self::ASSOC): array
    {
        return $this->result->fetch_all($mode);
    }

    /**
     * Fetch the next row of a result set as an associative, a numeric array, or both.
     */
    public function fetchArray(int $mode = self::ASSOC): array|false
    {
        return $this->result->fetch_array($mode) ?? false;
    }

    /**
     * Fetch the next row of a result set as an associative array.
     */
    public function fetchAssoc(): array|false
    {
        return $this->result->fetch_assoc() ?? false;
    }

    /**
     * Fetch the next row of a result set as an object.
     */
    public function fetchObject(): object|false
    {
        return $this->result->fetch_object() ?? false;
    }

    /**
     * Fetch the next row of a result set as an enumerated array.
     */
    public function fetchRow(): array|false
    {
        return $this->result->fetch_row() ?? false;
    }

    /**
     * Fetch a single column from the next row of a result set.
     */
    public function fetchColumn(int $column): string|float|int|null|false
    {
        return $this->result->fetch_column($column);
    }

    /**
     * Returns the number of rows in a result.
     */
    public function numRows(): int|string
    {
        return $this->result->num_rows;
    }

    /**
     * Returns number of affected records.
     */
    public function affectedRows(): int|string
    {
        return $this->affectedRows;
    }
}
