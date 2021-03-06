<?php

namespace eq\modules\user\models;

use EQ;
use eq\base\NotImplementedException;
use eq\base\TModuleClass;
use eq\datatypes\DataTypeBase;
use eq\modules\user\UserModule;
use eq\web\IIdentity;

/**
 * @property int id
 * @property string status
 * @property string name
 * @property string email
 * @property string firstname
 * @property string phone
 * @property int role
 * @property string pass
 * @property string pass_confirm
 * @property string invite
 * @property array settings
 * @property array session_fields
 * @property UserModule module
 * @property array|string login_field
 * @property string login_field_name
 * @property string login_field_value
 *
 * @property string scenario
 * @property array fields
 * @property array data
 * @property array errors
 */
trait TUser
{

    use TModuleClass;

    private static $_fields = [];

    protected $login_field_field = null;
    protected $login_field_type = null;

    protected $_auth = null;

    abstract public function fieldExists($field);
    abstract public function bind($events, $callable);
    abstract public function unbind($events, $callable = null);
    abstract public function addRawError($message, $field = null);
    abstract public function save();
    abstract public function load($condition);
    abstract public function reload();
    abstract public function validate();
    abstract public function reset();
    abstract public function applyAll($data);
    abstract public function applyLoaded($data);
    abstract public function clearFieldErrors($name);

    abstract protected function setChanged($field);

    public function __get($name)
    {
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        parent::__set($name, $value);
    }

    public function __isset($name)
    {
        return parent::__isset($name);
    }

    public function notify($message, $type = "info")
    {
        // TODO: Implement
        throw new NotImplementedException();
    }

    public function getFields()
    {
        if(!self::$_fields)
            self::$_fields = $this->module->getFields();
        $fields = self::$_fields;
        if($this->scenario === "login") {
            if(is_array($this->login_field)) {
                $lfields = $this->login_field;
                array_shift($lfields);
                foreach($lfields as $field) {
                    $fields[$field]['show'] = false;
                    $fields[$field]['required'] = false;
                }
            }
        }
        return $fields;
    }

    public function getSessionFields()
    {
        $fields = $this->fields;
        $to_unset = [
            'email_confirm',
            'pass',
            'pass_confirm',
            'invite',
        ];
        foreach($to_unset as $name) {
            unset($fields[$name]);
        }
        return array_keys($fields);
    }

    public function getLoginFieldName()
    {
        return is_array($this->module->login_field)
            ? $this->module->login_field[0] : $this->module->login_field;
    }

    public function getDbName()
    {
        return $this->module->db_name;
    }

