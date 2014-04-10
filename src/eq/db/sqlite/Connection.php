<?php
/**
 * Last Change: 2014 Mar 15, 12:29
 */

namespace eq\db\sqlite;

use PDO;

class Connection extends \eq\db\ConnectionBase
{

    protected $driver = "sqlite";

    protected function createDSN()
    {
        
    }

}
