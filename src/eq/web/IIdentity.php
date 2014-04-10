<?php
/**
 * Last Change: 2014 Apr 04, 00:24
 */

namespace eq\web;

interface IIdentity
{

    public function isAuth();
    public function isAdmin();

}
