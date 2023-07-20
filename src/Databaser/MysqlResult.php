<?php

namespace SFW\Databaser;

/**
 * MySQL result handling.
 */
class MysqlResult extends Result
{
    /**
     * Gets result set from statement.
     */
    public function __construct(protected \mysqli_result|false $result, protected int|string $affected)
    {
        $this->rows = $this->result->fetch_all();

        if ($this->rows) {
            foreach ($result->fetch_fields() as $i => $field) {
                $this->names[$i] = $field->name;

                if ($field->type === MYSQLI_TYPE_JSON) {
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
        return $this->affected;
    }
}
