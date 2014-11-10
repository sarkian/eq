<?php

namespace eq\web;

const ROLE_GUEST = 0;
const ROLE_USER = 1;
const ROLE_ADMIN = 2;
const ROLE_MODERATOR = 3;

interface IIdentity
{

    const SESSION_LIMIT = 29;

    const ROLE_GUEST = ROLE_GUEST;
    const ROLE_USER = ROLE_USER;
    const ROLE_ADMIN = ROLE_ADMIN;
    const ROLE_MODERATOR = ROLE_MODERATOR;

    public function isAuth();
    public function isAdmin();
    public function isModerator();
    public function hasModerRights();
    public function getStatus();
    public function notify($message, $type = "info");

}
