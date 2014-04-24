<?php
/**
 * Last Change: 2014 Apr 24, 04:04
 */

namespace eq\modules\user;

use EQ;
use eq\helpers\Arr;

class UserModule extends \eq\base\ModuleBase
{

    public function getUrlPrefix()
    {
        return $this->config("url_prefix", "");
    }

}
