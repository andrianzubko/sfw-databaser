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
     * Connects to database on demand.
     *
     * @throws Exception\Runtime
     */
    protected function connect(): self
    {
        if ($this->connected) {
            return $this;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        if (!isset($this->options['host'])) {
            $this->options['host'] = 'localhost';
        } elseif (
            str_starts_with($this->options['host'], '/')
        ) {
            $this->options['socket'] = $this->options['host'];

            $this->options['host'] = 'localhost';
        }

        if ($this->options['persistent'] ?? false) {
            $this->options['host'] = 'p:' . $this->options['host'];
        }

        try {
            $this->db = new \mysqli(
                $this->options['host'],
                $this->options['user'] ?? null,
                $this->options['pass'] ?? null,
                $this->options['db'] ?? null,
                $this->options['port'] ?? 3306,
                $this->options['socket'] ?? null,
            );

            $this->db->set_charset($this->options['charset'] ?? 'utf8mb4');
        } catch (\mysqli_sql_exception $e) {
            throw (new Exception\Runtime($e->getMessage()))
                ->setSqlState($e->getSqlState())
                ->addSqlStateToMessage();
        }

        $this->connected = true;

        return $this;
    }

    /**
     * Begin command is different at different databases.
     */
    protected function makeBeginCommand(?string $isolation): string
    {
        if ($isolation !== null) {
            return "SET TRANSACTION $isolation; START TRANSACTION";
        }

        return "START TRANSACTION";
    }

    /**
     * Assigns result to local class.
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
     * @throws Exception\Runtime
     */
    public function lastInsertId(): int|string|false
    {
        if (!$this->connected) {
            $this->connect();
        }

        return $this->db->insert_id;
    }

    /**
     * Executes bundle queries at once.
     *
     * @throws Exception\Runtime
     */
    protected function executeQueries(string $queries): \mysqli_result|false
    {
        try {
            $this->db->multi_query($queries);

            do {
                $result = $this->db->store_result();
            } while (
                $this->db->next_result()
            );
        } catch (\mysqli_sql_exception $e) {
            throw (new Exception\Runtime($e->getMessage()))
                ->setSqlState($e->getSqlState())
                ->addSqlStateToMessage();
        }

        return $result;
    }

    /**
     * Escapes special characters in a string.
     */
    protected function escapeString(string $string): string
    {
        return "'" . $this->db->real_escape_string($string) . "'";
    }
}
