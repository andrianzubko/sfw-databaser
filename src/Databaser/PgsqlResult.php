<?php

namespace SFW\Databaser;

/**
 * PostgreSQL result handling.
 */
class PgsqlResult extends Result
{
    /**
     * Columns are returned into the array having the field-name as the array index.
     */
    public const ASSOC = PGSQL_ASSOC;

    /**
     * Columns are returned into the array having an enumerated index.
     */
    public const NUM = PGSQL_NUM;

    /**
     * Columns are returned into the array having both a numerical index and the field-name as the associative index.
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
        $rows = pg_fetch_all($this->result, $mode);

        if (isset($this->jsonFields)) {
            foreach ($rows as $i => $row) {
                $this->decodeJsonInRow($rows[$i]);
            }
        }

        return $rows;
    }

    /**
     * Fetch the next row of a result set as an associative, a numeric array, or both.
     */
    public function fetchArray(int $mode = self::ASSOC): array|false
    {
        $row = pg_fetch_array($this->result, null, $mode);

        if (isset($this->jsonFields)
            && $row !== false
        ) {
            $this->decodeJsonInRow($row);
        }

        return $row;
    }

    /**
     * Fetch the next row of a result set as an associative array.
     */
    public function fetchAssoc(): array|false
    {
        $row = pg_fetch_assoc($this->result);

        if (isset($this->jsonFields)
            && $row !== false
        ) {
            $this->decodeJsonInRow($row);
        }

        return $row;
    }

    /**
     * Fetch the next row of a result set as an object.
     */
    public function fetchObject(): object|false
    {
        $row = pg_fetch_assoc($this->result);

        if (isset($this->jsonFields)
            && $row !== false
        ) {
            $this->decodeJsonInRow($row);
        }

        return $row === false ? false : (object) $row;
    }

    /**
     * Fetch the next row of a result set as an enumerated array.
     */
    public function fetchRow(): array|false
    {
        $row = pg_fetch_row($this->result);

        if (isset($this->jsonFields)
            && $row !== false
        ) {
            $this->decodeJsonInRow($row);
        }

        return $row;
    }

    /**
     * Fetch a single column from the next row of a result set.
     */
    public function fetchColumn(int $column = 0): array|string|null|false
    {
        $value = pg_fetch_result($this->result, $column);

        if (isset($this->jsonFields)
            && !empty($value)
        ) {
            return json_decode($value, true);
        }

        return $value;
    }

    /**
     * Fetches all rows in a particular result column as an array.
     */
    public function fetchAllColumns(int $column = 0): array
    {
        $values = pg_fetch_all_columns($this->result, $column);

        if (isset($this->jsonFields)) {
            foreach ($values as $i => $value) {
                if (!empty($value)) {
                    $values[$i] = json_decode($value, true);
                }
            }
        }

        return $values;
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
