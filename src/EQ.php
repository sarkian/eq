<?php
/**
 * Last Change: 2014 Apr 10, 14:22
 */

define("EQROOT", realpath(__DIR__."/.."));

defined("EQ_DBG") or define("EQ_DBG", true);
defined("EQ_WARNING") or define("EQ_WARNING", true);
defined("EQ_NOTICE") or define("EQ_NOTICE", true);
defined("EQ_DEPRECATED") or define("EQ_DEPRECATED", true);
defined("EQ_STRICT") or define("EQ_STRICT", true);

require_once EQROOT."/src/eq/base/ExceptionBase.php";
require_once EQROOT."/src/eq/base/LoaderException.php";
require_once EQROOT."/src/eq/helpers/Str.php";
require_once EQROOT."/src/eq/base/Loader.php";
require_once EQROOT."/src/eq/base/TObject.php";
require_once EQROOT."/src/eq/base/Object.php";
require_once EQROOT."/src/eq/base/TEvent.php";
require_once EQROOT."/src/eq/base/TAlias.php";
require_once EQROOT."/src/eq/base/ModuleAbstract.php";
require_once EQROOT."/src/eq/base/AppBase.php";


abstract class EQ extends \eq\base\AppBase
{

    public static function init($approot)
    {
        define("APPROOT", realpath($approot));
        self::setAlias("@eq", EQROOT);
        self::setAlias("@app", APPROOT);
        self::setAlias("@runtime", APPROOT.DIRECTORY_SEPARATOR."runtime");
        self::setAlias("@log", APPROOT.DIRECTORY_SEPARATOR."log");
        eq\base\Loader::init([
            EQROOT."/src",
        ], [], APPROOT."/runtime/loader.cache");
        spl_autoload_register(["eq\base\Loader", "loadClass"]);
    }

    public static function powered()
    {
        return "Powered by EQ Framework ".self::version();
    }

    public static function version()
    {
        
    }

}
