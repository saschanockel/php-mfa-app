<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use saschanockel\PhpMfaApp\Services\Database;


return ConsoleRunner::createHelperSet(Database::getEntityManager());
