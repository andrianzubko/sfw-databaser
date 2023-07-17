<?php

namespace SFW\Databaser;

/**
 * MySQL result handling.
 */
class MysqlResultEmpty extends MysqlResult
{
    /**
     * No parameters in this case.
     */
    public function __construct() {}

    /**
     * Fetches all result rows as an associative array, a numeric array, or both.
     */
    public function fetchAll(int $mode = self::ASSOC): array
    {
        return [];
    }

    /**
     * Fetch the next row of a result set as an associative, a numeric array, or both.
     */
    public function fetchArray(int $mode = self::ASSOC): array|false
    {
        return false;
    }

    /**
     * Fetch the next row of a result set as an associative array.
     */
    public function fetchAssoc(): array|false
    {
        return false;
    }

    /**
     * Fetch the next row of a result set as an object.
     */
    public function fetchObject(): object|false
    {
        return false;
    }

    /**
     * Fetch the next row of a result set as an enumerated array.
     */
    public function fetchRow(): array|false
    {
        return false;
    }

    /**
     * Fetch a single column from the next row of a result set.
     */
    public function fetchColumn(int $column = 0): array|string|float|int|null|false
    {
        return false;
    }

    /**
     * Fetches all rows in a particular result column as an array.
     */
    public function fetchAllColumns(int $column = 0): array
    {
        return [];
    }

    /**
     * Returns the number of rows in a result.
     */
    public function numRows(): int|string
    {
        return 0;
    }

    /**
     * Returns number of affected records.
     */
    public function affectedRows(): int|string
    {
        return 0;
    }
}
