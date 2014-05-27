<?php

namespace eq\modules\admin\controllers;

use EQ;
use eq\base\TModuleClass;
use eq\modules\admin\assets\AdminAsset;
use eq\modules\ajax\AjaxResponse;
use eq\web\Controller;

class ModulesController extends Controller
{

    use TModuleClass;

    protected $template = "main";

    protected function permissions()
    {
        return [
            'user,guest' => ["deny", "#all"],
        ];
    }

    public function actionIndex()
    {
        if(EQ::app()->request->isAjax())
            $this->template = null;
        AdminAsset::addJs("modules");
        $modules = EQ::app()->available_modules;
        $this->render("modules/index", ['modules' => $modules]);
    }

    public function actionToggle(AjaxResponse $res, $module_name)
    {
        if(!is_string($module_name) || !$module_name)
            $res->error("Invalid module name");
        $module = EQ::app()->module($module_name, true);
        if(!$module)
            $res->error("Module not found");
        if($module->isEnabled() && !$module->canToggle())
            $res->error("Cant disable module");
        EQ::app()->dbconfig->set("modules.$module_name.enabled", !$module->isEnabled());
    }

    public function actionRemove(AjaxResponse $res, $module_name)
    {
        // TODO: implement
    }

}
