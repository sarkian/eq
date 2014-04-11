#!/usr/bin/env php
<?php
/**
 * Last Change: 2014 Apr 10, 21:47
 */

$res = exec("git log -1 --pretty=format:'%h - %s (%ci)'");
var_dump($res);
