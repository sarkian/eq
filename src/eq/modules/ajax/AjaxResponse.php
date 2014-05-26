<?php

namespace eq\modules\ajax;

use EQ;

final class AjaxResponse
{

    const RET_MESSAGE = 1;
    const RET_DATA = 2;

    private static $_instance = null;

    protected $success = true;
    protected $message = null;
    protected $warnings = [];
    protected $data = null;
    protected $raw = false;
    protected $raw_contents = null;
    protected $ret = self::RET_DATA;

    public static function instance()
    {
        if(!self::$_instance)
            self::$_instance = new AjaxResponse();
        return self::$_instance;
    }

    private final function __construct()
    {

    }

    public function returnMessage()
    {
        $this->ret = self::RET_MESSAGE;
        return $this;
    }

    public function returnData()
    {
        $this->ret = self::RET_DATA;
        return $this;
    }

    public function setSuccess($value)
    {
        $this->success = $value;
        return $this;
    }

    public function setMessage($message)
    {
        if(is_string($message) && $message)
            $this->message = $message;
        return $this;
    }

    public function error($message = null, $leave_data = false, $leave_warnings = false)
    {
        $this->success = false;
        $this->message = is_string($message) && $message ? $message : EQ::t("Application error");
        if(!$leave_data)
            $this->data = null;
        if(!$leave_warnings)
            $this->warnings = [];
        throw new AjaxErrorException();
    }

    public function warning($message)
    {
        if(!in_array($message, $this->warnings))
            $this->warnings[] = $message;
        return $this;
    }

    public function clear()
    {
        $this->data = null;
        return $this;
    }

    /**
     * @param mixed $value , ...
     * @return AjaxResponse
     */
    public function push($value)
    {
        if(!is_array($this->data)) {
            $this->data = [];
            if(!is_null($this->data))
                EQ::warn("Rewriting data");
        }
        foreach(func_get_args() as $value)
            $this->data[] = $value;
        return $this;
    }

    public function raw($contents = null)
    {
        $this->raw = true;
        if(!is_null($contents))
            $this->raw_contents = $contents;
        return $this;
    }

    public function noRaw()
    {
        $this->raw = false;
        $this->raw_contents = null;
        return $this;
    }

    public function getMessage()
    {
        if(is_string($this->message) && $this->message)
            return $this->message;
        return EQ::t($this->success ? "Done" : "Application error");
    }

    public function getResponseContents()
    {
        if($this->raw)
            return is_string($this->data) ? $this->data : $this->raw_contents;
        return json_encode([
            'success' => $this->success,
            'message' => $this->getMessage(),
            'warnings' => $this->warnings,
            'data' => $this->data,
        ]);
    }

    public function processReturnValue($value)
    {
        if(!is_null($value))
            $this->data = $value;
        return $this;
    }

    public function printResponse()
    {
        if(!$this->raw)
            EQ::app()->header("Content-type", "application/json");
        echo $this->getResponseContents();
        return $this;
    }

} 