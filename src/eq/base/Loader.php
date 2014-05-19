<?php

namespace eq\base;

use EQ;
use eq\helpers\Str;

// TODO херня этот кэш
class Loader
{

    protected static $dirs = [];
    protected static $ns_dirs = [];
    protected static $cache_file;
    protected static $ns_regexp = [];
    protected static $cache = [];

    public static function init($dirs = [], $ns_dirs = [], $cache_file = null)
    {
        self::$dirs = $dirs;
        self::$ns_dirs = $ns_dirs;
        self::$cache_file = $cache_file;
        if($cache_file && file_exists($cache_file))
            self::cacheLoad();
    }

    public static function addDir($dir)
    {
        self::$dirs[] = $dir;
    }

    public static function dirs()
    {
        return self::$dirs;
    }

    public static function addNSDir($ns, $dir)
    {
        self::$ns_dirs[$ns] = $dir;
    }

    public static function loadClass($cname)
    {
        $fname = self::findClass($cname);
        if($fname)
            require_once $fname;
        else {
            if(self::cacheClassExists($cname))
                self::cacheRemoveClass($cname);
            throw new LoaderException("Class not found: ".$cname);
        }
    }

    public static function classExists($cname)
    {
        if(class_exists($cname, false))
            return true;
        $fname = self::findClass($cname);
        if(!$fname)
            return false;
        require_once $fname;
        if(!class_exists($cname, false))
            return false;
        self::$cache[$cname] = $fname;
        return true;
    }

    public static function classLocation($cname)
    {
        return isset(self::$cache[$cname]) ? self::$cache[$cname] : null;
    }

    public static function autofindClass($name, $ns_prefix,
                                         $postfix = null, $namespaces = [])
    {
        if(!strncmp($name, '\\', 1))
            return self::classExists($name) ? $name : false;
        if(is_null($postfix)) {
            $postfix = substr_compare($ns_prefix, "s", -1)
                ? $postfix : Str::var2method(substr($ns_prefix, 0, -1));
        }
        $namespaces = array_unique(array_merge([
            EQ::app()->app_namespace, "eq"
        ], $namespaces));
        $parts = explode('\\', trim(str_replace(".", '\\', $name), '.\\'));
        array_push($parts, Str::cmdvar2method(array_pop($parts)).$postfix);
        $cparts = $parts;
        $ns = array_shift($cparts);
        array_unshift($cparts, $ns_prefix);
        $cname = $ns.'\\'.implode('\\', $cparts);
        if(self::classExists($cname))
            return $cname;
        array_unshift($parts, $ns_prefix);
        $cbasename = implode('\\', $parts);
        foreach($namespaces as $ns) {
            $cname = trim($ns, '\\').'\\'.$cbasename;
            if(self::classExists($cname))
                return $cname;
        }
        return false;
    }

    protected static function findClass($cname)
    {
        if(self::cacheClassExists($cname)) {
            $fname = self::cacheGetClass($cname);
            if(file_exists($fname))
                return $fname;
        }
        $fname_rel = str_replace("\\", DIRECTORY_SEPARATOR, $cname).".php";
        foreach(self::$dirs as $dir) {
            $fname = $dir.DIRECTORY_SEPARATOR.$fname_rel;
            if(file_exists($fname)) {
                self::cacheAddClass($cname, $fname);
                return $fname;
            }
        }
        foreach(self::$ns_dirs as $ns => $dir) {
            $exp = self::createNSRegexp($ns);
            if(preg_match($exp, $cname)) {
                $fname_rel = str_replace("\\", DIRECTORY_SEPARATOR,
                        preg_replace($exp, "", $cname)).".php";
                $fname = $dir.DIRECTORY_SEPARATOR.$fname_rel;
                if(file_exists($fname)) {
                    self::cacheAddClass($cname, $fname);
                    return $fname;
                }
            }
        }
        return false;
    }

    protected static function createNSRegexp($ns)
    {
        if(isset(self::$ns_regexp[$ns]))
            return self::$ns_regexp[$ns];
        $exp = "/^".preg_quote($ns, "/")."\\\*/";
        self::$ns_regexp[$ns] = $exp;
        return $exp;
    }

    protected static function cacheClassExists($cname)
    {
        return isset(self::$cache[$cname]);
    }

    protected static function cacheGetClass($cname)
    {
        return self::$cache[$cname];
    }

    protected static function cacheAddClass($cname, $fname)
    {
        self::$cache[$cname] = $fname;
        self::cacheSave();
    }

    protected static function cacheRemoveClass($cname)
    {
        unset(self::$cache[$cname]);
        self::cacheSave();
    }

    // TODO use php array (?)
    protected static function cacheLoad()
    {
        if(!self::$cache_file)
            return;
        $lines = file(self::$cache_file,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $cname = "";
        foreach($lines as $line) {
            if($cname) {
                self::$cache[$cname] = $line;
                $cname = "";
            } else
                $cname = $line;
        }
    }

    protected static function cacheSave()
    {
        if(!self::$cache_file)
            return;
        $file = @fopen(self::$cache_file, "w");
        if($file === false)
            return;
        foreach(self::$cache as $cname => $fname)
            fwrite($file, $cname."\n".$fname."\n");
        fclose($file);
        if(fileperms(self::$cache_file) !== 0664)
            @chmod(self::$cache_file, 0664);
    }

}
