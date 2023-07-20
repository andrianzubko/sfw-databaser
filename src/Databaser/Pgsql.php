<?php

namespace SFW\Databaser;

/**
 * Postgresql driver.
 */
class Pgsql extends Driver
{
    /**
     * Database instance.
     */
    protected \PgSql\Connection $db;

    /**
     * Driver name.
     */
    protected string $driver = 'Postgresql';

    /**
     * Connecting to database on demand.
     *
     * @throws Exception
     */
    protected function connect(): void
    {
        $db = (($this->options['persistent'] ?? false) ? 'pg_pconnect' : 'pg_connect')(
            sprintf(
                "host=%s port=%s dbname=%s user=%s password=%s options='--client_encoding=%s'",
                    $this->options['host'] ?? 'localhost',
                    $this->options['port'] ?? 5432,
                    $this->options['db'] ?? '',
                    $this->options['user'] ?? '',
                    $this->options['pass'] ?? '',
                    $this->options['charset'] ?? 'utf-8'
            ),
            PGSQL_CONNECT_FORCE_NEW
        );

        if ($db === false) {
            throw new Exception(
                $this->driver, 'Error in the process of establishing a connection'
            );
        }

        pg_set_error_verbosity($db, PGSQL_ERRORS_VERBOSE);

        $this->db = $db;
    }

    /**
     * Begin command is different at different databases.
     */
    protected function makeBeginCommand(?string $isolation): string
    {
        if (isset($isolation)) {
            return "START TRANSACTION $isolation";
        }

        return "START TRANSACTION";
    }

    /**
     * Assign result to local class.
     */
    protected function assignResult(object|false $result): Result
    {
        return new PgsqlResult($result);
    }

    /**
     * Executing bundle queries at once.
     *
     * @throws Exception
     */
    protected function executeQueries(string $queries): object|false
    {
        $result = @pg_query($this->db, $queries);

        if ($result !== false) {
            return $result;
        }

        if (preg_match('/^ERROR:\s*([\dA-Z]{5}):\s*(.+)/u', pg_last_error($this->db), $M)) {
            throw new Exception($this->driver, $M[2], $M[1]);
        } else {
            throw new Exception($this->driver, $M[0]);
        }
    }

    /**
     * Escaping string.
     */
    protected function escapeString(string $string): string
    {
        return pg_escape_literal($this->db, $string);
    }
}
