<?php
/**
 * Last Change: 2014 Apr 11, 15:50
 */

namespace eq\helpers;

class Git
{

    protected static $binary = null;

    public static function test()
    {
        var_dump(self::findBinary());
    }

    public static function isRepo()
    {
        return is_dir(EQROOT."/.git");
    }

    public static function lastCommit()
    {
        if(!self::isRepo() || !self::findBinary())
            return "";
        chdir(EQROOT);
        $res = exec(self::$binary
            ." log -1 --pretty=format:'%h - %s (%cd)' --date=short", $out, $ret);
        return $ret === 0 ? $res : "";
    }

    public static function findBinary()
    {
        if(is_null(self::$binary)) {
            $cmd = OS_WIN ? "where" : "which";
            $res = exec("$cmd git", $out, $ret);
            self::$binary = $ret === 0 ? $res : false;
        }
        return self::$binary;
    }

}
