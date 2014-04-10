<?php
/**
 * Last Change: 2014 Apr 08, 01:22
 */

$dir = realpath(__DIR__."/..");
$basedir = basename($dir);

return [
    'system' => [
        'app_namespace' => preg_replace("/[^a-zA-Z0-9]/", "_", $basedir),
        'src_dirs' => ["@app/src"],
        'default_timezone' => "UTC",
        'time_offset' => 0,
    ],
    'dev' => [
        'project_name' => $basedir,
        'project_root' => $dir,
    ],
    'components' => [],
    'modules' => [],
    'web' => [
        'content_root' => "@app/../www",
        'route' => ["@app/route.eqrt"],
    ],
    'console' => [],
    'task' => [],
    'debug_override' => [],
];
