<?php

namespace eq\dev\test;

use ReflectionMethod;

    trait some_trait
    {

        public function call($args)
        {
            echo "hello from trait\n";
        }

        private function __ok()
        {
            ReflectionMethod::isFinal();
            ReflectionMethod::__construct();
            file_get_contents();
        }

    }