    public function getRules()
    {
        return [
            'register' => $this->registerRules(),
            'login' => $this->loginRules(),
            'update' => $this->updateRules(),
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
        if(is_null($this->_auth))
            $this->_auth = $this->loadSession();
        return $this->_auth;
    }

    public function isAdmin()
    {
        if(!$this->isAuth())
            return false;
        elseif($this->fieldExists("role"))
            return $this->role === IIdentity::ROLE_ADMIN;
        else
            return false;
    }

    public function isModerator()
    {
        if(!$this->isAuth())
            return false;
        elseif($this->fieldExists("role"))
            return $this->role === IIdentity::ROLE_MODERATOR;
        else
            return false;
    }

    public function hasModerRights()
    {
        if(!$this->isAuth())
            return false;
        elseif($this->fieldExists("role"))
            return $this->role === IIdentity::ROLE_ADMIN || $this->role === IIdentity::ROLE_MODERATOR;
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

    public function getLoginField()
    {
        return $this->module->login_field;
    }

    public function getLoginFieldValue()
    {
        return $this->{$this->login_field_name};
    }

    public function fieldLabel($name)
    {
        if(!$this->isLoginField($name))
            return parent::fieldLabel($name);
        else
            return $this->loginFieldLabel();
    }

    /**
     * @param $fieldname
     * @return DataTypeBase|string
     * @throws \eq\base\InvalidParamException
     */
    public function fieldType($fieldname)
    {
        if($this->scenario === "login"
            && $this->login_field_type
            && $fieldname === $this->login_field_name
        )
            return $this->login_field_type;
        return parent::fieldType($fieldname);
    }

    public function settingsGet($name, $default = null)
    {
        return isset($this->settings[$name]) ? $this->settings[$name] : $default;
    }

    public function settingsSet($name, $value)
    {
        if(isset($this->settings[$name]) && $this->settings[$name] !== $value)
            $this->setChanged("settings");
        $this->data['settings'][$name] = $value;
    }

    public function settingsUnset($name)
    {
        if(isset($this->settings[$name]))
            $this->setChanged("settings");
        unset($this->data['settings'][$name]);
    }

    protected function isLoginField($name)
    {
        if($this->scenario !== "login")
            return false;
        if(is_string($this->module->login_field))
            return $name == $this->module->login_field;
        elseif(is_array($this->module->login_field))
            return in_array($name, $this->module->login_field);
        else
            return false;
    }

    protected function loginFieldLabel()
    {
        if(!is_array($this->module->login_field))
            return parent::fieldLabel($this->module->login_field);
        $parts = [];
        foreach($this->module->login_field as $field) {
            $parts[] = parent::fieldLabel($field);
        }
        $last = array_pop($parts);
        if(!$parts)
            return $last;
        $label = implode(", ", $parts);
        $label .= " ".EQ::t("or")." ".$last;
        return $label;
    }

    protected function loginRules()
    {
        $fields = [];
        $fields[] = is_array($this->module->login_field)
            ? $this->module->login_field[0] : $this->module->login_field;
        if($this->module->config("use_password"))
            $fields[] = "pass";
        return [
            'change' => $fields,
            'required' => $fields,
        ];
    }

    protected function registerRules()
    {
        $change = [];
        $required = [];
        $unique = [];
        foreach($this->fields as $name => $field) {
            if(!$field['show'])
                continue;
            $change[] = $name;
            if($field['required'])
                $required = $name;
            if($field['unique'])
                $unique[] = $name;
        }
        return [
            'change' => $change,
            'required' => $required,
            'unique' => $unique,
        ];
    }

    protected function updateRules()
    {
        $change = [];
        $required = [];
        $unique = [];
        foreach($this->fields as $name => $field) {
            if(!$field['show'] || !$field['load'] || !$field['save'])
                continue;
            if($name === "pass" || $name === "pass_confirm")
                continue;
            $change[] = $name;
            if($field['unique'])
                $unique[] = $name;
            if($field['required'])
                $required[] = $name;
        }
        if($this->config("use_password")) {
            $change[] = "pass";
            $change[] = "pass_confirm";
        }
        if($this->config("use_settings")) {
            $change[] = "settings";
        }
        return [
            'change' => $change,
            'required' => $required,
            'unique' => $unique,
        ];
    }

    protected function detectLoginField()
    {
        $value = $this->login_field_value;
        if(!is_array($this->login_field)) {
            $this->login_field_field = $this->login_field;
            $this->login_field_type = $this->fieldType($this->login_field);
            return;
        }
        $fields = $this->login_field;
        usort($fields, function($a, $b) {
            $fields = ["name", "phone", "email"];
            $key_a = array_search($a, $fields);
            $key_b = array_search($b, $fields);
            if($key_a === $key_b)
                return 0;
            if($key_a !== false)
                $key_a++;
            if($key_b !== false)
                $key_b++;
            return $key_a < $key_b ? 1 : -1;
        });
        foreach($fields as $field) {
            if(!in_array($field, $this->login_field))
                continue;
            $type = $this->fieldType($field);
            if($type::isA($value)) {
                $this->login_field_field = $field;
                $this->login_field_type = $type;
                return;
            }
        }
        $this->login_field_field = $this->login_field_name;
        $this->login_field_type = $this->fieldType($this->login_field_name);
    }

    protected function scenarioRegister()
    {
        $use_invite = $this->module->config("use_invite");
        $invite = $use_invite
            ? ($this->module->db_type === "mongo" ? new MongoInvite() : new SqlInvite()) : null;
        $pass = "";
        $this->bind("afterApply", function() {
            $this->unbind("afterApply");
            $this->save();
        });
        $this->bind("afterValidate", function() use($use_invite, &$invite) {
            $this->unbind("afterValidate");
            if(strlen($this->pass) && $this->pass !== $this->pass_confirm)
                $this->addRawError(EQ::t("Passwords do not match"), "pass");
            if($use_invite && (!$this->invite || !$invite->load($this->invite)))
                $this->addRawError(EQ::t("Invalid invite"), "invite");
        });
        $this->bind("beforeSave", function() use(&$pass) {
            $this->unbind("beforeSave");
            $pass = $this->pass;
            $this->pass = "";
        });
        $this->bind("saveSuccess", function() use(&$pass, $use_invite, &$invite) {
            $this->pass = $this->encryptPassword($pass);
            $this->data['role'] = IIdentity::ROLE_USER;
            $this->setChanged("role");
            $this->unbind("saveSuccess");
            if(!$this->save())
                $this->addRawError(EQ::t("Application error. Try later."));
            elseif($use_invite)
                $invite->delete();
            $this->pass = $pass;
            $this->_auth = null;
            $this->saveSession();
        });
        $this->bind("saveFail", function() use(&$pass) {
            $this->pass = $pass;
        });
    }

    protected function scenarioLogin()
    {
        $this->bind("afterApply", function() {
            $this->unbind("afterApply");
            $this->detectLoginField();
            $this->validate();
            if($this->errors)
                return;
            $pass = $this->pass;
            $rules = [
                $this->login_field_field => $this->login_field_value,
            ];
            $val = $this->login_field_value;
            if($this->load($rules)) {
                if(!$this->verifyPassword($pass)) {
                    $this->addRawError(EQ::t("Invalid login or password"), $this->login_field_name);
                    $this->data[$this->login_field_name] = $val;
                    $this->pass = $pass;
                    return;
                }
                $this->_auth = null;
                $this->saveSession();
            }
            else {
                $this->addRawError(EQ::t("Invalid login or password"), $this->login_field_name);
            }
        });
    }

    protected function scenarioLogout()
    {
        $this->reset();
        EQ::app()->session->destroy();
    }

    protected function scenarioUpdate()
    {
        $hash = "";
        $this->bind("beforeApply", function() use(&$hash) {
            $this->unbind("beforeApply");
            $this->reload();
            $hash = $this->pass;
        });
        $this->bind("afterValidate", function() use(&$hash) {
            $this->unbind("afterValidate");
            if(strlen($this->pass)) {
                if($this->pass !== $this->pass_confirm) {
                    $this->clearFieldErrors("pass");
                    $this->clearFieldErrors("pass_confirm");
                    $this->addRawError(EQ::t("Passwords do not match"), "pass");
                }
                else
                    $this->pass = $this->encryptPassword($this->pass);
            }
            else
                $this->pass = $hash;
        });
        $this->bind("saveSuccess", function() {
            $this->saveSession();
        });
    }

    protected function saveSession()
    {
        $data = [];
        foreach($this->session_fields as $field)
            $data[$field] = $this->{$field};
        EQ::app()->session['userdata'] = $data;
    }

    protected function loadSession()
    {
        $data = $this->validateSessionData(EQ::app()->session['userdata']);
        if($data === false)
            return false;
        unset($data['sessions']);
        unset($data['sessid']);
//        $this->applyAll($data);
        $this->applyLoaded($data);
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
