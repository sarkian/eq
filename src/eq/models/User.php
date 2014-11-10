<?php

namespace eq\models;

use EQ;
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

    public function isModerator()
    {
        return false;
    }

    public function hasModerRights()
    {
        return false;
    }

    public function getStatus()
    {
        return "guest";
    }

    public function notify($message, $type = "info")
    {
        EQ::warn("Not implemented, yet");
    }

}
