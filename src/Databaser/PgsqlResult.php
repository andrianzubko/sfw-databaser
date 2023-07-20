<?php

namespace SFW\Databaser;

/**
 * Postgresql result handling.
 */
class PgsqlResult extends Result
{
    /**
     * Gets result set from statement.
     */
    public function __construct(protected \PgSql\Result|false $result)
    {
        $this->rows = pg_fetch_all($this->result, PGSQL_NUM);

        if ($this->rows) {
            foreach ($this->rows[0] as $i => $value) {
                $this->names[$i] = pg_field_name($this->result, $i);

                if (pg_field_type($this->result, $i) === 'json') {
                    foreach ($this->rows as $j => $row) {
                        if (isset($row[$i])) {
                            $this->rows[$j][$i] = json_decode($row[$i], true);
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns number of affected rows.
     */
    public function affectedRows(): int|string
    {
        return pg_affected_rows($this->result);
    }
}
