#!/usr/bin/env php
<?php
/**
 * Last Change: 2013 Nov 08, 08:17
 */

$fname = "/home/sarkian/src/phpunit/PHPUnit/Framework/Assert.php";
require_once $fname;
$fcode = file($fname);

$class = new ReflectionClass("PHPUnit_Framework_Assert");
$methods = $class->getMethods();
$rescode = [];
foreach($methods as $method) {
    if(!preg_match("/^assert/", $method->name))
        continue;
    $defstr = "    ".$method->getDocComment();
    $defline = $method->getStartLine();
    $line = $fcode[$defline - 1];
    $args = preg_replace("/^[^\(]*\(|\)[^\)]*$/", "", $line);
    $defstr .= "\n    public function ".$method->name."( ".$args." ) {}";
    $rescode[] = $defstr;
}

echo implode("\n\n", $rescode);
