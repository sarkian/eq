<?php

namespace eq\cgen\reflection;

/**
 * Реализует парсинг doc-comment'а.
 *
 * @author Sarkian <root@dustus.org> 
 * @doc TO_DO Write documentation
 * @test TO_DO Write test
 * @uses ReflectionClass::getDocComment()
 * @uses ReflectionFunctionAbstract::getDocComment()
 */
trait TDocBlock
{

    /**
     * @var array[] Массив значений тегов вида [tag => [line1, line2]]
     */
    protected $docblock_tags = [];

    /**
     * @var string Краткое описание
     */
    protected $docblock_short_descr = '';

    /**
     * @var string Развёрнутое описание
     */
    protected $docblock_long_descr = '';

    /**
     * @var array Кэш значений запрашиваемых методом getDocblockTagValues
     */
    protected $cache = [];

    /**
     * Возвращает краткое описание класса/метода/etc. 
     * 
     * @return string Краткое описание или пустая строка, если таковое отсутствует
     */
    public function getDocShortDescr()
    {
        return $this->docblock_short_descr;
    }

    /**
     * Возвращает развёрнутое описание класса/метода/etc. 
     * 
     * @return string Развёрнутое описание или пустая строка, если таковое отсутствует
     */
    public function getDocLongDescr()
    {
        return $this->docblock_long_descr;
    }

    /**
     * Возвращает тип параметра метода, если таковой определён. 
     * 
     * @param string $param_name Имя параметра
     * @return string Тип параметра или "mixed", если таковой не определён
     */
    public function getDocParamType($param_name)
    {
        if(isset($this->cache["__param.type.$param_name"]))
            return $this->cache["__param.type.$param_name"];
        $params = $this->getDocblockTag('param');
        $params or $params = [];
        $type = '';
        foreach($params as $param) {
            if(isset($param[1]) && $param[1] === '$'.$param_name) {
                if(isset($param[0]) && $param[0])
                    $type = $param[0];
                break;
            }
        }
        $type or $type = 'mixed';
        $this->cache["__param.type.$param_name"] = $type;
        return $type;
    }

    /**
     * Возвращает описание параметра, если таковое определено. 
     * 
     * @param string $param_name Имя параметра
     * @return string Описание параметра или пустая строка, если таковое не определено
     */
    public function getDocParamDescr($param_name)
    {
        if(isset($this->cache["__param.descr.$param_name"]))
            return $this->cache["__param.descr.$param_name"];
        $params = $this->getDocblockTag('param');
        $params or $params = [];
        $descr = '';
        foreach($params as $param) {
            if(isset($param[1]) && $param[1] === '$'.$param_name) {
                $descr = \implode(' ', \array_slice($param, 2));
                break;
            }
        }
        $this->cache["__param.descr.$param_name"] = $descr;
        return $descr;
    }

    /**
     * Возвращает значения всех тегов @see. 
     * 
     * @return array Массив значений всех тегов @see, без дубликатов
     */
    public function getDocSee()
    {
        return $this->getDocblockTagValues('see');
    }

    /**
     * Возвращает типы исключений, обозначенных в докблоке тегом @throws. 
     * 
     * @return array Все обозначенные типы исключений, без дубликатов
     */
    public function getDocThrows()
    {
        return $this->getDocblockTagValues('throws', true);
    }

    public function getDocThrowsDescr()
    {
        $throws = [];
        $tags = $this->getDocblockTag('throws');
        if(!$tags) return $throws;
        // print_r($tags);
        foreach($tags as $tag) {
            if($tag && isset($tag[0], $tag[1])) {
                if(!\preg_match('/^[a-zA-Z0-9_\\\]+$/', $tag[0]))
                    continue;
                if(isset($throws[$tag[0]]))
                    continue;
                $throws[$tag[0]] = \implode(' ', \array_slice($tag, 1));
            }
        }
        return $throws;
    }

    /**
     * Присутствует ли тег @api в докблоке. 
     * 
     * @return bool true, если присутствует (не зависимо от значения), в противном случае false
     */
    public function getDocApi()
    {
        return !\is_null($this->getDocblockTag('api'));
    }

    /**
     * Возвращает все упоминания тега $tag или null, если таковые отсутствуют.
     * 
     * @param string $tag Имя тега
     * @return array|null Массив значений тега, каждое из которых разбито по пробелам или null
     */
    public function getDocblockTag($tag)
    {
        return isset($this->docblock_tags[$tag]) ? $this->docblock_tags[$tag] : null;
    }

    /**
     * Возвращает все значения тега $tag в докблоке, исключая дубликаты. 
     * 
     * @param string $tag Имя тега
     * @param bool $allow_or Допускать ли использование '|' как разделителя (e.g. @return string|array)
     * @param bool $allow_space Допускать ли пробелы в значении тега
     * @return array Все значения тега $tag, исключая дубликаты
     */
    public function getDocblockTagValues($tag, $allow_or = false, $allow_space = false)
    {
        if(!isset($this->cache[$tag]) || !\is_array($this->cache[$tag])) {
            $defs = $this->getDocblockTag($tag);
            if($defs) {
                $res = [];
                foreach($defs as $def) {
                    if(!$def) continue;
                    $line = $allow_space ? \implode(' ', $def) : \array_shift($def);
                    $res = array_merge($res, $allow_or ? \explode('|', $line) : [$line]);
                }
                $this->cache[$tag] = \array_unique(
                    array_diff($res, [''])
                );
            }
            else
                $this->cache[$tag] = [];
        }
        return $this->cache[$tag];
    }

    /**
     * Парсит докблок. Вызывается в конструторе. 
     * 
     * @return void
     * @uses ReflectionClass::getDocComment()
     * @uses ReflectionFunctionAbstract::getDocComment()
     */
    protected function processDocBlock()
    {
        $doc = \preg_split("/[\r\n]+/", $this->getDocComment());
        $doc = \array_map(function($line) {
            return \preg_replace('/^\*\s*/', '', \trim($line, " \r\n\t"));
        }, $doc);
        if(\array_shift($doc) !== '/**')
            return;
        if(\array_pop($doc) !== '/')
            return;
        $f_sdescr = false;
        $f_ldescr = false;
        $descr = [];
        foreach($doc as $line) {
            if(\preg_match('/^\@[a-zA-Z]+/', $line)) {
                $f_sdescr = true;
                $f_ldescr = true;
                $line = \preg_split('/[\s\t]+/', $line);
                $tagname = \substr(\array_shift($line), 1);
                $this->docblock_tags[$tagname][] = $line;
            }
            elseif($line) {
                if(!$f_sdescr) {
                    $this->docblock_short_descr = $line;
                    $f_sdescr = true;
                }
                elseif(!$f_ldescr) {
                    $descr[] = $line;
                }
            }
            elseif(!$f_ldescr && $descr)
                $descr[] = $line;
        }
        $this->docblock_short_descr = \trim($this->docblock_short_descr, " \r\n\t");
        $this->docblock_long_descr = \trim(\implode("\n", $descr), " \r\n\t");
    }

}
