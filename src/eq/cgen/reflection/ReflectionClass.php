<?php

namespace eq\cgen\reflection;

/**
 * ReflectionClass 
 * 
 * @author Sarkian <root@dustus.org> 
 * @doc TO_DO Write documentation
 * @test TO_DO Write test
 */
class ReflectionClass extends \ReflectionClass
{

    use TDocBlock;

    /**
     * __construct 
     * 
     * @param mixed $argument 
     * @return void
     */
    public function __construct($argument)
    {
        parent::__construct($argument);
        $this->processDocBlock();
    }

    /**
     * Возвращает методы, определённые в классе. 
     * 
     * @param int $filter 
     * @return eq\cgen\reflection\ReflectionMethod[]
     */
    public function getDeclaredMethods($filter = 1799)
    {
        return \array_filter(
            $this->getMethods($filter),
            function(ReflectionMethod $method) {
                return $method->class === $this->name;
            }
        );
    }

    /**
     * Возвращает унаследованные методы. 
     * 
     * @param int $filter 
     * @return eq\cgen\reflection\ReflectionMethod[]
     */
    public function getInheritedMethods($filter = 1799)
    {
        return \array_filter(
            $this->getMethods($filter),
            function(ReflectionMethod $method) {
                return $method->class !== $this->name;
            }
        );
    }

    /**
     * getProperties 
     * 
     * @param int $filter 
     * @return eq\cgen\reflection\ReflectionProperty[]
     * @override
     */
    public function getProperties($filter = 1793)
    {
        $props = parent::getProperties($filter);
        return \array_map(function(\ReflectionProperty $prop) {
            return new ReflectionProperty($prop->class, $prop->name);
        }, $props);
    }

    /**
     * getMethods 
     * 
     * @param string $filter 
     * @return eq\cgen\reflection\ReflectionMethod[]
     * @override
     */
    public function getMethods($filter = 1799)
    {
        $methods = parent::getMethods($filter);
        return \array_map(function(\ReflectionMethod $method) {
            return new ReflectionMethod($method->class, $method->name);
        }, $methods);
    }

    public function getMethod($name)
    {
        return new ReflectionMethod($this->name, $name);
    }

}
