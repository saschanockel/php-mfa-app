<?php

namespace saschanockel\PhpMfaApp\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class Database
{
    public static function getEntityManager(): EntityManager
    {
        $paths = array('/var/www/html/src/Entities');

        // the connection configuration
        $dbParams = array(
            'driver' => 'mysqli',
            'user' => getenv('DATABASE_USER'),
            'password' => getenv('DATABASE_PASSWORD'),
            'dbname' => getenv('DATABASE_NAME'),
            'host' => getenv('DATABASE_HOST'),
            'port' => getenv('DATABASE_PORT')
        );

        $config = Setup::createAnnotationMetadataConfiguration($paths, true);
        return EntityManager::create($dbParams, $config);
    }
}