<?php

namespace eq\cgen\base\docblock;

/**
 * Представляет doc-блок класса, метода, файла и т.д.
 */
class Docblock extends DocblockAbstract
{

    /**
     * @var TagList[] Массив списков тегов. Заполняется при вызове processDocblock().
     */
    protected $tags = [];

    /**
     * @var string Краткое описание.
     */
    protected $descr_short = "";

    /**
     * @var string Подробное описание.
     */
    protected $descr_long = "";

    /**
     * Конструктор.
     * @param string $doc_comment Doc-блок
     */
    public function __construct($doc_comment = "")
    {
        $this->processDocblock($doc_comment);
    }

    /**
     * Возвращает имена всех встречающихся в блоке тегов (без "@").
     * @return array
     */
    public function getAllUsedTags()
    {
        return array_keys($this->tags);
    }

    /**
     * Удаляет все теги.
     */
    public function clearTags()
    {
        $this->tags = [];
    }

    /**
     * Возвращает или устанавливает краткое описание.
     * @param string $value Краткое описание, если его нужно установить
     * @return string|Docblock Краткое описание или текущий инстанс класса, если передан параметр $value.
     */
    public function shortDescription($value = null)
    {
        if(is_null($value))
            return $this->descr_short;
        $this->descr_short = $value;
        return $this;
    }

    /**
     * Возвращает или устанавливает подробное описание.
     * @param string $value Подробное описание, если его нужно установить
     * @return string|Docblock Подробное описание или текущий инстанс класса, если передан параметр $value.
     */
    public function longDescription($value = null)
    {
        if(is_null($value))
            return $this->descr_long;
        $this->descr_long = $value;
        return $this;
    }

    /**
     * Возвращает объект, содержащий теги, подходящие под указанные критерии.
     * @param string $name Имя тега (без "@")
     * @param string $wfirst Первое слово значения тега; если начинается с "/" - трактуется как регэксп
     * @param string $wsecond Второе слово значения тега; если начинается с "/" - трактуется как регэксп
     * @return TagList Объект, содержащий теги, подходящие под указанные критерии
     */
    public function tag($name, $wfirst = null, $wsecond = null)
    {
        if(!isset($this->tags[$name]))
            $this->tags[$name] = new TagList($name, $wfirst, $wsecond);
        $tags = $this->tags[$name];
        if(!is_null($wfirst))
            $tags = $tags->getByWFirst($wfirst);
        if(!is_null($wsecond))
            $tags = $tags->getByWSecond($wsecond);
        return $tags;
    }

    /**
     * Рендерит doc-блок.
     * @param int $indent Отступ в пробелах для каждой строки
     * @return string Doc-блок, готовый для вставки перед методом, классом, etc.
     */
    public function render($indent = 0)
    {
        $indent_str = str_repeat(" ", $indent);
        $out = [$indent_str."/**"];
        if($this->descr_short)
            $out = array_merge($out, $this->renderDescription($this->descr_short, $indent_str));
        if($this->descr_long)
            $out = array_merge($out, $this->renderDescription($this->descr_long, $indent_str));
        if(!$this->tags && count($out) > 1)
            array_pop($out);
        foreach($this->tags as $tag) {
            $rendered = rtrim($tag->render($indent));
            if($rendered)
                $out[] = $rendered;
        }
        if(count($out) < 2)
            $out[] = $indent_str." *";
        $out[] = $indent_str." */";
        return implode("\n", $out);
    }

    /**
     * Обрабатывает описание, разбивая его на строки, пригодные для вставки в doc-блок.
     * @param string $descr Описание
     * @param string $indent_str Отступ
     * @return array Массив строк (с отступом и " * ") описания.
     */
    protected function renderDescription($descr, $indent_str = "")
    {
        $lines = array_map(function ($line) use ($indent_str) {
            return $indent_str." * ".$line;
        }, explode("\n", $descr));
        $lines[] = $indent_str." *";
        return $lines;
    }

    /**
     * Парсит doc-блок.
     * @param string $doc Doc-блок
     */
    protected function processDocblock($doc)
    {
        if(!$doc)
            return;
        $doc = array_map(function ($line) {
            return preg_replace('/^\*\s{0,1}/', "", trim($line, " \r\n\t"));
        }, preg_split("/[\r\n]+/", $doc));
        if(array_shift($doc) !== "/**")
            return;
        if(array_pop($doc) !== "/")
            return;
        $f_sdescr = false;
        $f_ldescr = false;
        $prevtag = false;
        $descr_short = [];
        $descr_long = [];
        foreach($doc as $line) {
            if(preg_match('/^\@[a-zA-Z]+/', $line, $matches)) {
                $f_sdescr = true;
                $f_ldescr = true;
                $tagname = substr(trim($matches[0], " \r\n\t"), 1);
                $tagval = preg_replace('/^\@[a-zA-Z]+\s*/', "", $line);
                if(!isset($this->tags[$tagname]))
                    $this->tags[$tagname] = new TagList($tagname);
                $this->tags[$tagname]->addTagByValue($tagval);
                $prevtag = $tagname;
            } elseif($line) {
                if($prevtag)
                    $this->tags[$prevtag]->previousAdded()->append("\n".$line);
                elseif(!$f_sdescr) {
                    $descr_short[] = $line;
                } elseif(!$f_ldescr)
                    $descr_long[] = $line;
            } else {
                $prevtag = false;
                if($descr_short)
                    $f_sdescr = true;
                if($f_sdescr && !$f_ldescr && $descr_long)
                    $descr_long[] = $line;
            }
        }
        $this->descr_short = trim(implode("\n", $descr_short), " \r\n\t");
        $this->descr_long = trim(implode("\n", $descr_long), " \r\n\t");
    }

}
