<?php
/**
 * Last Change: 2014 Apr 09, 02:43
 */

namespace eq\web;

use EQ;

class Request extends \eq\base\Object
{

    const C_SECURE = 1;
    const C_NOHTTPONLY = 2;

    protected $scheme = "http";
    protected $host;
    protected $uri;
    protected $method;
    protected $root;

    public function __construct()
    {
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
            $this->scheme = "https";
        }
        $this->host = $_SERVER['HTTP_HOST'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->root = $this->scheme."://".$this->host;
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getRoot()
    {
        return $this->root;
    }

}
