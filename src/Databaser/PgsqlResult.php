<?php

namespace SFW\Databaser;

/**
 * PostgreSQL result handling.
 */
class PgsqlResult
{
    /**
     * Result object overlaying.
     */
    public function __construct(protected \PgSql\Result $result) {}

    /**
     * Calling functions as methods.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return ("pg_$name")($this->result, ...$arguments);
    }
}
