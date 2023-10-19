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
     * Connects to database on demand.
     *
     * @throws Exception\Runtime
     */
    protected function connect(): void
    {
        if (isset($this->db)) {
            return;
        }

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
            throw (new Exception\Runtime('Error in the process of establishing a connection'))
                ->addSqlStateToMessage();
        }

        pg_set_error_verbosity($db, PGSQL_ERRORS_VERBOSE);

        $charset = $this->options['charset'] ?? 'utf-8';

        if (pg_set_client_encoding($db, $charset) === -1) {
            throw (new Exception\Runtime("Unable to set charset $charset"))
                ->addSqlStateToMessage();
        }

        $this->db = $db;
    }

    /**
     * Begin command is different at different databases.
     */
    protected function makeBeginCommand(?string $isolation): string
    {
        if ($isolation !== null) {
            return "START TRANSACTION $isolation";
        }

        return "START TRANSACTION";
    }

    /**
     * Assigns result to local class.
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
     * Not implemented!
     */
    public function lastInsertId(): false
    {
        return false;
    }

    /**
     * Executes bundle queries at once.
     *
     * @throws Exception\Runtime
     */
    protected function executeQueries(string $queries): \PgSql\Result
    {
        if (!isset($this->db)) {
            $this->connect();
        }

        $result = @pg_query($this->db, $queries);

        if ($result !== false) {
            return $result;
        }

        $lastError = pg_last_error($this->db);

        if (preg_match('/^ERROR:\s*([\dA-Z]{5}):\s*(.+)/u', $lastError, $M)) {
            throw (new Exception\Runtime($M[2]))
                ->setSqlState($M[1])
                ->addSqlStateToMessage();
        } else {
            throw (new Exception\Runtime($lastError))
                ->addSqlStateToMessage();
        }
    }

    /**
     * Escapes special characters in a string.
     */
    protected function escapeString(string $string): string
    {
        if (!isset($this->db)) {
            $this->connect();
        }

        return pg_escape_literal($this->db, $string);
    }
}
