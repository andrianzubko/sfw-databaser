<?php

namespace SFW\Databaser;

/**
 * PostgreSQL driver.
 */
class PgSQL extends Driver
{
    /**
     * Database instance.
     */
    protected \PgSql\Connection|false $db = false;

    /**
     * Connecting to database on demand.
     */
    protected function connect(): void
    {
        $connect = $this->options['persistent'] ?? false === true
            ? 'pg_pconnect' : 'pg_connect';

        $this->db = $connect($this->options['connection'] ?? '', PGSQL_CONNECT_FORCE_NEW);

        if ($this->db === false) {
            throw new Exception('error in the process of establishing a connection');
        }

        pg_set_error_verbosity($this->db, PGSQL_ERRORS_VERBOSE);

        if (pg_set_client_encoding($this->db, $this->options['encoding'] ?? 'UTF-8') == -1) {
            throw new Exception(
                sprintf('cannot set encoding %s', $this->options['encoding'] ?? 'UTF-8')
            );
        }
    }

    /**
     * Begin command is different on different databases.
     */
    protected function makeBeginCommand(?string $isolation): string
    {
        if (isset($isolation)) {
            return "BEGIN $isolation";
        }

        return "BEGIN";
    }

    /**
     * Executing bundle queries at once.
     */
    protected function executeQueries(string $queries): array
    {
        $result = @pg_query($this->db, $queries);

        if ($result === false) {
            $error = pg_last_error($this->db);

            if (preg_match('/^ERROR:\s*([\dA-Z]{5}):\s*(.+)/u', $error, $M)) {
                return [false, [$M[2], $M[1]]];
            } else {
                return [false, ['unknown error']];
            }
        }

        return [$result, false];
    }

    /**
     * Escaping string.
     */
    protected function escapeString(string $string): string
    {
        return pg_escape_literal($this->db, $string);
    }

    /**
     * Extending this method for result overlaying.
     */
    public function query(string|array $queries): object|false
    {
        $result = parent::query($queries);

        return $result === false ? false : new PgSQLResult($result);
    }
}
