<?php

namespace SFW;

/**
 * Databaser.
 */
class Databaser
{
    /**
     * PDO instance
     */
    protected \PDO $db;

    /**
     * Queries queue.
     */
    protected array $queries = [];

    /**
     * Timer of executed queries.
     */
    protected float $timer = 0;

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
     * Clearing at shutdown if still in transaction.
     */
    public function __construct(
        protected string $dsn,
        protected ?string $username = null,
        protected ?string $password = null,
        protected ?array $options = null,
        protected mixed $profiler = null
    ) {
        register_shutdown_function(
            function () {
                register_shutdown_function(
                    function () {
                        if ($this->inTrans) {
                            try {
                                $this->rollback();
                            } catch (Databaser\Exception) {}
                        }
                    }
                );
            }
        );
    }

    /**
     * Connecting to database on demand.
     *
     * @throws Databaser\Exception
     */
    protected function connect(): void
    {
        $this->options ??= [];

        $this->options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        $this->options[\PDO::ATTR_EMULATE_PREPARES] = true;

        try {
            $this->db = new \PDO(
                $this->dsn,
                $this->username,
                $this->password,
                $this->options
            );
        } catch (\PDOException $error) {
            throw new Databaser\Exception($error->errorInfo);
        }
    }

    /**
     * Begin transaction.
     *
     * @throws Databaser\Exception
     */
    public function begin(?string $isolation = null): void
    {
        $this->execute();

        if ($this->inTrans) {
            $this->rollback();
        }

        $command = "START TRANSACTION";

        if (isset($isolation)) {
            if (str_starts_with($this->dsn, 'pgsql')) {
                $command = "START TRANSACTION $isolation";
            } else {
                $command = "SET TRANSACTION $isolation; START TRANSACTION";
            }
        }

        $this->queries[] = [self::BEGIN, $command];
    }

    /**
     * Commit transaction. If nothing was after begin, then ignore begin.
     *
     * @throws Databaser\Exception
     */
    public function commit(): void
    {
        if ($this->queries
            && end($this->queries)[0] == self::BEGIN
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
     * @throws Databaser\Exception
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
     * @throws Databaser\Exception
     */
    public function queue(array|string ...$queries): void
    {
        foreach (array_keys($queries) as $i) {
            foreach ((array) $queries[$i] as $query) {
                $this->queries[] = [self::REGULAR, $query];
            }
        }

        if (count($this->queries) > 64) {
            $this->execute();
        }
    }

    /**
     * Executing query and return result.
     *
     * @throws Databaser\Exception
     */
    public function query(array|string ...$queries): Databaser\Result|false
    {
        foreach (array_keys($queries) as $i) {
            foreach ((array) $queries[$i] as $query) {
                $this->queries[] = [self::REGULAR, $query];
            }
        }

        return new Databaser\Result($this->execute());
    }

    /**
     * Executing all queued queries.
     *
     * @throws Databaser\Exception
     */
    public function flush(): void
    {
        $this->execute();
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @throws Databaser\Exception
     */
    public function lastInsertId(?string $name = null): string|false
    {
        if (!isset($this->db)) {
            $this->connect();
        }

        return $this->db->lastInsertId($name);
    }

    /**
     * Executing all queued queries and result returning.
     *
     * @throws Databaser\Exception
     */
    protected function execute(): \PDOStatement|false
    {
        if (!$this->queries) {
            return false;
        }

        if (!isset($this->db)) {
            $this->connect();
        }

        foreach ($this->queries as $query) {
            if ($query[0] == self::BEGIN) {
                $this->inTrans = true;
            } elseif (
                   $query[0] == self::COMMIT
                || $query[0] == self::ROLLBACK
            ) {
                $this->inTrans = false;
            }
        }

        $queries = array_column($this->queries, 1);

        $this->queries = [];

        $this->counter += 1;

        $timer = gettimeofday(true);

        try {
            $result = $this->db->query(implode(';', $queries));
        } catch (\PDOException $error) {
            throw new Databaser\Exception($error->errorInfo);
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
     * @throws Databaser\Exception
     */
    public function string(mixed $strings, string $null = 'NULL'): string
    {
        if (!isset($this->db)) {
            $this->connect();
        }

        if (is_scalar($strings)) {
            return $this->db->quote((string) $strings);
        } elseif (is_array($strings)) {
            foreach ($strings as &$value) {
                if (isset($value)) {
                    $value = $this->db->quote((string) $value);
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
