<?php

namespace eq\models;

use eq\web\IIdentity;

class User implements IIdentity
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
