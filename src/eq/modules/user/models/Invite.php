<?php

namespace eq\modules\user\models;

use eq\data\Model;

class Invite extends Model
{

    public function getFields()
    {
        return [
            'user_id'   => "uintp",
            'created'   => "uintp",
            'invite'    => "str",
            'days'      => "uint",
            'name'      => "str",
            'phone'     => "phone",
        ];
    }

    public function getDbName()
    {
        return "main";
    }

    public function getTableName()
    {
        return "invites";
    }

    public function getPk()
    {
        return "invite";
    }

    public function getMessages()
    {
        return [];
    }

}
