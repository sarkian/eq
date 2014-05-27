<?php

namespace eq\modules\admin;

use eq\base\ModuleBase;
use eq\web\html\Html;
use EQ;

class ModuleHtmlHelper
{

    public $title;
    public $description;

    protected $module;
    protected $enabled;
    protected $can_disable;

    public function __construct(ModuleBase $module)
    {
        $this->module = $module;
        $this->enabled = $module->isEnabled();
        $this->can_disable = $module->canToggle();
        $this->title = htmlentities($module->title);
        $this->description = $module->description;
        $this->description = $this->description ?
            htmlentities($this->description) : "<i>".EQ::t("No description")."</i>";
    }

    public function panelClass()
    {
        $class = ["panel", "module-panel"];
        if($this->module->errors)
            $class[] = "panel-danger";
        elseif($this->module->warnings)
            $class[] = "panel-warning";
        elseif(!$this->enabled)
            $class[] = "panel-default";
        elseif($this->can_disable)
            $class[] = "panel-primary";
        else
            $class[] = "panel-cant-disable";
        return implode(" ", $class);
    }

    public function enabledCheckbox()
    {
        $options = [
            'type' => "checkbox",
            'class' => "module-checkbox",
        ];
        if($this->enabled)
            $options['checked'] = "checked";
        if(!$this->can_disable)
            $options['disabled'] = "disabled";
        return Html::tag("input", $options);
    }

    public function dependencies()
    {
        if(!$this->module->depends)
            return EQ::t("No dependencies");
        $links = [];
        foreach($this->module->depends as $mname) {
            $opts = ['onclick' => "return false;"];
            $module = EQ::app()->module($mname, true);
            if($module) {
                $href = "#".$mname;
                $opts['data-module-name'] = $mname;
                if($this->module->isEnabled() && !$module->isEnabled())
                    $opts['class'] = "warning";
            }
            else {
                $href = "#";
                $opts['class'] = "error";
            }
            $links[] = Html::link($mname, $href, $opts);
        }
        return EQ::t("Dependencies").": ".implode(", ", $links);
    }

}