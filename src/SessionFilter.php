<?php

namespace Goudenvis\SessionFilter;

class Filter
{
    protected static $search = 'search';

    /**
     * @param $key
     * @return bool
     *
     * Check if the session contains the requested key
     *
     */

    protected static function hasFilter($key): bool
    {
        return (
            !empty(session(self::$search)) &&
            array_key_exists($key, session(self::$search))
        );
    }

    /**
     * @param $key
     * @return array
     *
     * Get the data from the session. Even if it is not set
     *
     */

    public static function getFilter($key): array
    {
        if ( self::hasFilter($key) )
        {
            return session(self::$search)[$key];
        }
        return [];
    }

    public static function setFilter($key, $data): void
    {
        session(self::$search . $key, $data);
    }
}
