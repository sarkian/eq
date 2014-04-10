<?php

namespace eq\dev\test;

    class TestComponent extends \eq\base\Component
    {

        use some_trait;

        public function __construct()
        {
            \EQ::app()->registerComponentMethod('testf', [$this, 'testf']);
        }

        public function testf($a)
        {

        }

    }
