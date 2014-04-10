<?php

namespace eq\cgen\base;

/**
 * Представляет класс.
 * 
 * @author Sarkian <root@dustus.org> 
 * @doc TO_DO Write documentation
 * @test TO_DO Write test
 * @since 0.2
 */
class ClassBase
{

    /**
     * @var string Имя класса
     */
    protected $name;

    /**
     * @var eq\cgen\base\ConstantBase[] Массив констант класса
     */
    protected $constants = [];

    /**
     * @var eq\cgen\base\PropertyBase[] Массив свойств класса
     */
    protected $properties = [];

    /**
     * @var eq\cgen\base\MethodBase[] Массив методов класса
     */
    protected $methods = [];

    /**
     * Конструктор.
     * 
     * @param string $name Имя класса
     */
    public function __construct($name)
    {

    }

    /**
     * Добавляет константу в класс.
     * 
     * @param eq\cgen\base\ConstantBase $constant 
     * @return eq\cgen\base\ClassBase 
     */
    public function addConstant(ConstantBase $constant)
    {

    }

    /**
     * Добавляет свойство в класс.
     * 
     * @param eq\cgen\base\PropertyBase $property Добавляемое свойство
     * @return eq\cgen\base\ClassBase 
     */
    public function addProperty(PropertyBase $property)
    {

    }

    /**
     * Добавляет метод в класс.
     * 
     * @param eq\cgen\base\MethodBase $method Добавляемый метод
     * @return eq\cgen\base\ClassBase 
     */
    public function addMethod(MethodBase $method)
    {

    }

    /**
     * Рендер кода класса.
     * 
     * @param int $indent Отступ
     * @return string
     */
    public function render($indent = 0)
    {

    }

}
