<?php

namespace eq\web;

    class User
    {

        public $id = 0;
        
        public function isAuth()
        {
            return false;
        }

        public function isAdmin()
        {
            return false;
        }

    }
