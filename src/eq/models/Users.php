<?php
/**
 * Last Change: 2014 Apr 04, 00:24
 */

namespace eq\models;

class Users implements \eq\web\IIdentity
{

    public function isAuth()
    {
        return false;
    }

    public function isAdmin()
    {
        return false;
    }

}
