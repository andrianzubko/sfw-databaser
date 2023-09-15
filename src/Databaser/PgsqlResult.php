<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace SFW\Databaser;

/**
 * Postgresql result handling.
 */
class PgsqlResult extends Result
{
    /**
     * Gets column names and looking for json types.
     */
    public function __construct(protected \PgSql\Result $result)
    {
        $numFields = pg_num_fields($this->result);

        if ($numFields) {
            for ($i = 0; $i < $numFields; $i++) {
                $this->colNames[$i] = pg_field_name($this->result, $i);

                if (pg_field_type($this->result, $i) === 'json') {
                    $this->jsonCols[$i] = true;
                }
            }
        }
    }

    /**
     * Fetches all result rows as numeric array.
     */
    protected function fetchAllRows(): array
    {
        return pg_fetch_all($this->result, PGSQL_NUM);
    }

    /**
     * Fetches next result row as numeric array.
     */
    protected function fetchNextRow(): array|false
    {
        return pg_fetch_row($this->result);
    }

    /**
     * Fetches next result row column.
     */
    protected function fetchNextRowColumn(int $i): mixed
    {
        return pg_fetch_result($this->result, $i);
    }

    /**
     * Fetches all result rows columns.
     */
    protected function fetchAllRowsColumns(int $i): array
    {
        return pg_fetch_all_columns($this->result, $i);
    }

    /**
     * Moves internal result pointer.
     */
    public function seek(int $i = 0): self
    {
        pg_result_seek($this->result, $i);

        return $this;
    }

    /**
     * Gets number of affected rows.
     */
    public function affectedRows(): int|string
    {
        return pg_affected_rows($this->result);
    }

    /**
     * Gets the number of result rows.
     */
    public function numRows(): int|string
    {
        return pg_num_rows($this->result);
    }
}
