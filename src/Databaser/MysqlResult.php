<?php

namespace SFW\Databaser;

/**
 * Mysql result handling.
 */
class MysqlResult extends Result
{
    /**
     * Gets column names and looking for json types.
     */
    public function __construct(protected \mysqli_result $result, protected int|string $affectedRows) {
        if ($this->result->field_count) {
            foreach ($result->fetch_fields() as $i => $field) {
                $this->colNames[$i] = $field->name;

                if ($field->type === MYSQLI_TYPE_JSON) {
                    $this->jsonCols[$i] = true;
                }
            }
        }
    }

    /**
     * Fetches all result rows without corrections as numeric array.
     */
    protected function fetchAllRows(): array
    {
        return $this->result->fetch_all();
    }

    /**
     * Fetches next result row without corrections as numeric array.
     */
    protected function fetchNextRow(): array|false
    {
        return $this->result->fetch_row() ?? false;
    }

    /**
     * Fetches next result column without corrections.
     */
    protected function fetchNextColumn(int $i): mixed
    {
        return $this->result->fetch_column($i);
    }

    /**
     * Moves internal result pointer.
     */
    public function seek(int $i = 0): self
    {
        $this->result->data_seek($i);

        return $this;
    }

    /**
     * Gets number of affected rows.
     */
    public function affectedRows(): int|string
    {
        return $this->affectedRows;
    }

    /**
     * Gets the number of rows in result.
     */
    public function numRows(): int|string
    {
        return $this->result->num_rows;
    }
}
