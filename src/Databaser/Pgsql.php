<?php

namespace SFW\Databaser;

/**
 * PostgreSQL driver.
 */
class Pgsql extends Driver
{
    /**
     * Database instance.
     */
    protected \PgSql\Connection|false $db = false;

    /**
     * Connecting to database on demand.
     *
     * @throws Exception
     */
    protected function connect(): void
    {
        $connect = ($this->options['persistent'] ?? false) === true ? 'pg_pconnect' : 'pg_connect';

        $this->db = $connect($this->options['connection'] ?? '', \PGSQL_CONNECT_FORCE_NEW);

        if ($this->db === false) {
            throw new Exception('Error in the process of establishing a connection');
        }

        pg_set_error_verbosity($this->db, \PGSQL_ERRORS_VERBOSE);

        if (pg_set_client_encoding($this->db, $this->options['encoding'] ?? 'UTF-8') == -1) {
            throw new Exception(
                sprintf('Cannot set encoding %s', $this->options['encoding'] ?? 'UTF-8')
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
     * Executing query and returning result.
     *
     * @throws Exception
     */
    public function query(string|array $queries): PgsqlResult|false
    {
        $result = parent::query($queries);

        return new PgsqlResult($result);
    }
}
