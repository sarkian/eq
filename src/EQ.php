<?php

define("OS_WIN", strstr(PHP_OS, "WIN") ? true : false);

define("EQROOT", realpath(__DIR__."/.."));

defined("EQ_RECOVERY") or define("EQ_RECOVERY", false);

defined("EQ_DBG") or define("EQ_DBG", true);
defined("EQ_WARNING") or define("EQ_WARNING", EQ_DBG);
defined("EQ_NOTICE") or define("EQ_NOTICE", EQ_DBG);
defined("EQ_DEPRECATED") or define("EQ_DEPRECATED", EQ_DBG);
defined("EQ_STRICT") or define("EQ_STRICT", EQ_DBG);

require_once EQROOT."/src/eq/base/ExceptionBase.php";
require_once EQROOT."/src/eq/base/LoaderException.php";
require_once EQROOT."/src/eq/helpers/Str.php";
require_once EQROOT."/src/eq/helpers/FileSystem.php";
require_once EQROOT."/src/eq/base/Loader.php";
require_once EQROOT."/src/eq/base/TObject.php";
require_once EQROOT."/src/eq/base/Object.php";
require_once EQROOT."/src/eq/base/TEvent.php";
require_once EQROOT."/src/eq/base/TAlias.php";
require_once EQROOT."/src/eq/base/ModuleAbstract.php";
require_once EQROOT."/src/eq/base/AppBase.php";

require_once EQROOT."/src/eq/base/StaticComponent.php";
require_once EQROOT."/src/eq/base/CacheObject.php";
require_once EQROOT."/src/eq/base/Cache.php";


abstract class EQ extends \eq\base\AppBase
{

    public static function init($approot)
    {
        define("APPROOT", realpath($approot));
        self::setAlias("@eq", EQROOT);
        self::setAlias("@eqsrc", EQROOT.DIRECTORY_SEPARATOR."src");
        self::setAlias("@app", APPROOT);
        self::setAlias("@runtime", APPROOT.DIRECTORY_SEPARATOR."runtime");
        self::setAlias("@log", APPROOT.DIRECTORY_SEPARATOR."log");
        self::setAlias("@data", APPROOT.DIRECTORY_SEPARATOR."data");
        eq\base\Loader::init([
            EQROOT."/src",
        ], [], APPROOT."/runtime/loader.cache");
        spl_autoload_register(['eq\base\Loader', "loadClass"]);
    }

}
