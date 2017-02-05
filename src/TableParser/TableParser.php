<?php

namespace Maghead\TableParser;

use PDO;
use Exception;
use SQLBuilder\Driver\BaseDriver;

class TableParser
{
    public static function create(PDO $connection, BaseDriver $driver)
    {
        $class = 'Maghead\\TableParser\\'.ucfirst($driver->getDriverName()).'TableParser';
        if (!class_exists($class, true)) {
            throw new Exception("parser driver does not support {$driver->getDriverName()} currently.");
        }
        return new $class($connection, $driver);
    }
}
