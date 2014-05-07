<?php

namespace eq\web;

use EQ;
use eq\base\ExceptionBase;

class HttpRedirectException extends ExceptionBase
{

    protected $type = "HttpRedirectException";
    protected $url;
    protected $status;
    protected $message;

    public function __construct($url, $status = 302, $message = "Found")
    {
        if(!is_string($url) || !$url)
            $url = "/";
        if(preg_match('/^\{.+\}$/', $url)) {
            $url = EQ::app()->createUrl(preg_replace('/^\{|\}$/', "", $url));
        }
        if(!is_int($status) || $status < 100 || $status > 511)
            $status = 302;
        if(!is_string($message) || !$message)
            $message = "Found";
        $this->url = $url;
        $this->status = $status;
        $this->message = $message;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getUrl()
    {
        return $this->url;
    }

}
