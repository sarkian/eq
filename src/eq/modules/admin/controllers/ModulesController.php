<?php

namespace eq\modules\admin\controllers;

use EQ;
use eq\base\TModuleClass;
use eq\base\WrapContainer;
use eq\modules\admin\assets\AdminAsset;
use eq\modules\admin\ModuleHtmlHelper;
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
        $modules = new WrapContainer(EQ::app()->available_modules, ModuleHtmlHelper::cls());
        $this->render("modules/index", ['modules' => $modules]);
    }

    public function actionToggle(AjaxResponse $res, $module_name)
    {
        if(!EQ::app()->validateToken())
            $res->error("Invalid request");
        if(!is_string($module_name) || !$module_name)
            $res->error("Invalid module name");
        $module = EQ::app()->module($module_name, true);
        if(!$module)
            $res->error("Module not found");
        if($module->isEnabled() && !$module->canToggle())
            $res->error("Cant disable module");
        $enabled = (EQ_RECOVERY && !$module->isEnabled())
            ? (bool) EQ::app()->dbconfig->get("modules.{$module->name}.enabled", false)
            : $module->isEnabled();
        EQ::app()->dbconfig->set("modules.$module_name.enabled", !$enabled);
    }

    public function actionRemove(AjaxResponse $res, $module_name)
    {
        // TODO: implement
    }

    public function actionInfo(AjaxResponse $res, $module_name)
    {
        // TODO: implement
    }

}
