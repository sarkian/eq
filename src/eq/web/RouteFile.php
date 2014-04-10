<?php
/**
 * Last Change: 2014 Mar 24, 21:51
 */

namespace eq\web;

use eq\base\InvalidCallException;

class RouteFile
{

    protected $fname;
    protected $meta = [];
    protected $rules = [];

    public function __construct($fname = null, $noload = false)
    {
        $this->fname = $fname;
        if(!$noload && $fname && file_exists($fname))
            $this->load($fname);
    }

    public function load($fname = null)
    {
        if($fname)
            $this->fname = $fname;
        $lines = file($fname, FILE_IGNORE_NEW_LINES);
        foreach($lines as $lnum => $line) {
            $lnum += 1;
            $line = trim($line, " \r\n\t");
            if(!$line || !strncmp($line, "#", 1))
                continue;
            if(!strncmp($line, ":", 1))
                $this->processMeta($line, $lnum);
            else {
                $rule = RouteRule::fromString($line, $fname, $lnum);
                if($rule->preprocessed)
                    $this->rules = array_merge(
                        $this->rules, $rule->generated_rules);
                else
                    $this->rules[] = $rule;
            }
        }
    }

    public function save($fname = null)
    {
        if(!is_string($fname) && !is_string($this->fname))
            throw new InvalidCallException("File name must be specified");
    }

    public function getRules()
    {
        return $this->rules;
    }

    protected function processMeta($line, $lnum)
    {
        
    }

}
