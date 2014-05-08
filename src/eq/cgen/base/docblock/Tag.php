<?php
/**
 * Last Change: 2013 Oct 28, 13:44
 */

namespace eq\cgen\base\docblock;

/**
 * Представляет тег doc-блока.
 */
class Tag extends DocblockAbstract
{

    # @section Properties

    /**
     * @var string Имя тега (без "@").
     */
    protected $name;

    /**
     * @var string Значение тега.
     */
    protected $_value = "";

    /**
     * @var string Первое слово значения тега.
     */
    protected $_wfirst = "";

    /**
     * @var string Второе слово значения тега.
     */
    protected $_wsecond = "";

    /**
     * @var string Часть значения, идущая после первого слова.
     */
    protected $_fromfirst = "";

    /**
     * @var string Часть значения, идущая после второго слова.
     */
    protected $_fromsecond = "";

    # @endsection Properties

    /**
     * Конструктор.
     * @param string $name Имя тега
     * @param string $value Значение тега
     */
    public function __construct($name, $value = "")
    {
        $this->name = $name;
        $this->_value = $value;
        $this->processValue();
    }

    /**
     * Добавляет строку к значению тега.
     * @param string $value Добавляемая строка
     */
    protected function append($value)
    {
        $this->_value .= $value;
        $this->processValue();
    }

    /**
     * Устанавливает (если указан параметр) и возвращает значение тега.
     * @param string $value Новое значение
     * @return string Текущее значение тега.
     */
    protected function value($value = null)
    {
        if(is_null($value))
            return $this->_value;
        $this->_value = $value;
        $this->processValue();
        return $this->_value;
    }

    /**
     * Устанавливает (если указан параметр) и возвращает первое слово значения тега.
     * @param string $value Новое значение
     * @return string Первое слово текущего значения тега.
     */
    protected function wfirst($value = null)
    {
        if(is_null($value))
            return $this->_wfirst;
        $this->_value = trim($value, " \r\n\t");
        if($this->_wsecond)
            $this->_value .= $this->_value ? " ".$this->_wsecond : $this->_wsecond;
        if($this->_fromsecond)
            $this->_value .= $this->_value ? " ".$this->_fromsecond : $this->_fromsecond;
        $this->processValue();
        return $this->_wfirst;
    }

    /**
     * Устанавливает (если указан параметр) и возвращает второе слово значения тега.
     * @param string $value Новое значение
     * @return string Второе слово текущего значения тега.
     */
    protected function wsecond($value = null)
    {
        if(is_null($value))
            return $this->_wsecond;
        $value = trim($value, " \r\n\t");
        $this->_value = '';
        if($this->_wfirst)
            $this->_value = $this->_wfirst;
        if($value)
            $this->_value .= $this->_value ? " ".$value : $value;
        if($this->_fromsecond)
            $this->_value .= $this->_value ? " ".$this->_fromsecond : $this->_fromsecond;
        $this->processValue();
        return $this->_wsecond;
    }

    /**
     * Устанавливает (если указан параметр) и возвращает часть значения тега после первого слова.
     * @param string $value Новое значение
     * @return string Часть текущего значения тега после первого слова.
     */
    protected function fromfirst($value = null)
    {
        if(is_null($value))
            return $this->_fromfirst;
        $value = trim($value, " \r\n\t");
        $this->_value = $this->_wfirst;
        if($value)
            $this->_value .= $this->_value ? " ".$value : $value;
        $this->processValue();
        return $this->_fromfirst;
    }

    /**
     * Устанавливает (если указан параметр) и возвращает часть значения тега после второго слова.
     * @param string $value Новое значение
     * @return string Часть текущего значения тега после второго слова.
     */
    protected function fromsecond($value = null)
    {
        if(is_null($value))
            return $this->_fromsecond;
        $value = trim($value, " \r\n\t");
        $this->_value = $this->_wfirst;
        if($this->_wsecond)
            $this->_value .= $this->_value ? " ".$this->_wsecond : $this->_wsecond;
        if($value)
            $this->_value .= $this->_value ? " ".$value : $value;
        $this->processValue();
        return $this->_fromsecond;
    }

    /**
     * Рендерит тег.
     * @param int $indent Отступ в пробелах
     * @return string Часть докблока (не комментарий полностью!), содержащая тег.
     */
    protected function render($indent = 0)
    {
        $indent_str = str_repeat(" ", $indent);
        return $indent_str." * @".$this->name." ".preg_replace(
            "/[\r\n]+/", "\n".$indent_str." * ", trim($this->_value, " \r\n\t"));
    }

    /**
     * Парсит текущее значение тега.
     */
    protected function processValue()
    {
        $this->_value = trim($this->_value, " \r\n\t");
        $words = preg_split("/[\s\t\n\r]+/", $this->_value, 3);
        $this->_wfirst = isset($words[0]) ? trim($words[0], " \r\n\t") : "";
        $this->_wsecond = isset($words[1]) ? trim($words[1], " \r\n\t") : "";
        $this->_fromsecond = isset($words[2]) ? trim($words[2], " \r\n\t") : "";
        $this->_fromfirst = trim(preg_replace("/^[^\s]+[\s\t\n\r]*/", "", $this->_value), " \r\n\t");
    }

}
