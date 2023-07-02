<?php

namespace SFW\Databaser;

/**
 * PostgreSQL result handling.
 */
class PgsqlResult extends Result
{
    /**
     * Columns are returned into the array having the fieldname as the array index.
     */
    public const ASSOC = PGSQL_ASSOC;

    /**
     * Columns are returned into the array having an enumerated index.
     */
    public const NUM = PGSQL_NUM;

    /**
     * Columns are returned into the array having both a numerical index and the fieldname as the associative index.
     */
    public const BOTH = PGSQL_BOTH;

    /**
     * Passing parameters to properties.
     */
    public function __construct(protected \PgSql\Result $result) {}

    /**
     * Fetches all result rows as an associative array, a numeric array, or both.
     */
    public function fetchAll(int $mode = self::ASSOC): array
    {
        return pg_fetch_all($this->result, $mode);
    }

    /**
     * Fetch the next row of a result set as an associative, a numeric array, or both.
     */
    public function fetchArray(int $mode = self::ASSOC): array|false
    {
        return pg_fetch_array($this->result, null, $mode);
    }

    /**
     * Fetch the next row of a result set as an associative array.
     */
    public function fetchAssoc(): array|false
    {
        return pg_fetch_assoc($this->result);
    }

    /**
     * Fetch the next row of a result set as an object.
     */
    public function fetchObject(): object|false
    {
        return pg_fetch_object($this->result);
    }

    /**
     * Fetch the next row of a result set as an enumerated array.
     */
    public function fetchRow(): array|false
    {
        return pg_fetch_row($this->result);
    }

    /**
     * Fetch a single column from the next row of a result set.
     */
    public function fetchColumn(int $column): string|null|false
    {
        return pg_fetch_result($this->result, $column);
    }

    /**
     * Returns the number of rows in a result.
     */
    public function numRows(): int
    {
        return pg_num_rows($this->result);
    }

    /**
     * Returns number of affected records.
     */
    public function affectedRows(): int
    {
        return pg_affected_rows($this->result);
    }
}
