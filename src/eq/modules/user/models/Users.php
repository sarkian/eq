<?php
/**
 * Last Change: 2014 Apr 19, 18:53
 */

namespace eq\modules\user\models;

use EQ;
use eq\base\TModuleClass;
use eq\data\Model;
use eq\data\TModel;
use eq\modules\user\UserModule;
use eq\web\IIdentity;

/**
 * @property int id
 * @property string name
 * @property string email
 * @property string firstname
 * @property string phone
 * @property int role
 * @property string pass
 * @property string pass_confirm
 * @property string invite
 * @property array session_fields
 * @property UserModule module
 */
class Users extends Model implements IIdentity
{

    use TModuleClass;

    const SESSION_LIMIT = 29;
    
    const ROLE_USER     = 1;
    const ROLE_ADMIN    = 2;
    
    private static $_fields = [];

    public function getFields()
    {
        if(self::$_fields)
            return self::$_fields;
        self::$_fields = $this->module->getFields();
        return self::$_fields;
    }

    public function _getFields()
    {
        return [
            'id' => [
                'type' => "uintp",
                'show' => false,
                'load' => true,
                'save' => false,
            ],
            'name' => [
                'type' => "username",
                'show' => true,
                'label' => EQ::t("Username"),
                'default' => "",
                'load' => true,
                'save' => true,
            ],
            'email' => [
                'type' => "email",
                'show' => true,
                'label' => EQ::t("Email"),
                'load' => true,
                'save' => true,
            ],
            'firstname' => [
                'type' => "firstname",
                'show' => true,
                'label' => EQ::t("Firstname"),
                'load' => true,
                'save' => true,
            ],
            'phone' => [
                'type' => "phone",
                'show' => true,
                'label' => EQ::t("Phone"),
                'load' => true,
                'save' => true,
            ],
            'role' => [
                'type' => "uintp",
                'show' => false,
                'load' => true,
                'save' => true,
            ],
            'pass' => [
                'type' => "password",
                'show' => true,
                'label' => EQ::t("Password"),
                'load' => true,
                'save' => true,
            ],
            'pass_confirm' => [
                'type' => "password",
                'show' => true,
                'label' => EQ::t("Password confirmation"),
                'load' => false,
                'save' => false,
            ],
            'invite' => [
                'type' => "invite",
                'show' => true,
                'label' => EQ::t("Invite"),
                'load' => false,
                'save' => false,
            ],
        ];
    }

    public function getSessionFields()
    {
        return [
            "id",
            "name",
            "role",
            "firstname",
            "phone",
        ];
    }

    public function getDbName()
    {
        return EQ::app()->config("modules.user.db_name", "main");
    }

    public function getTableName()
    {
        return EQ::app()->config("modules.user.table_name", "users");
    }

    public function getRules()
    {
        return [
            'register' => [
                'change' => ['name', 'email', 'firstname',
                        'phone', 'pass', 'pass_confirm', 'invite'],
                'required' => ['name', 'email', 'firstname',
                        'phone', 'pass', 'pass_confirm', 'invite'],
                'unique' => ['name', 'phone', 'email'],
            ],
            'login' => [
                'change' => ['name', 'pass'],
                'required' => ['name', 'pass'],
            ],
            'edit' => [
                'change' => ['firstname', 'phone'],
                'required' => ['firstname', 'phone'],
            ],
            'ajax-edit' => [
                'change' => ['email', 'firstname', 'phone',
                        'role', 'pass', 'subs'],
            ],
        ];
    }

    public function getMessages()
    {
        return [];
    }

    public function verifyPassword($pass)
    {
        return $this->pass === $this->encryptPassword($pass);
    }

    public function encryptPassword($pass)
    {
        return md5(sha1($this->id).sha1($pass));
    }

    public function isAuth()
    {
        return $this->loadSession();
    }

    public function isAdmin()
    {
        if($this->fieldExists("role"))
            return $this->role === self::ROLE_ADMIN;
        else
            return false;
    }

    public function getStatus()
    {
        if($this->isAuth())
            return $this->isAdmin() ? "admin" : "user";
        else
            return "guest";
    }

    protected function scenarioRegister()
    {
        $invite = new Invites();
        $pass = "";
        $this->bind("afterValidate", function() use(&$invite) {
            if($this->pass && $this->pass !== $this->pass_confirm)
                $this->addError("Passwords do not match", "pass");
            if(!$this->invite || !$invite->load($this->invite))
                $this->addError("Invalid invite", "invite");
        });
        $this->bind("beforeSave", function() use(&$pass) {
            $pass = $this->pass;
            $this->pass = "";
        });
        $this->bind("saveSuccess", function() use(&$pass, &$invite) {
            $this->pass = $this->verifyPassword($pass);
            $this->role = 1;
            $this->unbind("saveSuccess");
            if(!$this->save())
                $this->addRawError("Error. Try later");
            else
                $invite->delete();
            $this->pass = $pass;
        });
        $this->bind("saveFail", function() use(&$pass) {
            $this->pass = $pass;
        });
    }

    protected function scenarioLogin()
    {
        $this->bind("afterApply", function() {
            $this->unbind("afterApply");
            $this->validate();
            if($this->errors)
                return;
            $pass = $this->pass;
            if($this->load(['name' => $this->name])) {
                if(!$this->verifyPassword($pass)) {
                    $this->addRawError("Invalid login or password", "name");
                    return;
                }
                $this->saveSession();
            }
        });
    }

    protected function scenarioLogout()
    {
        $this->reset();
        EQ::app()->session->destroy();
    }

    protected function saveSession()
    {
        $data = [];
        foreach($this->session_fields as $field)
            $data[$field] = $this->{$field};
        EQ::log($data);
        EQ::app()->session['userdata'] = $data;
    }

    protected function loadSession()
    {
        $data = $this->validateSessionData(EQ::app()->session['userdata']);
        if($data === false)
            return false;
        unset($data['sessions']);
        unset($data['sessid']);
        $this->applyAll($data);
        return true;
    }

    protected function validateSessionData($data)
    {
        $fields = $this->session_fields;
        foreach($fields as $name) {
            if(!isset($data[$name]))
                return false;
        }
        foreach($data as $name => $value) {
            if(!in_array($name, $fields, true))
                unset($data[$name]);
        }
        return $data;
    }

}
