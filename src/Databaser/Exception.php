<?php

namespace SFW\Databaser;

interface Exception extends \SFW\Exception
{
    /**
     * Adds sqlstate to message.
     */
    public function addSqlStateToMessage(): self;

    /**
     * Sets sqlstate.
     */
    public function setSqlState(string $sqlState): self;

    /**
     * Gets sqlstate.
     */
    public function getSqlState(): string;
}
