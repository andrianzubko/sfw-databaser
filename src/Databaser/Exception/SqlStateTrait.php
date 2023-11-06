<?php

declare(strict_types=1);

namespace SFW\Databaser\Exception;

trait SqlStateTrait
{
    /**
     * Code which identifies SQL error condition.
     */
    protected string $sqlState = 'HY000';

    /**
     * Adds sqlstate to message.
     */
    public function addSqlStateToMessage(): self
    {
        $this->message = "[$this->sqlState] $this->message";

        return $this;
    }

    /**
     * Sets sqlstate.
     */
    public function setSqlState(string $sqlState): self
    {
        $this->sqlState = $sqlState;

        return $this;
    }

    /**
     * Gets sqlstate.
     */
    public function getSqlState(): string
    {
        return $this->sqlState;
    }
}
