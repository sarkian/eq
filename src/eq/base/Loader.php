<?php

namespace eq\base;

use EQ;
use eq\helpers\Str;

class Loader
{

    protected static $dirs = [];
    protected static $ns_dirs = [];
    protected static $cache_file;
    protected static $ns_regexp = [];

    /**
     * @var CacheObject
     */
    protected static $fcache;

    /**
     * @var CacheObject
     */
    protected static $afcache;

    public static function init($dirs = [], $ns_dirs = [], $cache_file = null)
    {
        self::$dirs = $dirs;
        self::$ns_dirs = $ns_dirs;
        self::$fcache = Cache::inst()->get("loader.files");
        self::$afcache = Cache::inst()->get("loader.autofind");
        if(defined("APPROOT") && file_exists(APPROOT."/vendor/autoload.php"))
            require_once APPROOT."/vendor/autoload.php";
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
            self::$fcache->unsetValue($cname);
            throw new LoaderException("Class not found: ".$cname);
        }
    }

    public static function classExists($cname)
    {
        if(class_exists($cname, false)
            || interface_exists($cname, false) || trait_exists($cname, false)
        )
            return true;
        $fname = self::findClass($cname);
        if(!$fname)
            return false;
        require_once $fname;
        if(!class_exists($cname, false)
            && !interface_exists($cname, false) && !trait_exists($cname, false)
        )
            return false;
        self::$fcache->setValue($cname, $fname);
        return true;
    }

    public static function classLocation($cname)
    {
        return self::$fcache->getValue($cname);
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
        $key = "$name:$ns_prefix:$postfix:".implode(",", $namespaces);
        $cname = self::$afcache->getValue($key);
        if($cname && self::classExists($cname))
            return $cname;
        $namespaces = array_unique(array_merge([
            EQ::app()->app_namespace, "eq"
        ], $namespaces));
        $parts = explode('\\', trim(str_replace(".", '\\', $name), '.\\'));
        array_push($parts, Str::cmdvar2method(array_pop($parts)).$postfix);
        $cparts = $parts;
        $ns = array_shift($cparts);
        array_unshift($cparts, $ns_prefix);
        $cname = $ns.'\\'.implode('\\', $cparts);
        if(self::classExists($cname)) {
            self::$afcache->setValue($key, $cname);
            return $cname;
        }
        array_unshift($parts, $ns_prefix);
        $cbasename = implode('\\', $parts);
        foreach($namespaces as $ns) {
            $cname = trim($ns, '\\').'\\'.$cbasename;
            if(self::classExists($cname)) {
                self::$afcache->setValue($key, $cname);
                return $cname;
            }
        }
        return false;
    }

    protected static function findClass($cname)
    {
        if(self::$fcache->valueExists($cname)) {
            $fname = self::$fcache->getValue($cname);
            if(file_exists($fname))
                return $fname;
        }
        $fname_rel = str_replace("\\", DIRECTORY_SEPARATOR, $cname).".php";
        foreach(self::$dirs as $dir) {
            $fname = $dir.DIRECTORY_SEPARATOR.$fname_rel;
            if(file_exists($fname)) {
                self::$fcache->setValue($cname, $fname);
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
                    self::$fcache->setValue($cname, $fname);
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
        $exp = "/^".preg_quote($ns, "/").'\\\*/';
        self::$ns_regexp[$ns] = $exp;
        return $exp;
    }

}
