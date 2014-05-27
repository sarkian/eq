<?php

namespace eq\web;

use EQ;
use eq\assets\eq\BaseAsset;
use eq\assets\eq\JsdataAsset;
use eq\helpers\Arr;

class Jsdata
{

    protected $data = [];

    public function __construct($config = [])
    {
        EQ::app()->bind("beforeRender", function() {
            EQ::app()->trigger("jsdata.register", $this);
            if($this->data) {
                $data = json_encode($this->data);
                EQ::app()->client_script->addJs("EQ.registerComponent('data', new eq.Jsdata($data));");
            }
        });
        EQ::app()->bind("client_script.render", function() {
            if($this->data)
                JsdataAsset::register();
        });
    }

    public function get($name, $default = null)
    {
        return Arr::getItem($this->data, $name, $default);
    }

    public function set($name, $value)
    {
        Arr::setItem($this->data, $name, $value);
    }

}
