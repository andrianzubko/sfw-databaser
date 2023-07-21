<?php

namespace SFW\Databaser;

/**
 * Exceptions handler.
 */
class Exception extends \Exception
{
    /**
     * Adding sqlstate and correct file and line.
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

        foreach ($this->getTrace() as $trace) {
            if (!str_starts_with($trace['file'], dirname(__DIR__))) {
                $this->file = $trace['file'];

                $this->line = $trace['line'];

                break;
            }
        }
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
