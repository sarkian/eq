<?php

namespace eq\modules\user;

use EQ;
use eq\base\ModuleBase;
use eq\helpers\Str;

class UserModule extends ModuleBase
{

    private $fields_defaults;
    private $login_field;

    public function getUrlPrefix()
    {
        return $this->config("url_prefix", "");
    }

    public function getFields()
    {
        $fields = [
            'id' => $this->field("id"),
        ];
        foreach($this->config("fields", []) as $name => $attrs) {
            $fields[$name] = $this->field($name);
        }
        if(!isset($fields[$this->login_field]))
            $fields[$this->login_field] = $this->field($this->login_field);
        // email
        if($this->config("use_email", true)) {
            $fields['email'] = $this->field("email");
            if($this->config("email_confirmation", false) && !isset($fields['email_confirm']))
                $fields['email_confirm'] = $this->field("email_confirm");
            if($this->config("email_verification", true)) {
                // TODO Email verification
            }
        }
        // password
        if($this->config("use_password", true)) {
            $fields['pass'] = $this->field("pass");
            if($this->config("password_confirmation", true) && !isset($fields['pass_confirm']))
                $fields['pass_confirm'] = $this->field("pass_confirm");
        }
        // phone
        if($this->config("use_phone", false)) {
            $fields['phone'] = $this->field("phone");
        }
        // firstname
        if($this->config("use_firstname", false)) {
            $fields['firstname'] = $this->field("firstname");
        }
        // lastname
        if($this->config("use_lastname", false)) {
            $fields['lastname'] = $this->field("lastname");
        }
        // invite
        if($this->config("use_invite", false)) {
            $fields['invite'] = $this->field("invite");
        }
        $this->login_field = $this->config("login_field", "name");
        EQ::log($this->login_field);
        //        EQ::log(array_keys($fields));
        //        EQ::log($fields);
        return $fields;
    }

    public function getLoginField()
    {
        if(!$this->login_field)
            $this->getFields();
        return $this->login_field;
    }

    protected function field($name)
    {
        $field = $this->config("fields.$name", []);
        $defaults = $this->fieldDefaults($name);
        foreach($defaults as $aname => $aval) {
            if(!isset($field[$aname]))
                $field[$aname] = $aval;
        }
        return $field;
    }

    protected function fieldDefaults($name)
    {
        if(!$this->fields_defaults)
            $this->fields_defaults = $this->fieldsDefaults();
        $attrs = isset($this->fields_defaults[$name]) ? $this->fields_defaults[$name] : [];
        if(is_string($attrs))
            $attrs = $this->fields_defaults[$attrs];
        if(!isset($attrs['type']) || !$attrs['type'])
            $attrs['type'] = "str";
        if(!isset($attrs['show']))
            $attrs['show'] = true;
        if(!isset($attrs['load']))
            $attrs['load'] = true;
        if(!isset($attrs['save']))
            $attrs['save'] = true;
        if(!isset($attrs['unique']))
            $attrs['unique'] = false;
        if(!isset($attrs['default']))
            $attrs['default'] = "";
        if(!isset($attrs['label']))
            $attrs['label'] = EQ::t(Str::method2label($name));
        return $attrs;
    }

    private function fieldsDefaults()
    {
        return [
            'id' => [
                'type' => "uintp",
                'show' => false,
                'load' => true,
                'save' => false,
                'unique' => true,
                'default' => 0,
                'label' => "",
            ],
            'name' => [
                'type' => "username",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => true,
                'default' => "",
                'label' => EQ::t("Username"),
            ],
            'email' => [
                'type' => "email",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => true,
                'default' => "",
                'label' => EQ::t("Email"),
            ],
            'email_confirm' => [
                'type' => "email",
                'show' => true,
                'load' => false,
                'save' => false,
                'default' => "",
                'label' => EQ::t("Email confirmation"),
            ],
            'firstname' => [
                'type' => "firstname",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => false,
                'default' => "",
                'label' => EQ::t("First Name"),
            ],
            'lastname' => [
                'type' => "lastname",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => false,
                'default' => "",
                'label' => EQ::t("Last Name"),
            ],
            'phone' => [
                'type' => "phone",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => true,
                'default' => "",
                'label' => EQ::t("Phone"),
            ],
            'role' => [
                'type' => "uintp",
                'show' => false,
                'load' => true,
                'save' => true,
                'default' => 0,
                'label' => EQ::t("Role"),
            ],
            'pass' => [
                'type' => "password",
                'show' => true,
                'load' => true,
                'save' => true,
                'default' => "",
                'label' => EQ::t("Password"),
            ],
            'pass_confirm' => [
                'type' => "password",
                'show' => true,
                'load' => false,
                'save' => false,
                'default' => "",
                'label' => EQ::t("Password confirmation"),
            ],
            'invite' => [
                'type' => "invite",
                'show' => true,
                'load' => false,
                'save' => false,
                'default' => "",
                'label' => EQ::t("Invite"),
            ],
        ];
    }

}
