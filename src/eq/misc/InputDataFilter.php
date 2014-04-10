<?php

namespace eq\misc;

use eq\base\DataTypeException;

/**
 * Базовый класс для фильтрации пользовательского ввода (`$_POST`, `$_GET`, `$argv`, etc.).
 * 
 * @author Sarkian <root@dustus.org> 
 * @doc docs/eq/misc/InputDataFilter.md
 * @test tests/eq/misc/InputDataFilterTest.php
 * @uses eq\base\DataTypeException
 */
class InputDataFilter
{

    /**
     * Фильтрует один или несколько элементов массива `$src`.
     * 
     * @doc docs/eq/misc/InputDataFilter/getVars.md
     * @param array $src 
     * @param mixed $name 
     * @param string $type 
     * @return mixed
     * @throws eq\base\DataTypeException|eq\base\LoaderException
     */
    public function getVars($src, $name = null, $type = null)
    {
        if(!$name) return $src;
        if(\is_array($name))
            return $this->getVarsArray($src, $name);
        elseif(\is_string($name))
            return $this->getOneVar($src, $name, $type);
        else
            throw new DataTypeException("Invalid name");
    }

    /**
     * Фильтрует значение `$var` в соответствии с типом `$type`.
     * 
     * @doc docs/eq/misc/InputDataFilter/filterVar.md
     * @param mixed $var 
     * @param string $type 
     * @return mixed
     * @throws eq\base\DataTypeException|eq\base\LoaderException
     */
    public function filterVar($var, $type = null)
    {
        if(!$type) return $var;
        if(!\is_string($type) || !\is_subclass_of($type, 'eq\datatypes\DataTypeBase'))
            throw new DataTypeException("Type must be a subclass of eq\\datatypes\\DataTypeBase");
        return $type::filter($var);
    }

    /**
     * Фильтрует каждый элемент массива `$input` *(не рекурсивно)* в соответствии с типом `$type`. 
     * 
     * @doc docs/eq/misc/InputDataFilter/filterArray.md
     * @param array $input 
     * @param string $type 
     * @return array
     * @throws eq\base\DataTypeException|eq\base\LoaderException
     */
    public function filterArray($input, $type = null)
    {
        if(!\is_array($input)) return [];
        $output = [];
        foreach($input as $name => $value)
            $output[$name] = $this->filterVar($value, $type);
        return $output;
    }

    /**
     * Рекурсивно фильтрует каждый элемент массива `$input` в соответствии с типом `$type`. 
     * 
     * @doc docs/eq/misc/InputDataFilter/filterArrayRecursive.md
     * @param array $input 
     * @param string $type 
     * @return array
     * @throws eq\base\DataTypeException|eq\base\LoaderException
     */
    public function filterArrayRecursive($input, $type = null)
    {
        if(!\is_array($input)) return [];
        $output = [];
        foreach($input as $name => $value) {
            if(\is_array($value))
                $output[$name] = $this->filterArrayRecursive($value, $type);
            else
                $output[$name] = $this->filterVar($value, $type);
        }
        return $output;
    }

    /**
     * Выбирает из массива `$src` и фильтрует элементы, указанные в `$vars`.
     * 
     * @doc docs/eq/misc/InputDataFilter/getVarsArray.md
     * @param array $src 
     * @param array $vars 
     * @return array
     * @throws eq\base\DataTypeException|eq\base\LoaderException
     */
    protected function getVarsArray($src, $vars)
    {
        $res = [];
        foreach($vars as $name => $type) {
            if(!$name || !\is_string($name))
                throw new DataTypeException("Index must be a not empty string");
            if(\is_string($type) || \is_null($type))
                $res[$name] = $this->getOneVar($src, $name, $type);
            elseif(\is_array($type)) {
                $cnt = \count($type);
                if(!$cnt || $cnt > 2 || !isset($type[0]))
                    throw new DataTypeException("Invalid type and newname");
                $newname = $type[0];
                if(!$newname || !\is_string($newname))
                    throw new DataTypeException("Invalid newname");
                $type = isset($type[1]) ? $type[1] : null;
                $res[$newname] = $this->getOneVar($src, $name, $type);
            }
            else
                throw new DataTypeException("Invalid type");
        }
        return $res;
    }

    /**
     * Выбирает из массива `$src` и фильтрует в соответствии с типом `$type` элемент с индексом `$name`. 
     * 
     * @doc docs/eq/misc/InputDataFilter/getOneVar.md
     * @param array $src 
     * @param string $name 
     * @param string $type 
     * @return mixed
     * @throws eq\base\DataTypeException|eq\base\LoaderException
     */
    protected function getOneVar($src, $name, $type = null)
    {
        $this->prevalidateVarName($name);
        $is_array = \substr($name, - 2) === '[]';
        if($is_array) $name = \substr($name, 0, \strlen($name) - 2);
        \preg_match_all('/^[^\[\]]+|\[([^\[\]]+)\]/', $name, $matches);
        if($matches && $matches[0] && $matches[1] && \count($matches[1]) > 1)
            $var = $this->processNestedVar($src, $name, $matches);
        else
            $var = $this->processVar($src, $name);
        return $is_array
            ? $this->filterArray($var, $type)
            : $this->filterVar($var, $type);
    }

    private function prevalidateVarName($name)
    {
        if(!\strlen($name) || substr($name, 0, 1) === '['
            || substr($name, 0, 1) === ']'
            || \preg_match('/^[^\[\]]+\]/', $name)
            || \preg_match('/\][^\[\]]+\[/', $name))
                throw new DataTypeException("Invalid name: $name");
    }

    private function processNestedVar($src, $name, $matches)
    {
        if(!\preg_match('/\[\]$|\[[^\[\]]+\]$/', $name))
            throw new DataTypeException("Invalid name: $name");
        $parts = $matches[1];
        $parts[0] = $matches[0][0];
        $var = $src;
        foreach($parts as $index)
            $var = isset($var[$index]) ? $var[$index] : null;
        return $var;
    }

    private function processVar($src, $name)
    {
        if(\strpos($name, '[') !== false || \strpos($name, ']') !== false)
            throw new DataTypeException("Invalid name: $name");
        return isset($src[$name]) ? $src[$name] : null;
    }

}
