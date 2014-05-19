<?php

namespace eq\modules\user\controllers;

use EQ;
use eq\base\TModuleClass;
use eq\modules\user\models\Users;
use eq\web\Controller;
use eq\web\WebApp;

class UserController extends Controller
{

    use TModuleClass;

    protected function permissions()
    {
        return [
            'guest' => ["deny", "logout"],
            'user,admin' => ["deny", "login", "/"],
        ];
    }

    public function getTemplate()
    {
        return "main";
    }

    public function actionLogin()
    {
        $this->createTitle(EQ::t("Login"));
        $model = EQ::app()->user->setScenario("login");
        $form = EQ::widget($this->config("login_form_widget", "ModelForm"), $model);
        if(EQ::app()->request->isPost()) {
            $model->apply($form->getData());
            if($model->isAuth())
                $this->redir($this->module->config("login_redirect_url", "{main.index}"));
        }
        $this->render("login", [
            'model' => $model,
            'form' => $form,
        ]);
    }

    public function actionLogout()
    {
        EQ::app()->user->setScenario("logout");
        $this->redir($this->module->config("logout_redirect_url", "{main.index}"));
    }

    public function actionRegister()
    {
        $this->createTitle(EQ::t("Register"));
        $model = new Users("register");
        $form = EQ::widget($this->config("register_form_widget", "ModelForm"), $model);
        $this->render("register", [
            'model' => $model,
            'form' => $form,
        ]);
    }

    public function actionTest()
    {
        header("Content-type: text/plain");
        $app = new WebApp([]);
        echo $app->request->method;
        var_dump($this->module_class);
        var_dump($this->module_namespace);
        var_dump($this->module_name);
    }

}
