<?php

namespace eq\web\route;

use eq\base\TObject;

/**
 * @property RouteRule[] rules
 * @property array rules_data
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

    public function getRulesData()
    {
        $data = [];
        foreach($this->rules as $rule)
            $data[] = $rule->saveData();
        return $data;
    }

}
