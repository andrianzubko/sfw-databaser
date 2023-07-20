<?php

namespace SFW\Databaser;

/**
 * Database driver.
 */
abstract class Driver
{
    /**
     * Special mark for regular query.
     */
    protected const REGULAR = 0;

    /**
     * Special mark for begin query.
     */
    protected const BEGIN = 1;

    /**
     * Special mark for commit query.
     */
    protected const COMMIT = 2;

    /**
     * Special mark for rollback query.
     */
    protected const ROLLBACK = 3;

    /**
     * Queries queue.
     */
    protected array $queries = [];

    /**
     * In transaction flag.
     */
    protected bool $inTrans = false;

    /**
     * Timer of executed queries.
     */
    protected float $timer = 0;

    /**
     * Count of executed queries.
     */
    protected int $counter = 0;

    /**
     * Clearing at shutdown if still in transaction.
     */
    public function __construct(protected array $options = [], protected mixed $profiler = null)
    {
        register_shutdown_function(
            function () {
                register_shutdown_function(
                    function () {
                        if ($this->inTrans) {
                            try {
                                $this->rollback();
                            } catch (Exception) {}
                        }
                    }
                );
            }
        );
    }

    /**
     * Connecting to database on demand.
     *
     * @throws Exception
     */
    abstract protected function connect(): void;

    /**
     * Begin command is different at different databases.
     */
    abstract protected function makeBeginCommand(?string $isolation): string;

    /**
     * Begin transaction.
     *
     * @throws Exception
     */
    public function begin(?string $isolation = null): void
    {
        $this->execute();

        if ($this->inTrans) {
            $this->rollback();
        }

        $this->queries[] = [self::BEGIN, $this->makeBeginCommand($isolation)];
    }

    /**
     * Commit transaction. If nothing was after begin, then ignore begin.
     *
     * @throws Exception
     */
    public function commit(): void
    {
        if ($this->queries
            && end($this->queries)[0] === self::BEGIN
        ) {
            array_pop($this->queries);
        } else {
            $this->queries[] = [self::COMMIT, "COMMIT"];
        }

        $this->execute();
    }

    /**
     * Rollback transaction.
     *
     * @throws Exception
     */
    public function rollback(?string $to = null): void
    {
        if (isset($to)) {
            $this->queries[] = [self::REGULAR, "ROLLBACK TO $to"];
        } else {
            $this->queries[] = [self::ROLLBACK, "ROLLBACK"];
        }

        $this->execute();
    }

    /**
     * Queueing query.
     *
     * @throws Exception
     */
    public function queue(array|string $queries): void
    {
        foreach ((array) $queries as $query) {
            $this->queries[] = [self::REGULAR, $query];
        }

        if (count($this->queries) > 64) {
            $this->execute();
        }
    }

    /**
     * Assign result to local class.
     */
    abstract protected function assignResult(object|false $result): Result;

    /**
     * Executing query and return result.
     *
     * @throws Exception
     */
    public function query(array|string $queries): Result|false
    {
        foreach ((array) $queries as $query) {
            $this->queries[] = [self::REGULAR, $query];
        }

        return $this->assignResult($this->execute());
    }

    /**
     * Executing all queued queries.
     *
     * @throws Exception
     */
    public function flush(): void
    {
        $this->execute();
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     */
    public function lastInsertId(): int|string|false
    {
        return false;
    }

    /**
     * Executing bundle queries at once.
     *
     * @throws Exception
     */
    abstract protected function executeQueries(string $queries): object|false;

    /**
     * Executing all queued queries and result returning.
     *
     * @throws Exception
     */
    protected function execute(): object|false
    {
        if (!$this->queries) {
            return false;
        }

        if (!isset($this->db)) {
            $this->connect();
        }

        foreach ($this->queries as $query) {
            if ($query[0] === self::BEGIN) {
                $this->inTrans = true;
            } elseif (
                   $query[0] === self::COMMIT
                || $query[0] === self::ROLLBACK
            ) {
                $this->inTrans = false;
            }
        }

        $queries = array_column($this->queries, 1);

        $this->queries = [];

        $this->counter += 1;

        $timer = gettimeofday(true);

        try {
            $result = $this->executeQueries(implode(';', $queries));
        } catch (Exception $error) {
            throw new Exception(
                $this->driver,
                $error->getSqlMessage(),
                $error->getSqlState()
            );
        } finally {
            $this->timer += $timer = gettimeofday(true) - $timer;

            if (isset($this->profiler)) {
                ($this->profiler)($timer, $queries);
            }
        }

        return $result;
    }

    /**
     * Formatting numbers for queries.
     */
    public function number(mixed $numbers, string $null = 'NULL'): string
    {
        if (is_scalar($numbers)) {
            return (string) (double) $numbers;
        } elseif (is_array($numbers)) {
            foreach ($numbers as $i => $value) {
                if (isset($value)) {
                    $numbers[$i] = (double) $value;
                } else {
                    $numbers[$i] = $null;
                }
            }

            return implode(',', $numbers);
        }

        return $null;
    }

    /**
     * Escaping string.
     */
    abstract protected function escapeString(string $string): string;

    /**
     * Formatting and escaping strings for queries.
     *
     * @throws Exception
     */
    public function string(mixed $strings, string $null = 'NULL'): string
    {
        if (!isset($this->db)) {
            $this->connect();
        }

        if (is_scalar($strings)) {
            return $this->escapeString((string) $strings);
        } elseif (is_array($strings)) {
            foreach ($strings as $i => $value) {
                if (isset($value)) {
                    $strings[$i] = $this->escapeString((string) $value);
                } else {
                    $strings[$i] = $null;
                }
            }

            return implode(',', $strings);
        }

        return $null;
    }

    /**
     * Join expressions for where part of queries.
     */
    public function every(array $expressions): string
    {
        if (!$expressions) {
            return 'true';
        }

        return implode(' AND ', $expressions);
    }

    /**
     * Join expressions for where part of queries.
     */
    public function any(array $expressions): string
    {
        if (!$expressions) {
            return 'true';
        }

        return implode(' OR ', $expressions);
    }

    /**
     * Join expressions for select or order part of queries.
     */
    public function commas(array $expressions): string
    {
        if (!$expressions) {
            return 'true';
        }

        return implode(',', $expressions);
    }

    /**
     * Join expressions with pluses.
     */
    public function pluses(array $expressions): string
    {
        return implode('+', $expressions);
    }

    /**
     * Join expressions with spaces.
     */
    public function spaces(array $expressions): string
    {
        return implode(' ', $expressions);
    }

    /**
     * Getting timer of executed queries.
     */
    public function getTimer(): float
    {
        return $this->timer;
    }

    /**
     * Getting count of executed queries.
     */
    public function getCounter(): int
    {
        return $this->counter;
    }
}
