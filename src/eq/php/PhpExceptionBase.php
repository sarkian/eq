<?php

namespace eq\php;

    abstract class PhpExceptionBase extends \eq\base\ExceptionBase
    {

        protected $type = "PhpExceptionBase";
        protected $context;

        public function __construct($errno, $message, $file, $line, $context)
        {
            parent::__construct($message, $errno);
            $this->file = $file;
            $this->line = $line;
            $this->context = $context;
        }

    }
