<?php /** @noinspection PhpComposerExtensionStubsInspection */

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
     * Connecting to database on demand.
     *
     * @throws RuntimeException
     */
    protected function connect(): void
    {
        $db = (($this->options['persistent'] ?? false) ? 'pg_pconnect' : 'pg_connect')(
            sprintf(
                "host=%s port=%s dbname=%s user=%s password=%s",
                    $this->options['host'] ?? 'localhost',
                    $this->options['port'] ?? 5432,
                    $this->options['db'] ?? '',
                    $this->options['user'] ?? '',
                    $this->options['pass'] ?? ''
            ),
            PGSQL_CONNECT_FORCE_NEW
        );

        if ($db === false) {
            throw (new RuntimeException('Error in the process of establishing a connection'))
                ->addSqlStateToMessage();
        }

        pg_set_error_verbosity($db, PGSQL_ERRORS_VERBOSE);

        $charset = $this->options['charset'] ?? 'utf-8';

        if (pg_set_client_encoding($db, $charset) === -1) {
            throw (new RuntimeException("Unable to set charset $charset"))
                ->addSqlStateToMessage();
        }

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
        if ($result instanceof \PgSql\Result) {
            $result = new PgsqlResult($result);
        } else {
            $result = new Result();
        }

        return $result->setMode($this->mode);
    }

    /**
     * Executing bundle queries at once.
     *
     * @throws RuntimeException
     */
    protected function executeQueries(string $queries): object|false
    {
        $result = @pg_query($this->db, $queries);

        if ($result !== false) {
            return $result;
        }

        if (preg_match('/^ERROR:\s*([\dA-Z]{5}):\s*(.+)/u',
                pg_last_error($this->db), $M)
        ) {
            throw (new RuntimeException($M[2]))
                ->setSqlState($M[1])
                ->addSqlStateToMessage();
        } else {
            throw (new RuntimeException($M[0]))
                ->addSqlStateToMessage();
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
