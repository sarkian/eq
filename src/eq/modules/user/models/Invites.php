<?php
/**
 * Last Change: 2014 Apr 19, 18:06
 */

namespace eq\modules\user\models;

class Invites extends \eq\data\Model
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
