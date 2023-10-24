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
     * Default mode for fetchAll method of Result class.
     */
    protected ?int $mode = null;

    /**
     * External profiler for queries.
     */
    protected \Closure $profiler;

    /**
     * Timer of executed queries.
     */
    protected static float $timer = 0.0;

    /**
     * Count of executed queries.
     */
    protected static int $counter = 0;

    /**
     * Cleanups at shutdown if still in transaction.
     */
    public function __construct(protected array $options = [])
    {
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
     * Connects to database on demand.
     *
     * @throws Exception\Runtime
     */
    abstract protected function connect(): void;

    /**
     * Begin command is different at different databases.
     */
    abstract protected function makeBeginCommand(?string $isolation): string;

    /**
     * Begins transaction.
     *
     * @throws Exception\Runtime
     */
    public function begin(?string $isolation = null): self
    {
        if ($this->inTrans) {
            $this->rollback();
        }

        $this->queries[] = [self::BEGIN, $this->makeBeginCommand($isolation)];

        $this->inTrans = true;

        return $this;
    }

    /**
     * Commits transaction. If nothing was after begin, then ignores begin.
     *
     * @throws Exception\Runtime
     */
    public function commit(): self
    {
        if ($this->queries && end($this->queries)[0] === self::BEGIN) {
            array_pop($this->queries);
        } else {
            $this->queries[] = [self::COMMIT, "COMMIT"];
        }

        $this->execute();

        $this->inTrans = false;

        return $this;
    }

    /**
     * Rollbacks transaction.
     *
     * @throws Exception\Runtime
     */
    public function rollback(): self
    {
        if ($this->queries && end($this->queries)[0] === self::BEGIN) {
            array_pop($this->queries);
        } else {
            $this->queries[] = [self::ROLLBACK, "ROLLBACK"];
        }

        $this->execute();

        $this->inTrans = false;

        return $this;
    }

    /**
     * Queues query.
     *
     * @throws Exception\Runtime
     */
    public function queue(string $query): self
    {
        $this->queries[] = [self::REGULAR, $query];

        if (\count($this->queries) > 64) {
            $this->execute();
        }

        return $this;
    }

    /**
     * Assigns result to local class.
     */
    abstract protected function assignResult(object|false $result): Result;

    /**
     * Executes query and returns result.
     *
     * @throws Exception\Runtime
     */
    public function query(string $query): Result
    {
        $this->queries[] = [self::REGULAR, $query];

        return $this->assignResult($this->execute());
    }

    /**
     * Executes all queued queries.
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
    abstract public function lastInsertId(): int|string|false;

    /**
     * Executes bundle queries at once.
     *
     * @throws Exception\Runtime
     */
    abstract protected function executeQueries(string $queries): object|false;

    /**
     * Executes all queued queries and returns result.
     *
     * @throws Exception\Runtime
     */
    protected function execute(): object|false
    {
        if (!$this->queries) {
            return false;
        }

        $queries = array_column($this->queries, 1);

        $this->queries = [];

        $timer = gettimeofday(true);

        try {
            $result = $this->executeQueries(implode('; ', $queries));
        } finally {
            $timer = gettimeofday(true) - $timer;

            self::$timer += $timer;

            self::$counter += 1;

            if (isset($this->profiler)) {
                ($this->profiler)($timer, $queries);
            }
        }

        return $result;
    }

    /**
     * Escapes special characters in a string.
     */
    abstract protected function escapeString(string $string): string;

    /**
     * Formats numbers for queries.
     */
    public function number(mixed $number, string $null = 'NULL'): string
    {
        switch (true) {
            case $number === null:
                return $null;
            case \is_array($number):
                foreach ($number as $i => $value) {
                    $number[$i] = $this->number($value, $null);
                }

                return $this->commas($number, '');
            default:
                return (string) (float) $number;
        }
    }

    /**
     * Formats booleans for queries.
     */
    public function bool(mixed $bool, string $null = 'NULL'): string
    {
        switch (true) {
            case $bool === null:
                return $null;
            case \is_array($bool):
                foreach ($bool as $i => $value) {
                    $bool[$i] = $this->bool($value, $null);
                }

                return $this->commas($bool, '');
            default:
                return $bool ? 'true' : 'false';
        }
    }

    /**
     * Formats and escapes strings for queries.
     *
     * @throws Exception\Runtime
     */
    public function string(mixed $string, string $null = 'NULL'): string
    {
        switch (true) {
            case $string === null:
                return $null;
            case \is_array($string):
                foreach ($string as $i => $value) {
                    $string[$i] = $this->string($value, $null);
                }

                return $this->commas($string, '');
            default:
                return $this->escapeString((string) $string);
        }
    }

    /**
     * Formats and escapes strings, booleans and numerics for queries depending on types.
     *
     * @throws Exception\Runtime
     */
    public function scalar(mixed $scalar, string $null = 'NULL'): string
    {
        switch (true) {
            case $scalar === null:
                return $null;
            case \is_array($scalar):
                foreach ($scalar as $i => $value) {
                    $scalar[$i] = $this->scalar($value, $null);
                }

                return $this->commas($scalar, '');
            case \is_numeric($scalar):
                return (string) $scalar;
            case \is_bool($scalar):
                return $scalar ? 'true' : 'false';
            default:
                return $this->escapeString((string) $scalar);
        }
    }

    /**
     * Joins expressions for WHERE.
     */
    public function every(array $expressions, ?string $default = 'true'): string
    {
        if (!$expressions) {
            return $default;
        }

        return implode(' AND ', $expressions);
    }

    /**
     * Joins expressions for WHERE.
     */
    public function any(array $expressions, ?string $default = 'true'): string
    {
        if (!$expressions) {
            return $default;
        }

        return implode(' OR ', $expressions);
    }

    /**
     * Joins expressions for SELECT or ORDER.
     */
    public function commas(array $expressions, ?string $default = 'true'): string
    {
        if (!$expressions) {
            return $default;
        }

        return implode(', ', $expressions);
    }

    /**
     * Joins expressions with pluses.
     */
    public function pluses(array $expressions, ?string $default = ''): string
    {
        if (!$expressions) {
            return $default;
        }

        return implode(' + ', $expressions);
    }

    /**
     * Joins expressions with spaces.
     */
    public function spaces(array $expressions, ?string $default = ''): string
    {
        if (!$expressions) {
            return $default;
        }

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
    public function setProfiler(\Closure $profiler): self
    {
        $this->profiler = $profiler;

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
