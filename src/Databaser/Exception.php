<?php

namespace SFW\Databaser;

/**
 * Exceptions handler.
 */
class Exception extends \Exception
{
    /**
     * Sql state.
     */
    protected string $sqlState = 'HY000';

    /**
     * Sql message.
     */
    protected string $sqlMessage = 'Unknown error';

    /**
     * Adding sqlstate and correct file and line.
     */
    public function __construct(array $errorInfo)
    {
        if (isset($errorInfo[0])) {
            $this->sqlState = $errorInfo[0];
        }

        if (isset($errorInfo[1])) {
            $this->code = $errorInfo[1];
        }

        if (isset($errorInfo[2])) {
            $this->sqlMessage = $errorInfo[2];
        }

        parent::__construct(
            sprintf("Databaser: [%s] %s",
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
     * Get sql state.
     */
    public function getSqlState(): string
    {
        return $this->sqlState;
    }

    /**
     * Get sql message.
     */
    public function getSqlMessage(): string
    {
        return $this->sqlMessage;
    }
}
