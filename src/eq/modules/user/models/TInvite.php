<?php

namespace eq\modules\user\models;

use eq\modules\user\UserModule;

trait TInvite
{

    public function getFields()
    {
        return [
            'user_id' => "uintp",
            'created' => "uintp",
            'invite' => "str",
            'days' => "uint",
            'name' => "str",
            'phone' => "phone",
        ];
    }

    public function getPk()
    {
        return "invite";
    }

    public function getMessages()
    {
        return [];
    }

    public function getDbName()
    {
        return UserModule::instance()->db_name;
    }

} 