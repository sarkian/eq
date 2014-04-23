<?php
/**
 * Last Change: 2014 Apr 24, 00:59
 */

namespace eq\modules\user;

use EQ;
use eq\helpers\Arr;

class UserModule extends \eq\base\ModuleBase
{

    protected $config = [];

    public function __construct($config = [])
    {
        $this->registerComponent("user", $this->findClass("models.Users"));
        // EQ::app()->route->register("*", "/user/{action}", $this->findClass("controllers.UserController"));
    }

}
