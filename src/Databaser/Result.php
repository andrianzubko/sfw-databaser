<?php

namespace SFW\Databaser;

/**
 * Database result handling.
 */
abstract class Result
{
    /**
     * Columns are returned into the array having the field-name as the array index.
     */
    public const ASSOC = 1;

    /**
     * Columns are returned into the array having an enumerated index.
     */
    public const NUM = 2;

    /**
     * Columns are returned into the array having both a numerical index and the field-name as the associative index.
     */
    public const BOTH = 3;

    /**
     * Json fields to decode.
     */
    protected array $jsonFields;

    /**
     * Mark fields as json to decode.
     */
    public function json(string|int ...$fields): self
    {
        $this->jsonFields = $fields;

        return $this;
    }

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
    abstract public function fetchColumn(int $column = 0): array|string|float|int|null|false;

    /**
     * Fetches all rows in a particular result column as an array.
     */
    abstract public function fetchAllColumns(int $column = 0): array;

    /**
     * Returns the number of rows in a result.
     */
    abstract public function numRows(): int|string;

    /**
     * Returns number of affected records.
     */
    abstract public function affectedRows(): int|string;

    /**
     * Decode json fields in row.
     */
    protected function decodeJsonInRow(array &$row): void
    {
        foreach ($this->jsonFields as $field) {
            if (isset($row[$field])) {
                $row[$field] = json_decode($row[$field], true);
            }
        }
    }
}
