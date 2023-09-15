<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace SFW\Databaser;

/**
 * Mysql driver.
 */
class Mysql extends Driver
{
    /**
     * Database instance.
     */
    protected \mysqli $db;

    /**
     * Connecting to database on demand.
     *
     * @throws RuntimeException
     */
    protected function connect(): void
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        if (str_starts_with($this->options['host'] ?? '', '/')) {
            $this->options['socket'] = $this->options['host'];

            $this->options['host'] = null;
        }

        if ($this->options['persistent'] ?? false) {
            $this->options['host'] = 'p:' . ($this->options['host'] ?? 'localhost');
        }

        try {
            $this->db = new \mysqli(
                $this->options['host'] ?? 'localhost',
                $this->options['user'] ?? null,
                $this->options['pass'] ?? null,
                $this->options['db'] ?? null,
                $this->options['port'] ?? 3306,
                $this->options['socket'] ?? null,
            );

            $this->db->set_charset(
                $this->options['charset'] ?? 'utf8mb4'
            );
        } catch (\mysqli_sql_exception $error) {
            throw (new RuntimeException($error->getMessage()))
                ->setSqlState($error->getSqlState())
                ->addSqlStateToMessage();
        }
    }

    /**
     * Begin command is different at different databases.
     */
    protected function makeBeginCommand(?string $isolation): string
    {
        if (isset($isolation)) {
            return "SET TRANSACTION $isolation; START TRANSACTION";
        }

        return "START TRANSACTION";
    }

    /**
     * Assign result to local class.
     */
    protected function assignResult(object|false $result): Result
    {
        if ($result instanceof \mysqli_result) {
            $result = new MysqlResult($result, $this->db->affected_rows);
        } else {
            $result = new Result();
        }

        return $result->setMode($this->mode);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @throws RuntimeException
     */
    public function lastInsertId(): int|string|false
    {
        if (!isset($this->db)) {
            $this->connect();
        }

        return $this->db->insert_id;
    }

    /**
     * Executing bundle queries at once.
     *
     * @throws RuntimeException
     */
    protected function executeQueries(string $queries): object|false
    {
        try {
            $this->db->multi_query($queries);

            do {
                $result = $this->db->store_result();
            } while (
                $this->db->next_result()
            );
        } catch (\mysqli_sql_exception $error) {
            throw (new RuntimeException($error->getMessage()))
                ->setSqlState($error->getSqlState())
                ->addSqlStateToMessage();
        }

        return $result;
    }

    /**
     * Escaping string.
     */
    protected function escapeString(string $string): string
    {
        return "'" . $this->db->real_escape_string($string) . "'";
    }
}
