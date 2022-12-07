<?php

namespace PruneMazui\MailAddressLiveChecker;

final class MxRecordResolver
{
    private static array $mxRecords = [];

    private function __construct()
    {
    }

    /**
     * @param string $hostname
     *
     * @return array
     */
    public static function resolve(string $hostname): array
    {
        if (array_key_exists($hostname, self::$mxRecords)) {
            return self::$mxRecords[$hostname];
        }

        $host = [];
        if (!getmxrr($hostname, $host)) {
            self::$mxRecords[$hostname] = [];
            return [];
        }

        self::$mxRecords[$hostname] = $host;
        return $host;
    }
}
