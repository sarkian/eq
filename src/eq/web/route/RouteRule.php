<?php
/**
 * Last Change: 2014 Apr 22, 22:40
 */

namespace eq\web\route;

class RouteRule
{

    use \eq\base\TObject;

    protected $fname;
    protected $lnum;

    protected $url_prefix;
    protected $path_prefix;

    protected $method;
    protected $url;
    protected $path;

    public function __construct($line = null, $fname = null, $lnum = null,
                $url_prefix = "", $path_prefix = "")
    {
        if($line)
            $this->parse($line, $fname, $lnum, $url_prefix, $path_prefix);
    }

    public function setMethod($method_)
    {
        $method = strtoupper(trim($method_, " \r\n\t"));
        if(!in_array($method, [
            "*",
            "GET",
            "POST",
            "PUT",
            "PATCH",
            "DELETE",
        ], true))
            $this->except("Invalid method: $method_");
        $this->method = $method;
    }

    public function saveData()
    {
        
    }

    public function loadData($data)
    {
        
    }

    public function parse($line, $fname, $lnum, $url_prefix = "", $path_prefix = "")
    {
        $this->fname = $fname;
        $this->lnum = $lnum;
        $this->url_prefix = $url_prefix;
        $this->path_prefix = $path_prefix;
        $parts = preg_split("/[\s\t]+/", $line);
        if(count($parts) !== 3)
            $this->except("Invalid rule");
        $this->setMethod($parts[0]);
        $this->url = new RouteRuleUrl($parts[1],
            $this->fname, $this->lnum, $this->url_prefix);
        $this->path = new RouteRulePath($parts[2],
            $this->fname, $this->lnum, $this->path_prefix);
    }

    protected function except($message)
    {
        throw new RouteSyntaxException($message, $this->fname, $this->lnum);
    }

    protected function exceptParsing($message)
    {
        throw new RouteParsingException($message, $this->fname, $this->lnum);
    }

    protected function unexpected($unexp, $exp)
    {
        if(is_array($exp)) {
            $exp_ = [];
            foreach($exp as $str)
                $exp_[] = count(preg_split("/\s|_/", $str)) == 1
                    ? "'$str'" : $str;;
            $exp = implode(" or ", $exp_);
        }
        elseif(count(preg_split("/\s|_/", $exp)) < 2)
            $exp = "'$exp'";
        if(count(preg_split("/\s|_/", $unexp)) == 1)
            $unexp = "'$unexp'";
        throw new RouteSyntaxException(
            "Unexpected $unexp, expecting $exp", $this->fname, $this->lnum);
    }

}
