<?php

namespace eq\web;

    class Session implements \ArrayAccess
    {

        public function __construct($sessvar = array())
        {
            if(session_id()) return;
            /*if(EQ::app()->memcache) {
                ini_set('session.save_handler', 'memcache');
                ini_set('session.save_path',
                    'tcp://'.EQ::app()->config['memcache']['host'].':'
                    .EQ::app()->config['memcache']['port']
                );
            }*/
            ini_set('session.gc_maxlifetime', 2592000);
            ini_set('session.cookie_lifetime', 2592000);
            session_name('_sessid');
            session_start();
        }
        
        public function offsetExists($name)
        {
            return isset($_SESSION[$name]);
        }

        public function offsetGet($name)
        {
            return $this->offsetExists($name) ? $_SESSION[$name] : null;
        }

        public function offsetSet($name, $value)
        {
            if(!session_id()) self::__construct();
            $_SESSION[$name] = $value;
        }

        public function offsetUnset($name)
        {
            unset($_SESSION[$name]);
        }
        
        public function __destroy()
        {
            session_unset();
            session_destroy();
            session_write_close();
            setcookie(session_name(), '' , 0 ,'/');
        }


    }
