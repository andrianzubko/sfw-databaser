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
     * External profiler for queries.
     */
    protected ?\Closure $profiler = null;

    /**
     * Default mode for fetchAll method of Result class.
     */
    protected ?int $mode = null;

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
    protected static float $timer = 0;

    /**
     * Count of executed queries.
     */
    protected static int $counter = 0;

    /**
     * Clearing at shutdown if still in transaction.
     */
    public function __construct(protected array $options = []) {
        if (isset($this->options['mode'])) {
            $this->mode = $this->options['mode'];
        }

        if ($this->options['cleanup'] ?? true) {
            register_shutdown_function(
                function () {
                    if ($this->inTrans) {
                        try {
                            $this->rollback();
                        } catch (Exception) {
                        }
                    }
                }
            );
        }
    }

    /**
     * Connecting to database on demand.
     *
     * @throws Exception\Runtime
     */
    abstract protected function connect(): self;

    /**
     * Begin command is different at different databases.
     */
    abstract protected function makeBeginCommand(?string $isolation): string;

    /**
     * Begin transaction.
     *
     * @throws Exception\Runtime
     */
    public function begin(?string $isolation = null): self
    {
        $this->execute();

        if ($this->inTrans) {
            $this->rollback();
        }

        $this->queries[] = [self::BEGIN, $this->makeBeginCommand($isolation)];

        return $this;
    }

    /**
     * Commit transaction. If nothing was after begin, then ignore begin.
     *
     * @throws Exception\Runtime
     */
    public function commit(): self
    {
        if ($this->queries
            && end($this->queries)[0] === self::BEGIN
        ) {
            array_pop($this->queries);
        } else {
            $this->queries[] = [self::COMMIT, "COMMIT"];
        }

        $this->execute();

        return $this;
    }

    /**
     * Rollback transaction.
     *
     * @throws Exception\Runtime
     */
    public function rollback(?string $to = null): self
    {
        if (isset($to)) {
            $this->queries[] = [self::REGULAR, "ROLLBACK TO $to"];
        } else {
            $this->queries[] = [self::ROLLBACK, "ROLLBACK"];
        }

        $this->execute();

        return $this;
    }

    /**
     * Queueing query.
     *
     * @throws Exception\Runtime
     */
    public function queue(string $query): self
    {
        $this->queries[] = [self::REGULAR, $query];

        if (count($this->queries) > 64) {
            $this->execute();
        }

        return $this;
    }

    /**
     * Assign result to local class.
     */
    abstract protected function assignResult(object|false $result): Result;

    /**
     * Executing query and return result.
     *
     * @throws Exception\Runtime
     */
    public function query(string $query): Result
    {
        $this->queries[] = [self::REGULAR, $query];

        return $this->assignResult($this->execute());
    }

    /**
     * Executing all queued queries.
     *
     * @throws Exception\Runtime
     */
    public function flush(): self
    {
        $this->execute();

        return $this;
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @throws Exception\Runtime
     */
    public function lastInsertId(): int|string|false
    {
        if (!isset($this->db)) {
            $this->connect();
        }

        return false;
    }

    /**
     * Executing bundle queries at once.
     *
     * @throws Exception\Runtime
     */
    abstract protected function executeQueries(string $queries): object|false;

    /**
     * Executing all queued queries and result returning.
     *
     * @throws Exception\Runtime
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

        self::$counter += 1;

        $timer = gettimeofday(true);

        try {
            $result = $this->executeQueries(implode(';', $queries));
        } finally {
            self::$timer += $timer = gettimeofday(true) - $timer;

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
            return (string) (float) $numbers;
        } elseif (is_array($numbers)) {
            foreach ($numbers as &$value) {
                if (isset($value)) {
                    $value = (float) $value;
                } else {
                    $value = $null;
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
     * @throws Exception\Runtime
     */
    public function string(mixed $strings, string $null = 'NULL'): string
    {
        if (!isset($this->db)) {
            $this->connect();
        }

        if (is_scalar($strings)) {
            return $this->escapeString((string) $strings);
        } elseif (is_array($strings)) {
            foreach ($strings as &$value) {
                if (isset($value)) {
                    $value = $this->escapeString((string) $value);
                } else {
                    $value = $null;
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
     * Gets timer of executed queries.
     */
    public function getTimer(): float
    {
        return self::$timer;
    }

    /**
     * Gets count of executed queries.
     */
    public function getCounter(): int
    {
        return self::$counter;
    }

    /**
     * Gets transaction status.
     */
    public function isInTrans(): bool
    {
        return $this->inTrans;
    }

    /**
     * Sets external profiler for queries.
     */
    public function setProfiler(callable $profiler): self
    {
        $this->profiler = $profiler(...);

        return $this;
    }

    /**
     * Sets default mode for fetchAll method of Result class.
     */
    public function setMode(?int $mode): self
    {
        $this->mode = $mode;

        return $this;
    }
}
