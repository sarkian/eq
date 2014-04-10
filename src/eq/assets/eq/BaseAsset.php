<?php
/**
 * Last Change: 2014 Apr 05, 17:12
 */

namespace eq\assets\eq;

class BaseAsset extends \eq\web\AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/eq";
    protected $base_path = "@www/assets/eq";
    protected $base_url = "@web/assets/eq";

    protected $js = [
        "eq.base-0.0.1.js",
    ];

    protected $depends = [
        // "jquery",
    ];

    public function getAfter()
    {
        return EQ_DBG ? ["eq.debug"] : [];
    }

}
