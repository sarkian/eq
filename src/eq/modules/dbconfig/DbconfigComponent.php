<?php

namespace eq\modules\dbconfig;

use eq\base\TModuleClass;

/**
 * @property DbconfigModule module
 */
class DbconfigComponent
{

    use TModuleClass;

    public function get($name, $default = null)
    {
        $this->module->get($name, $default);
    }

    public function set($name, $value)
    {
        $this->module->set($name, $value);
    }

    public function remove($name)
    {
        $this->module->remove($name);
    }

} 