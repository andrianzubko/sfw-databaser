<?php

namespace SFW\Databaser;

/**
 * MySQL driver.
 */
class Mysql extends Driver
{
    /**
     * Database instance.
     */
    protected \mysqli|false $db = false;

    /**
     * Connecting to database on demand.
     *
     * @throws Exception
     */
    protected function connect(): void
    {
        mysqli_report(\MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT);

        try {
            $this->db = new \mysqli(
             ...array_intersect_key($this->options,
                    array_flip(
                        [
                            'hostname',
                            'username',
                            'password',
                            'database',
                            'port',
                            'socket',
                        ]
                    )
                )
            );

            $this->db->set_charset($this->options['charset'] ?? 'utf8mb4');
        } catch (\mysqli_sql_exception $error) {
            throw new Exception($error->getMessage(), $error->getSqlState());
        }
    }

    /**
     * Begin command is different on different databases.
     */
    protected function makeBeginCommand(?string $isolation): string
    {
        if (isset($isolation)) {
            return "SET TRANSACTION $isolation; START TRANSACTION";
        }

        return "START TRANSACTION";
    }

    /**
     * Executing bundle queries at once.
     */
    protected function executeQueries(string $queries): array
    {
        try {
            $this->db->multi_query($queries);

            do {
                $result = $this->db->store_result();
            } while (
                $this->db->next_result()
            );
        } catch (\mysqli_sql_exception $error) {
            return [false, [$error->getMessage(), $error->getSqlState()]];
        }

        return [$result, false];
    }

    /**
     * Escaping string.
     */
    protected function escapeString(string $string): string
    {
        return "'" . $this->db->real_escape_string($string) . "'";
    }

    /**
     * Executing query and returning result.
     *
     * @throws Exception
     */
    public function query(string|array $queries): MysqlResult|false
    {
        $result = parent::query($queries);

        if ($result === false) {
            return new MysqlResultEmpty();
        }

        return new MysqlResult($result, $this->db->affected_rows);
    }
}
