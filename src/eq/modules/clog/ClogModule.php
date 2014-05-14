<?php
/**
 * Last Change: 2014 May 04, 05:37
 */

namespace eq\modules\clog;

use EQ;
use eq\base\ExceptionBase;
use eq\base\ModuleBase;
use eq\base\TAutobind;
use eq\base\UncaughtExceptionException;
use eq\helpers\Debug;
use eq\helpers\FileSystem;
use eq\php\PhpExceptionBase;

class ClogModule extends ModuleBase
{

    use TAutobind;

    protected $messages = [];
    protected $var_name;
    protected $tmpfname;
    protected $logkey;

    protected $project_root;
    protected $url;

    public function init()
    {
        $this->project_root = realpath(EQ::getAlias(
            $this->config("project_root", "@app")));
        $this->autobind();
    }

    public function __onRequest()
    {
        foreach($this->config("urls_blacklist", ["/favicon.ico", "*.map"]) as $url) {
            if(fnmatch($url, EQ::app()->request->uri))
                return;
        }
        if(!$this->checkKey())
            return;
        if($this->checkLogKey()) {

        } else {
            FileSystem::mkdir("@runtime/clog");
            $this->tmpfname = tempnam(EQ::getAlias("@runtime/clog"), "clog_");
            $this->logkey = basename($this->tmpfname);
            EQ::app()->header("X-EQ-CLog-LogKey", $this->logkey);
            EQ::app()->header("X-EQ-CLog-URL", EQ::app()->createAbsoluteUrl(
                "modules.eq:clog.clog.process", ['key' => $this->logkey]));
        }
    }

    public function __onLog($msg)
    {
        list($file, $line) = Debug::callLocation(4);
        $this->addMsg("log", func_get_args(), $file, $line);
    }

    public function __onWarn($msg)
    {
        list($file, $line) = Debug::callLocation(4);
        $this->addMsg("warn", func_get_args(), $file, $line);
    }

    public function __onErr($msg)
    {
        list($file, $line) = Debug::callLocation(4);
        $this->addMsg("err", func_get_args(), $file, $line);
    }

    public function __onTodo($msg)
    {
        list($file, $line) = Debug::callLocation(4);
        $this->addMsg("warn", "TODO: $msg", $file, $line);
    }

    public function __onFixme($msg)
    {
        list($file, $line) = Debug::callLocation(4);
        $this->addMsg("warn", "FIXME: $msg", $file, $line);
    }

    public function __onDump($var)
    {
        list($file, $line) = Debug::callLocation(4);
        $this->addMsg("dump", func_get_args(), $file, $line);
    }

    public function __onException($e)
    {
        if($e instanceof PhpExceptionBase)
            return;
        $etype = $e instanceof ExceptionBase
            ? $e->getType() : get_class($e);
        if($e instanceof UncaughtExceptionException) {
            $etype .= ": ".get_class($e->getException());
            $file = $e->getException()->getFile();
            $line = $e->getException()->getLine();
        } else {
            $file = $e->getFile();
            $line = $e->getLine();
        }
        $this->addMsg("err", $e->getMessage(),
            $etype.":\n".EQ::unalias($file), $line);
    }

    public function __onError($message, $file, $line)
    {
        $this->addMsg("err", $message,
            "Error:\n".EQ::unalias($file), $line);
        $this->__destruct();
    }

    public function __onWarning($message, $file, $line)
    {
        $this->addMsg("warn", $message,
            "Warning:\n".EQ::unalias($file), $line);
    }

    public function __onDeprecated($message, $file, $line)
    {
        $this->addMsg("warn", $message,
            "Deprecated:\n".EQ::unalias($file), $line);
    }

    public function __onNotice($message, $file, $line)
    {
        $this->addMsg("warn", $message,
            "Notice:\n".EQ::unalias($file), $line);
    }

    public function __onStrict($message, $file, $line)
    {
        $this->addMsg("warn", $message,
            "Strict:\n".EQ::unalias($file), $line);
    }

    public function __onDbQuery($dbname, $query)
    {

    }

    public function getUrlPrefix()
    {
        return $this->config("url_prefix", "/__system__/clog");
    }

    public function __destruct()
    {
        if(!$this->tmpfname)
            return;
        FileSystem::fputs($this->tmpfname, json_encode([
            'url' => EQ::app()->request->uri,
            'messages' => $this->messages,
        ]));
    }

    protected function checkKey()
    {
        return isset($_SERVER['HTTP_X_EQ_CLOG_KEY'])
        && $_SERVER['HTTP_X_EQ_CLOG_KEY'] === $this->config("key", "eqclogkey");
    }

    protected function checkLogKey()
    {
        if(!isset($_SERVER['HTTP_X_EQ_CLOG_LOGKEY']))
            return false;
        $this->logkey = preg_replace("/[^a-zA-Z0-9_]/", "",
            $_SERVER['HTTP_X_EQ_CLOG_LOGKEY']);
        if($this->logkey &&
            file_exists(EQ::getAlias("@runtime/clog/".$this->logkey))
        )
            return true;
        return false;
    }

    public function addMsg($type, $msg, $file = null, $line = null)
    {
        if(!is_array($msg))
            $msg = [$msg];
        if(!$file)
            list($file, $line) = Debug::callLocation(2);
        ob_start();
        foreach($msg as $m) {
            if($type === "dump")
                var_dump($m);
            else
                print_r($m);
            echo "\n";
        }
        $msg_r = substr(ob_get_clean(), 0, -1);
        ob_start();
        var_dump(count($msg) == 1 ? $msg[0] : $msg);
        $msg_d = substr(ob_get_clean(), 0, -1);
        if($type === "dump")
            $type = "log";
        $this->messages[] = [
            'type' => $type,
            'file' => EQ::unalias($file).":".$line,
            'message' => $msg,
            'message_r' => $msg_r,
            'message_d' => $msg_d,
        ];
    }

}
