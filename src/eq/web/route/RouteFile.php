<?php
/**
 * Last Change: 2014 Apr 22, 22:24
 */

namespace eq\web\route;

use eq\base\TObject;

/**
 * @property array rules
 */
class RouteFile
{

    use TObject;

    protected $rules = [];

    public function __construct($fname, $url_prefix = "", $path_prefix = "")
    {
        $lines = file($fname);
        foreach($lines as $lnum => $line) {
            $line = trim($line, " \r\n\t");
            if($line && strncmp($line, "#", 1)) {
                $this->rules[] = new RouteRule($line,
                    $fname, $lnum + 1, $url_prefix, $path_prefix);
            }
        }
    }

    public function getRules()
    {
        return $this->rules;
    }

}
