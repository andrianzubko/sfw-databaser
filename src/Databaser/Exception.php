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
    public function __construct(string $message, protected string $state = 'HY000')
    {
        parent::__construct(
            sprintf('[%s] %s', $this->state, $message)
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
     * Returning sqlstate.
     */
    public function getSqlState(): string
    {
        return $this->state;
    }
}
