<?php

namespace eq\console;

/**
 * Базовый класс для консольных команд. 
 * 
 * @author Sarkian <root@dustus.org> 
 * @doc TO_DO Write documentation
 * @test TO_DO Write test
 */
class Command
{

    private $reflection;

    public function __construct($reflection)
    {
        $this->reflection = $reflection;
    }

    /**
     * Выводит справку по комманде. 
     * 
     * @return void
     */
    protected function getCommandHelp()
    {
        // TO_DO Implement
    }

    protected function getActionHelp()
    {
        // TO_DO Implement
    }

}
