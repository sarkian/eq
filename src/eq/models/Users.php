<?php
/**
 * Last Change: 2014 Apr 04, 00:24
 */

namespace eq\models;

use eq\web\IIdentity;

class Users implements IIdentity
{

    public function isAuth()
    {
        return false;
    }

    public function isAdmin()
    {
        return false;
    }

    public function getStatus()
    {
        return "guest";
    }
}
