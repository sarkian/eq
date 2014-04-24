<?php
/**
 * Last Change: 2014 Apr 24, 04:52
 */

namespace eq\modules\clog;

use EQ;
use eq\helpers\Arr;
use eq\helpers\Str;
use eq\helpers\FileSystem;

class ClogModule extends \eq\base\ModuleBase
{

    use \eq\base\TAutobind;

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
        EQ::app()->registerStaticMethod("clog", function() {
            list($file, $line) = $this->callLocation(4);
            $this->addMsg("log", func_get_args(), $file, $line);
        });
        $this->autobind();
    }

    public function __onRequest()
    {
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
                "modules.clog.clog.process", ['key' => $this->logkey]));
        }
    }

    public function __onException($e)
    {
        if($e instanceof \eq\php\PhpExceptionBase)
            return;
        $etype = $e instanceof \eq\base\ExceptionBase
            ? $e->getType() : get_class($e);
        if($e instanceof \eq\base\UncaughtExceptionException) {
            $etype .= ": ".get_class($e->getException());
            $file = $e->getException()->getFile();
            $line = $e->getException()->getLine();
        }
        else {
            $file = $e->getFile();
            $line = $e->getLine();
        }
        $this->addMsg("err", $e->getMessage(), 
            $etype.":\n".$this->relativePath($file), $line);
    }

    public function __onError($message, $file, $line)
    {
        $this->addMsg("err", $message,
            "Error:\n".$this->relativePath($file), $line);
        $this->__destruct();
    }

    public function __onWarning($message, $file, $line)
    {
        $this->addMsg("warn", $message,
            "Warning:\n".$this->relativePath($file), $line);
    }

    public function __onDeprecated($message, $file, $line)
    {
        $this->addMsg("warn", $message,
            "Deprecated:\n".$this->relativePath($file), $line);
    }

    public function __onNotice($message, $file, $line)
    {
        $this->addMsg("warn", $message,
            "Notice:\n".$this->relativePath($file), $line);
    }

    public function __onStrict($message, $file, $line)
    {
        $this->addMsg("warn", $message,
            "Strict:\n".$this->relativePath($file), $line);
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
        $this->logkey = preg_replace("/[^a-zA-Z0-9]/", "", 
            $_SERVER['HTTP_X_EQ_CLOG_LOGKEY']);
        if($this->logkey && 
                file_exists(EQ::getAlias("@runtime/clog/".$this->logkey)))
            return true;
        return false;
    }

    public function addMsg($type, $msg, $file = null, $line = null)
    {
        if(!is_array($msg))
            $msg = [$msg];
        if(!$file)
            list($file, $line) = $this->callLocation();
        ob_start();
        foreach($msg as $m) {
            print_r($m);
            echo "\n";
        }
        $msg_r = substr(ob_get_clean(), 0, -1);
        ob_start();
        var_dump(count($msg) == 1 ? $msg[0] : $msg);
        $msg_d = substr(ob_get_clean(), 0, -1);
        $this->messages[] = [
            'type' => $type,
            'file' => $this->relativePath($file).":".$line,
            'message' => $msg,
            'message_r' => $msg_r,
            'message_d' => $msg_d,
        ];
    }

    protected function callLocation($skip = 2)
    {
        $trace = debug_backtrace();
        $file = "";
        $line = 0;
        if(isset($trace[$skip]['file'])) {
            $file = $trace[$skip]['file'];
            $line = $trace[$skip]['line'];
        }
        else {
            foreach(array_reverse($trace) as $call) {
                if(isset($call['file'])) {
                    $file = $call['file'];
                    $line = $call['line'];
                    break;
                }
            }
        }
        return [$file, $line];
    }

    protected function relativePath($file)
    {
        return preg_replace(
            "/^".preg_quote($this->project_root, "/")."\//", "", $file
        );
    }

    protected function createIdeLink($file, $line = 1)
    {
        
    }

}
