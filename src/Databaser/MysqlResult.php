<?php

namespace SFW\Databaser;

/**
 * MySQL result handling.
 */
class MysqlResult extends Result
{
    /**
     * Columns are returned into the array having the field-name as the array index.
     */
    public const ASSOC = MYSQLI_ASSOC;

    /**
     * Columns are returned into the array having an enumerated index.
     */
    public const NUM = MYSQLI_NUM;

    /**
     * Columns are returned into the array having both a numerical index and the field-name as the associative index.
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
        $rows = $this->result->fetch_all($mode);

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
        $row = $this->result->fetch_array($mode) ?? false;

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
        $row = $this->result->fetch_assoc() ?? false;

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
        $row = $this->result->fetch_assoc() ?? false;

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
        $row = $this->result->fetch_row() ?? false;

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
    public function fetchColumn(int $column = 0): array|string|float|int|null|false
    {
        $value = $this->result->fetch_column($column);

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
        $values = [];

        while (($value = $this->fetchColumn($column)) !== false) {
            $values[] = $value;
        }

        return $values;
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
