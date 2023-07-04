<?php

namespace SFW\Databaser;

/**
 * Database result handling.
 */
abstract class Result
{
    /**
     * Columns are returned into the array having the fieldname as the array index.
     */
    public const ASSOC = 1;

    /**
     * Columns are returned into the array having an enumerated index.
     */
    public const NUM = 2;

    /**
     * Columns are returned into the array having both a numerical index and the fieldname as the associative index.
     */
    public const BOTH = 3;

    /**
     * Fetches all result rows as an associative array, a numeric array, or both.
     */
    abstract public function fetchAll(int $mode = self::ASSOC): array;

    /**
     * Fetch the next row of a result set as an associative, a numeric array, or both.
     */
    abstract public function fetchArray(int $mode = self::BOTH): array|false;

    /**
     * Fetch the next row of a result set as an associative array.
     */
    abstract public function fetchAssoc(): array|false;

    /**
     * Fetch the next row of a result set as an object.
     */
    abstract public function fetchObject(): object|false;

    /**
     * Fetch the next row of a result set as an enumerated array.
     */
    abstract public function fetchRow(): array|false;

    /**
     * Fetch a single column from the next row of a result set.
     */
    abstract public function fetchColumn(int $column): string|float|int|null|false;

    /**
     * Fetches all rows in a particular result column as an array.
     */
    abstract public function fetchAllColumns(int $column): array;

    /**
     * Returns the number of rows in a result.
     */
    abstract public function numRows(): int|string;

    /**
     * Returns number of affected records.
     */
    abstract public function affectedRows(): int|string;
}
