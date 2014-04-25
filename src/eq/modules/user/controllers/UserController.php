<?php
/**
 * Last Change: 2014 Apr 25, 20:02
 */

namespace eq\modules\user\controllers;

use EQ;
use eq\modules\user\UserModule;
use eq\modules\user\models\Users;
use eq\web\HttpException;

class UserController extends \eq\web\Controller
{

    use \eq\base\TModuleClass;

    protected function permissions()
    {
        return [
            'guest' => ["deny", "logout"],
        ];
    }

    public function getTemplate()
    {
        return "main";
    }

    public function actionLogin()
    {
        $this->createTitle(EQ::t("Login"));
        $model = new Users("login");
        $form = EQ::widget(
            $this->config("login_form_widget", "eq.BootstrapModelForm"), $model);
        if(EQ::app()->request->isPost()) {
            EQ::app()->header("Content-type", "text/plain");
            $data = $form->getData();
            $this->redir("/");
            return;
        }
        $this->render("login", [
            'model' => $model,
            'form' => $form,
        ]);
    }

    public function actionLogout()
    {
        
    }

    public function actionRegister()
    {
        $this->createTitle(EQ::t("Register"));
        $model = new Users("register");
        $form = EQ::widget(
            $this->config("register_form_widget", "eq.BootstrapModelForm"), $model);
        $this->render("register", [
            'model' => $model,
            'form' => $form,
        ]);
    }

    public function actionTest()
    {
        header("Content-type: text/plain");
        var_dump($this->module_class);
        var_dump($this->module_namespace);
        var_dump($this->module_name);
    }

}
