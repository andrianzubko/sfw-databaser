<?php

namespace SFW;

/**
 * Database overlay.
 */
class Databaser
{
    /**
     * Available drivers.
     */
    protected static array $drivers = ['PgSQL','MySQL'];

    /**
     * Database initialization.
     */
    public static function init(string $driver, array $options = [], mixed $profiler = null): Database\Driver
    {
        if (!in_array($driver, self::$drivers, true)) {
            throw new Databaser\Exception(
                sprintf('Available drivers: %s', implode(', ', self::$drivers))
            );
        }

        return new ("SFW\\Databaser\\$driver")($options, $profiler);
    }
}
