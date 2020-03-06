<?php

namespace Schemata;

use RuntimeException;

class Schemata
{
    public const CLASS_SCHEMA = 1;
    public const CLASS_TYPE = 2;
    public const CLASS_FIELD = 3;
    private static $classMap = [
        self::CLASS_SCHEMA => 'SCHEMA',
        self::CLASS_TYPE => 'TYPE',
        self::CLASS_FIELD => 'FIELD',
    ];

    public static function classNameToId(string $key): int
    {
        $key = trim(strtoupper($key));
        $map = array_flip(self::$classMap);
        if (isset($map[$key])) {
            return $map[$key];
        }
        throw new RuntimeException("Can't map className: " . $key);
    }

    public static function classIdToName(int $key): string
    {
        $map = self::$classMap;
        if (isset($map[$key])) {
            return $map[$key];
        }
        throw new RuntimeException("Can't map classId: " . $key);
    }
}
