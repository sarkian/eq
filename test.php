#!/usr/bin/env php
<?php
/**
 * Last Change: 2014 Apr 14, 21:57
 */

class Test
{

    public function __construct()
    {
         
    }

}

array_shift($argv);
foreach($argv as $arg) {
    echo "    [$arg]\n";
}
echo "\n";
