<?php

namespace eq\web;

use eq\base\Object;
use eq\datatypes\DataTypeBase;
use eq\helpers\Arr;

/**
 * @property string scheme
 * @property string host
 * @property string uri
 * @property string method
 * @property string root
 */
class Request extends Object
{

    const C_SECURE = 1;
    const C_NOHTTPONLY = 2;

    protected $scheme = "http";
    protected $host;
    protected $uri;
    protected $method;
    protected $root;

    protected $_files = null;

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

    public function isPost()
    {
        return $this->method === "POST";
    }

    public function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === "XMLHttpRequest")
            || isset($_REQUEST['ajax']);
    }

    public function get($name, $default = null)
    {
        return Arr::getItem($_GET, $name, $default);
    }

    public function post($name, $default = null)
    {
        return Arr::getItem($_POST, $name, $default);
    }

    public function request($name, $default = null)
    {
        return Arr::getItem($_REQUEST, $name, $default);
    }

    public function file($name)
    {
        return new UploadedFile(isset($_FILES[$name]) ? $_FILES[$name] : []);
    }

    public function files($name = null)
    {

    }

    public function filterGet($name, $type, $default = null)
    {
        $type = DataTypeBase::getClass($type);
        return $type::filter(Arr::getItem($_GET, $name, $default));
    }

    public function filterPost($name, $type, $default = null)
    {
        $type = DataTypeBase::getClass($type);
        return $type::filter(Arr::getItem($_POST, $name, $default));
    }

    public function filterRequest($name, $type, $default = null)
    {
        $type = DataTypeBase::getClass($type);
        return $type::filter(Arr::getItem($_REQUEST, $name, $default));
    }

    protected function processFiles()
    {
        foreach($_FILES as $file) {

        }
    }

}
