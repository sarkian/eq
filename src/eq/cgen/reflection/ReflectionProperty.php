<?php
/**
 * Last Change: 2013 Oct 28, 12:44
 */

namespace eq\cgen\reflection;

class ReflectionProperty extends \ReflectionProperty implements IDefinitionString
{

    use TDocBlock;

    public function __construct($class, $name)
    {
        parent::__construct($class, $name);
        $this->processDocBlock();
    }

    public function getDocShortDescr()
    {
        if($this->docblock_short_descr)
            return $this->docblock_short_descr;
        $vars = $this->getDocblockTag('var');
        if(!isset($vars[0])) return '';
        $vars = $vars[0];
        if(!isset($vars[1])) return '';
        return implode(' ', array_slice($vars, 1));
    }

    public function getDocType()
    {
        $tags = $this->getDocblockTag('var');
        if(isset($tags[0][0])) return $tags[0][0];
        else return 'mixed';
    }

    public function getDefinitionCodeString()
    {

    }

    public function getDefinitionProtoString()
    {
        return $this->getModifiersString().' '.
            $this->getDocType().' '.
            '$'.$this->name;
    }

    public function getModifiersString()
    {
        $str = [];
        if($this->isPublic())
            $str[] = 'public';
        if($this->isProtected())
            $str[] = 'protected';
        if($this->isPrivate())
            $str[] = 'private';
        if($this->isStatic())
            $str[] = 'static';
        return \implode(' ', $str);
    }

}
