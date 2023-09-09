<?php

namespace SFW\Databaser;

/**
 * Exceptions handler.
 */
class Exception extends \Exception
{
    /**
     * Adding driver name and sqlstate.
     */
    public function __construct(
        string $driverName,
        protected string $sqlMessage = 'Unknown error',
        protected string $sqlState = 'HY000'
    ) {
        parent::__construct(
            sprintf("%s: [%s] %s",
                $driverName,
                $this->sqlState,
                $this->sqlMessage
            )
        );
    }

    /**
     * Get sql message.
     */
    public function getSqlMessage(): string
    {
        return $this->sqlMessage;
    }

    /**
     * Get sql state.
     */
    public function getSqlState(): string
    {
        return $this->sqlState;
    }
}
