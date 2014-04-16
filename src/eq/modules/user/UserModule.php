<?php
/**
 * Last Change: 2014 Apr 16, 14:01
 */

namespace eq\modules\user;

use EQ;
use eq\helpers\Arr;

class UserModule extends \eq\base\ModuleBase
{

    protected $config = [];

    public function __construct($config = [])
    {
        $this->config = Arr::extend($config, [

        ]);
        $this->registerComponent("user", $this->findClass("models.Users"));
    }

}
