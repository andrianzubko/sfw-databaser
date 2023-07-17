<?php

namespace SFW\Databaser;

/**
 * Abstraction for driver.
 */
abstract class Driver
{
    /**
     * Queries queue.
     */
    protected array $queries = [];

    /**
     * Microtime of executed queries.
     */
    protected float $microtime = 0;

    /**
     * Count of executed queries.
     */
    protected int $counter = 0;

    /**
     * In transaction flag.
     */
    protected bool $inTrans = false;

    /**
     * Special mark for regular queries.
     */
    protected const REGULAR = 0;

    /**
     * Special mark for begin queries.
     */
    protected const BEGIN = 1;

    /**
     * Special mark for commit queries.
     */
    protected const COMMIT = 2;

    /**
     * Special mark for rollback queries.
     */
    protected const ROLLBACK = 3;

    /**
     * Clearing on shutdown.
     */
    public function __construct(protected array $options, protected mixed $profiler = null)
    {
        register_shutdown_function(
            function () {
                register_shutdown_function(
                    function () {
                        if ($this->inTrans) {
                            $this->rollback();
                        }
                    }
                );
            }
        );
    }

    /**
     * Connecting to database on demand.
     *
     * Throws \SFW\Databaser\Exception
     */
    abstract protected function connect(): void;

    /**
     * Begin command is different on different databases.
     */
    abstract protected function makeBeginCommand(?string $isolation): string;

    /**
     * Executing bundle queries at once.
     */
    abstract protected function executeQueries(string $queries): array;

    /**
     * Escaping string.
     */
    abstract protected function escapeString(string $string): string;

    /**
     * Begin transaction.
     *
     * Throws \SFW\Databaser\Exception
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
     * Throws \SFW\Databaser\Exception
     */
    public function commit(): void
    {
        if ($this->queries && end($this->queries)[0] == self::BEGIN) {
            array_pop($this->queries);
        } else {
            $this->queries[] = [self::COMMIT, "COMMIT"];
        }

        $this->execute();
    }

    /**
     * Rollback transaction.
     *
     * Throws \SFW\Databaser\Exception
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
     * Throws \SFW\Databaser\Exception
     */
    public function queue(string|array $queries): void
    {
        if (is_array($queries)) {
            foreach ($queries as $query) {
                $this->queries[] = [self::REGULAR, $query];
            }
        } else {
            $this->queries[] = [self::REGULAR, $queries];
        }

        if (count($this->queries) >= 100) {
            $this->execute();
        }
    }

    /**
     * Executing query and result returning.
     *
     * Throws \SFW\Databaser\Exception
     *
     * @return Result|false
     */
    public function query(string|array $queries): object|false
    {
        if (is_array($queries)) {
            foreach ($queries as $query) {
                $this->queries[] = [self::REGULAR, $query];
            }
        } else {
            $this->queries[] = [self::REGULAR, $queries];
        }

        return $this->execute();
    }

    /**
     * Executing all queued queries.
     *
     * Throws \SFW\Databaser\Exception
     */
    public function flush(): void
    {
        $this->execute();
    }

    /**
     * Executing all queued queries and result returning.
     *
     * Throws \SFW\Databaser\Exception
     */
    protected function execute(): object|false
    {
        if (!$this->queries) {
            return false;
        }

        if ($this->db === false) {
            $this->connect();
        }

        foreach ($this->queries as $query) {
            if ($query[0] == self::BEGIN) {
                $this->inTrans = true;
            } elseif ($query[0] == self::COMMIT || $query[0] == self::ROLLBACK) {
                $this->inTrans = false;
            }
        }

        $queries = array_column($this->queries, 1);

        $this->queries = [];

        $this->counter += 1;

        $microtime = gettimeofday(true);

        [$result, $error] = $this->executeQueries(implode(';', $queries));

        $this->microtime += $microtime = gettimeofday(true) - $microtime;

        if (isset($this->profiler)) {
            ($this->profiler)($microtime, $queries);
        }

        if ($error !== false) {
            throw new Exception(...$error);
        }

        return $result;
    }

    /**
     * Formatting numbers for queries.
     */
    public function number(array|string|float|null $numbers, string $null = 'NULL'): string
    {
        if (is_scalar($numbers)) {
            return (string) (double) $numbers;
        } elseif (is_array($numbers)) {
            foreach ($numbers as &$value) {
                if (isset($value)) {
                    $value = (double) $value;
                } else {
                    $value = $null;
                }
            }

            return implode(',', $numbers);
        }

        return $null;
    }

    /**
     * Formatting and escaping strings for queries.
     *
     * Throws \SFW\Databaser\Exception
     */
    public function string(array|string|float|null $strings, string $null = 'NULL'): string
    {
        if ($this->db === false) {
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
        if ($expressions) {
            return implode(' AND ', $expressions);
        }

        return 'true';
    }

    /**
     * Join expressions for where part of queries.
     */
    public function any(array $expressions): string
    {
        if ($expressions) {
            return implode(' OR ', $expressions);
        }

        return 'true';
    }

    /**
     * Join expressions for select or order part of queries.
     */
    public function commas(array $expressions): string
    {
        if ($expressions) {
            return implode(',', $expressions);
        }

        return 'true';
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
     * Getting microtime of executed querues.
     */
    public function getMicrotime(): float
    {
        return $this->microtime;
    }

    /**
     * Getting count of executed querues.
     */
    public function getCounter(): int
    {
        return $this->counter;
    }
}
