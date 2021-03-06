<?php

namespace eq\modules\clog;

use EQ;
use eq\base\ExceptionBase;
use eq\base\ModuleBase;
use eq\base\TAutobind;
use eq\base\UncaughtExceptionException;
use eq\helpers\C;
use eq\helpers\Debug;
use eq\helpers\FileSystem;
use eq\php\PhpExceptionBase;

class ClogModule extends ModuleBase
{

    use TAutobind;

    protected $title = "EQ Clog";
    protected $description = [
        'ru_RU' => "Логгер",
        'en_US' => "Logger",
    ];

    protected $messages = [];
    protected $var_name;
    protected $tmpfname;
    protected $logkey;

    protected $project_root;
    protected $url;

    public function configDefaults()
    {
        return [
            'project_root' => "@app",
            'retrigger' => false,
            'urls_blacklist' => ["/favicon.ico", "*.map"],
            'write_db_queries' => false,
            'url_prefix' => "/__system__/clog",
            'key' => "eqclogkey",
            'task_log_format' => "plain",
        ];
    }

    protected function init()
    {
        $this->project_root = realpath(EQ::getAlias(
            $this->config("project_root")));
        $this->autobind();
        if($this->config("retrigger")) {
            EQ::app()->retrigger("log", [$this, "__onLog"]);
            EQ::app()->retrigger("warn", [$this, "__onWarn"]);
            EQ::app()->retrigger("err", [$this, "__onErr"]);
            EQ::app()->retrigger("todo", [$this, "__onTodo"]);
            EQ::app()->retrigger("fixme", [$this, "__onFixme"]);
            EQ::app()->retrigger("dump", [$this, "__onDump"]);
            EQ::app()->retrigger("exception", [$this, "__onException"]);
            EQ::app()->retrigger("error", [$this, "__onError"]);
            EQ::app()->retrigger("warning", [$this, "__onWarning"]);
            EQ::app()->retrigger("deprecated", [$this, "__onDeprecated"]);
            EQ::app()->retrigger("notice", [$this, "__onNotice"]);
            EQ::app()->retrigger("strict", [$this, "__onStrict"]);
            EQ::app()->retrigger("dbQuery", [$this, "__onDbQuery"]);
        }
    }

    public function webInit()
    {
        $this->onRequest();
    }

    public function onRequest()
    {
        foreach($this->config("urls_blacklist") as $url) {
            if(fnmatch($url, EQ::app()->request->uri))
                return;
        }
        if(!$this->checkKey())
            return;
        if($this->checkLogKey()) {

        }
        else {
            FileSystem::mkdir("@runtime/clog");
            $this->tmpfname = tempnam(EQ::getAlias("@runtime/clog"), "clog_");
            $this->logkey = basename($this->tmpfname);
            EQ::app()->header("X-EQ-CLog-LogKey", $this->logkey);
            EQ::app()->header("X-EQ-CLog-URL", EQ::app()->createAbsoluteUrl(
                "modules.eq:clog.clog.process", ['key' => $this->logkey], ["EQ_RECOVERY"]));
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
        $this->addPhpMsg("Error", "err", $message, $file, $line);
        $this->__destruct();
    }

    public function __onWarning($message, $file, $line)
    {
        $this->addPhpMsg("Warning", "warn", $message, $file, $line);
    }

    public function __onDeprecated($message, $file, $line)
    {
        $this->addPhpMsg("Deprecated", "warn", $message, $file, $line);
    }

    public function __onNotice($message, $file, $line)
    {
        $this->addPhpMsg("Notice", "warn", $message, $file, $line);
    }

    public function __onStrict($message, $file, $line)
    {
        $this->addPhpMsg("Strict", "warn", $message, $file, $line);
    }

    public function __onDbQuery($dbname, $query)
    {
        if(!$this->config("write_db_queries"))
            return;
        list($file, $line) = Debug::callLocation(4);
        $this->addMsg("log", "Query to DB '$dbname': $query", $file, $line);
    }

    public function getUrlPrefix()
    {
        return $this->config("url_prefix");
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
        && $_SERVER['HTTP_X_EQ_CLOG_KEY'] === $this->config("key");
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

    protected function addPhpMsg($php_type, $type, $msg, $file, $line)
    {
        $file = EQ::unalias($file);
        if(EQ::app()->type === "web")
            $file = "$php_type\n$file";
        else
            $msg = "$php_type: $msg";
        $this->addMsg($type, $msg, $file, $line);
    }

    public function addMsg($type, $msg, $file = null, $line = null)
    {
        if(!is_array($msg))
            $msg = [$msg];
        if(!$file)
            list($file, $line) = Debug::callLocation(1);
        switch(EQ::app()->type) {
            case "web":
                $this->webAddMsg($type, $msg, $file, $line);
                break;
            case "task":
                $format = strtolower($this->config("task_log_format"));
                if($format === "json")
                    $this->jsonAddMsg($type, $msg, $file, $line);
                elseif($format === "console" || $format === "terminal" || $format === "term")
                    $this->consoleAddMsg($type, $msg, $file, $line);
                elseif($format === "html")
                    $this->htmlAddMsg($type, $msg, $file, $line);
                else
                    $this->plainAddMsg($type, $msg, $file, $line);
                break;
            default:
                $this->consoleAddMsg($type, $msg, $file, $line);
        }
    }

    protected function webAddMsg($type, array $msg, $file, $line)
    {
        $msg_r = $this->sPrintMsg($type, $msg);
        ob_start();
        var_dump(count($msg) == 1 ? $msg[0] : $msg);
        $msg_d = substr(ob_get_clean(), 0, -1);
        $this->messages[] = [
            'type' => $type === "dump" ? "log" : $type,
            'file' => EQ::unalias($file).":".$line,
            'message' => $type === "dump" ? $msg_d : $msg,
            'message_r' => $msg_r,
            'message_d' => $msg_d,
        ];
    }

    protected function jsonAddMsg($type, array $msg, $file, $line)
    {

    }

    protected function plainAddMsg($type, array $msg, $file, $line)
    {

    }

    protected function htmlAddMsg($type, array $msg, $file, $line)
    {

    }

    protected function consoleAddMsg($type, array $msg, $file, $line)
    {
        $fg = C::FG_DEFAULT;
        $fm = null;
        switch($type) {
            case "log":
                $fg = C::FG_CYAN;
                break;
            case "warn":
                $fg = C::FG_YELLOW;
                break;
            case "err":
                $fg = C::FG_RED;
                $fm = C::BOLD;
                break;
        }
        $lfg = $type === "dump" ? C::FG_BLUE : $fg;
        $location = C::fmt("[$file:$line]", $lfg, $fm, C::UNDERLINE);
        $m = array_shift($msg);
        if(!count($msg) && is_scalar($m)) {
            $message = $location." ".C::fmt($m, $fg, $fm);
        }
        else {
            array_unshift($msg, $m);
            $message = preg_split("/\r|\n|\r\n|\n\r/", $this->sPrintMsg($type, $msg));
            array_pop($message);
            array_walk($message, function(&$line) use($fg, $lfg) {
                $line = C::fmt("┃", $lfg)."   ".C::fmt($line, $fg);
            });
            $message = $location."\n".implode("\n", $message);
        }
        if($type === "err" || $type === "warn")
            C::stderr($message);
        else
            C::stdout($message);
    }

    protected function sPrintMsg($type, $msg)
    {
        ob_start();
        foreach($msg as $m) {
            if($type === "dump")
                var_dump($m);
            else
                print_r($m);
            echo "\n";
        }
        return substr(ob_get_clean(), 0, -1);
    }

}
