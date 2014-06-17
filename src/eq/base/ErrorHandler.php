<?php


namespace eq\base;

use EQ;

use \eq\php\ErrorException;
use \eq\php\NoticeException;
use \eq\php\WarningException;
use \eq\php\DeprecatedException;
use \eq\php\StrictException;
use \eq\UncaughtExceptionException;

class ErrorHandler
{

    private static $errno;
    private static $message;
    private static $file;
    private static $line;
    private static $context;

    public static function register()
    {
        $cname = get_called_class();
        set_error_handler([$cname, "onError"]);
        set_exception_handler([$cname, "onException"]);
        register_shutdown_function([$cname, "onShutdown"]);
        ini_set("display_errors", 0);
    }

    public static function onError($errno, $message, $file, $line, $context)
    {
        restore_error_handler();
        // if(!EQ_DBG) return;
        self::$errno = $errno;
        self::$message = $message;
        self::$file = $file;
        self::$line = $line;
        self::$context = $context;
        if(ini_get("error_reporting") == 0)
            return;
        switch($errno) {
            case E_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
            case E_PARSE:
                EQ::app()->trigger("error", $message, $file, $line);
                self::throwError();
                break;
            case E_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                EQ::app()->trigger("warning", $message, $file, $line);
                if(EQ_WARNING)
                    self::throwWarning();
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                EQ::app()->trigger("deprecated", $message, $file, $line);
                if(EQ_DEPRECATED)
                    self::throwDeprecated();
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                EQ::app()->trigger("notice", $message, $file, $line);
                if(EQ_NOTICE)
                    self::throwNotice();
                break;
            case E_STRICT:
                EQ::app()->trigger("strict", $message, $file, $line);
                if(EQ_STRICT)
                    self::throwStrict();
                break;
            default:
                self::throwError();
        }
    }

    public static function onException(\Exception $e)
    {
        restore_exception_handler();
        if(EQ::app()) {
            EQ::app()->trigger("exception", $e);
            EQ::app()->processUncaughtException($e);
        }
        else
            echo "Uncaught exception '".get_class($e)."' in "
                .$e->getFile().":".$e->getLine()."\n".$e->getTraceAsString();
    }

    public static function onShutdown()
    {
        $err = error_get_last();
        if($err['type'] === E_ERROR) {
            if(\EQ::app()) {
                \EQ::app()->trigger("error", $err['message'], $err['file'], $err['line']);
                \EQ::app()->processFatalError($err);
            }
            else
                echo "Fatal Error: {$err['message']} in "
                    ."{$err['file']} on line {$err['line']}\n";
        }
    }

    private static function throwError()
    {
        throw new ErrorException(self::$errno, self::$message,
            self::$file, self::$line, self::$context);
    }

    private static function throwNotice()
    {
        throw new NoticeException(self::$errno, self::$message,
            self::$file, self::$line, self::$context);
    }

    private static function throwWarning()
    {
        throw new WarningException(self::$errno, self::$message,
            self::$file, self::$line, self::$context);
    }

    private static function throwDeprecated()
    {
        throw new DeprecatedException(self::$errno, self::$message,
            self::$file, self::$line, self::$context);
    }

    private static function throwStrict()
    {
        throw new StrictException(self::$errno, self::$message,
            self::$file, self::$line, self::$context);
    }
    
}
