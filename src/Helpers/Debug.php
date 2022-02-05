<?php
/*
 * Debug.php
 *
 * Created on Tue Feb 01 2022 13:56:27
 *
 * PHP Version 8.1.2
 *
 * @package      NoName
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 */

namespace Lukieer\TS3Query\Helpers;

/**
 * @class Debug
 * @brief Manage debug logs
 */
class Debug {

    /**
     * Stores a logs
     *
     * @var array
     */
    private static $logs = array();
    public static $enable = false;

    /**
     * addDebugLog
     *
     * @param string $log
     * @return boolean
     */
    public static function addDebugLog(string $log): bool
    {
        if(self::$enable)
        {
            self::$logs[] = $log;
            return true;
        }
        return false;
    }

    /**
     * getDebugLogs
     *
     * @return array
     */
    public static function getDebugLogs(): array
    {
        return self::$logs;
    }
}