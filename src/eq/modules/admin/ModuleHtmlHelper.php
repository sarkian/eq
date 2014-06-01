<?php

namespace eq\modules\admin;

use eq\base\ModuleBase;
use eq\base\WrapContainerItem;
use eq\web\html\Html;
use EQ;

class ModuleHtmlHelper extends WrapContainerItem
{

    /**
     * @var ModuleBase
     */
    protected $obj;

    public $enabled;
    public $can_disable;

    public function __construct(ModuleBase $module)
    {
        parent::__construct($module);
        $this->enabled = (EQ_RECOVERY && !$module->isEnabled())
            ? $this->isEnabledInConfig() : $module->isEnabled();
        $this->can_disable = $module->canToggle();
    }

    public function getPanelClass()
    {
        $class = ["panel", "module-panel"];
        if($this->obj->errors)
            $class[] = "panel-danger";
        elseif($this->obj->warnings)
            $class[] = "panel-warning";
        elseif(!$this->enabled)
            $class[] = "panel-default";
        elseif($this->can_disable)
            $class[] = "panel-primary";
        else
            $class[] = "panel-cant-disable";
        return implode(" ", $class);
    }

    public function getDependencies()
    {
        $deps = [];
        foreach($this->obj->dependencies as $mname) {
            $deps[$mname] = EQ::app()->module($mname, true);
        }
        return $deps;
    }

    protected function isEnabledInConfig()
    {
        return (bool) EQ::app()->dbconfig->get("modules.{$this->obj->name}.enabled", false);
    }

}