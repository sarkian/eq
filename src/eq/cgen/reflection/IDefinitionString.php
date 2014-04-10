<?php
/**
 * Last Change: 2013 Oct 16, 13:50
 */

namespace eq\cgen\reflection;

interface IDefinitionString
{

    public function getDefinitionCodeString();
    public function getDefinitionProtoString();
    public function getModifiersString();

}
