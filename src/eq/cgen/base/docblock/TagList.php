<?php

namespace eq\cgen\base\docblock;

use eq\base\InvalidArgumentException;

/**
 * Представляет список тегов с одинаковым именем.
 */
class TagList extends DocblockAbstract
{

    # @section Constants
    /**
     * @const int Первое слово в значении тега. Используется при вызове assoc().
     */
    const A_WFIRST = 0;

    /**
     * @const int Второе слово в значении тега. Используется при вызове assoc().
     */
    const A_WSECOND = 1;

    /**
     * @const int Часть значения тега после первого слова.
     *      Используется при вызове assoc().
     */
    const A_FROMFIRST = 2;

    /**
     * @const int Часть значения тега после второго слова.
     *      Используется при вызове assoc().
     */
    const A_FROMSECOND = 3;
    # @endsection Constants

    # @section Properties
    /**
     * @var string Имя тега.
     */
    protected $name;

    /**
     * @var Tag[] Массив тегов.
     */
    protected $tags = [];

    /**
     * @var Tag Последний добавленный тег.
     */
    protected $previous_added = null;

    /**
     * @var string Значение искомого/добавляемого тега.
     */
    protected $_value;

    /**
     * @var string Первое слово искомого/добавляемого тега.
     */
    protected $_wfirst;

    /**
     * @var string Второе слово искомого/добавляемого тега.
     */
    protected $_wsecond;

    /**
     * @var string Значение искомого/добавляемого тега, исключая первое слово.
     */
    protected $_fromfirst;

    /**
     * @var string Значение искомого/добавляемого тега, исключая первые 2 слова.
     */
    protected $_fromsecond;

    /**
     * @var TagList Родительский список, куда будут
     *      добавлены теги методами add(), addOnce() и addTagToRoot().
     */
    protected $parent_list;

    protected $assoc_methods = [
        self::A_WFIRST => 'wfirst',
        self::A_WSECOND => 'wsecond',
        self::A_FROMFIRST => 'fromfirst',
        self::A_FROMSECOND => 'fromsecond',
    ];
    # @endsection Properties

    /**
     * Конструктор.
     * @param string $name Имя всех тегов в данном списке
     * @param string $wfirst Первое слово искомого тега
     * @param string $wsecond Второе слово искомого тега
     * @param TagList $parent_list Родительский список,
     *      куда будут добавлены теги методами add(), addOnce(), addTagToRoot().
     */
    public function __construct($name, $wfirst = null,
                                $wsecond = null, TagList $parent_list = null)
    {
        $this->name = $name;
        $this->_wfirst = $wfirst;
        $this->_wsecond = $wsecond;
        $this->parent_list = $parent_list;
    }

    # @section API
    /**
     * Проверяет наличие тегов в списке.
     * @return bool TRUE, если в списке есть хоть один тег, иначе FALSE.
     */
    public function exists()
    {
        return !empty($this->tags);
    }

    /**
     * Возвращает количество тегов в списке.
     * @return int Количество тегов в списке.
     */
    public function count()
    {
        return count($this->tags);
    }

    /**
     * Добавляет строку к значению первого тега.
     * @param string $value Добавляемая строка
     * @return TagList $this
     */
    public function append($value)
    {
        if($this->exists())
            $this->tags[0]->append($value);
        return $this;
    }

    /**
     * Возвращает или устанавливает значение первого тега в списке.
     * @param string $value Новое значение
     * @return TagList Значение первого тега в
     *      списке или $this, если был указан параметр.
     */
    public function value($value = null)
    {
        $this->_value = $value;
        $res = $this->exists() ? $this->tags[0]->value($value) : "";
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает или устанавливает первое слово значения первого тега в списке.
     * @param string $value Новое значение
     * @return TagList Первое слово значения
     *      первого тега в списке или $this, если был указан параметр.
     */
    public function wfirst($value = null)
    {
        $this->_wfirst = $value;
        $res = $this->exists() ? $this->tags[0]->wfirst($value) : "";
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает или устанавливает второе слово значения первого тега в списке.
     * @param string $value Новое значение
     * @return TagList Второе слово значения
     *      первого тега в списке или $this, если был указан параметр.
     */
    public function wsecond($value = null)
    {
        $this->_wsecond = $value;
        $res = $this->exists() ? $this->tags[0]->wsecond($value) : "";
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает или устанавливает часть значения после
     *      первого слова первого тега в списке.
     * @param string $value Новое значение
     * @return TagList Часть значения после первого
     *      слова первого тега в списке или $this, если был указан параметр.
     */
    public function fromfirst($value = null)
    {
        $this->_fromfirst = $value;
        $res = $this->exists() ? $this->tags[0]->fromfirst($value) : "";
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает или устанавливает часть значения после
     *      второго слова первого тега в списке.
     * @param string $value Новое значение
     * @return TagList Часть значения после
     *      второго слова первого тега в списке $this, если был указан параметр.
     */
    public function fromsecond($value = null)
    {
        $this->_fromsecond = $value;
        $res = $this->exists() ? $this->tags[0]->fromsecond($value) : "";
        return is_null($value) ? $res : $this;
    }

    /**
     * Добавляет строку к значению каждого тега в списке.
     * @param string $value Добавляемая строка
     * @return TagList
     */
    public function appendAll($value)
    {
        foreach($this->tags as $tag)
            $tag->append($value);
        return $this;
    }

    /**
     * Возвращает или устанавливает значения всех тегов в списке.
     * @param string $value Новое значение
     * @return array|TagList Массив значений всех тегов
     *      в списке или $this, если был указан параметр.
     */
    public function valueAll($value = null)
    {
        $res = [];
        foreach($this->tags as $tag)
            $res[] = $tag->value($value);
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает или устанавливает первое слово значения каждого тега в списке.
     * @param string $value Новое значение
     * @return array|TagList Массив, содержащий
     *      первое слово каждого тега в списке $this, если был указан параметр.
     */
    public function wfirstAll($value = null)
    {
        $res = [];
        foreach($this->tags as $tag)
            $res[] = $tag->wfirst($value);
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает или устанавливает второе слово значения каждого тега в списке.
     * @param string $value Новое значение
     * @return array|TagList Массив, содержащий второе
     *      слово каждого тега в списке или $this, если был указан параметр.
     */
    public function wsecondAll($value = null)
    {
        $res = [];
        foreach($this->tags as $tag)
            $res[] = $tag->wsecond($value);
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает или устанавливает часть значения после первого слова
     *      каждого тега в списке.
     * @param string $value Новое значение
     * @return array|TagList Массив, содержащий часть
     *      значения после первого слова каждого тега в списке
     *      или $this, если был указан параметр.
     */
    public function fromfirstAll($value = null)
    {
        $res = [];
        foreach($this->tags as $tag)
            $res[] = $tag->fromfirst($value);
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает или устанавливает часть значения после
     *      второго слова каждого тега в списке.
     * @param string $value Новое значение
     * @return array|TagList Массив, содержащий часть
     *      значения после второго слова каждого тега в списке
     *      или $this, если был указан параметр.
     */
    public function fromsecondAll($value = null)
    {
        $res = [];
        foreach($this->tags as $tag)
            $res[] = $tag->fromsecond($value);
        return is_null($value) ? $res : $this;
    }

    /**
     * Возвращает ассоциативный массив из частей значений тегов.
     * @param int $keys Что использовать в качестве ключей (см. константы)
     * @param int $values Что использовать в качестве значений (см. константы)
     * @param bool $no_rewrite_keys Не переписывать значение,
     *      если ключ втречается более одного раза
     * @throws InvalidArgumentException В случае некорректных
     *      параметров $keys и $values.
     * @return array Ассоциативный массив из частей значений тегов.
     */
    public function assoc($keys, $values, $no_rewrite_keys = false)
    {
        if(!is_int($keys) || !isset($this->assoc_methods[$keys]))
            throw new InvalidArgumentException($this, "assoc", "keys", $keys);
        if(!is_int($values) || !isset($this->assoc_methods[$values]))
            throw new InvalidArgumentException($this, "assoc", "values", $values);
        $keys_method = $this->assoc_methods[$keys];
        $values_method = $this->assoc_methods[$values];
        $res = [];
        foreach($this->tags as $tag) {
            $key = $tag->{$keys_method}();
            if($no_rewrite_keys && isset($res[$key]))
                continue;
            $res[$key] = $tag->{$values_method}();
        }
        return $res;
    }

    /**
     * Добавляет тег с искомыми критериями в список.
     */
    public function add()
    {
        $tag = new Tag($this->name);
        if(!is_null($this->_value))
            $tag->value($this->_value);
        if(!is_null($this->_wfirst))
            $tag->wfirst($this->_wfirst);
        if(!is_null($this->_wsecond))
            $tag->wsecond($this->_wsecond);
        if(!is_null($this->_fromfirst))
            $tag->fromfirst($this->_fromfirst);
        if(!is_null($this->_fromsecond))
            $tag->fromsecond($this->_fromsecond);
        $this->addTagToRoot($tag);
    }

    /**
     * Добавляет тег с искомыми параметрами в список, только если список пуст.
     */
    public function addOnce()
    {
        if(!$this->exists())
            $this->add();
    }

    /**
     * Удаляет первый тег в списке.
     * @return TagList Текущий объект класса.
     */
    public function remove()
    {
        if($this->tags) {
            $tag = array_shift($this->tags);
            if($this->parent_list)
                $this->parent_list->removeTag($tag);
        }
        return $this;
    }

    /**
     * Удалаяет все теги в списке.
     * @return TagList Текущий объект класса.
     */
    public function removeAll()
    {
        if($this->parent_list) {
            foreach($this->tags as $tag)
                $this->parent_list->removeTag($tag);
        }
        $this->tags = [];
        return $this;
    }

    /**
     * Удаляет все теги в списке, кроме первого.
     * @return TagList Текущий объект класса.
     */
    public function removeDuplicates()
    {
        if($this->tags) {
            $tag = array_shift($this->tags);
            if($this->parent_list) {
                foreach($this->tags as $tag_)
                    $this->parent_list->removeTag($tag_);
            }
            $this->tags = [$tag];
        }
        return $this;
    }
    # @endsection API

    /**
     * Сбрасывает критерии искомых/добавляемых тегов.
     */
    protected function clearCriteries()
    {
        $this->_value = null;
        $this->_wfirst = null;
        $this->_wsecond = null;
        $this->_fromfirst = null;
        $this->_fromsecond = null;
    }

    /**
     * Добавляет тег.
     * @param Tag $tag Добавляемый тег
     */
    protected function addTag(Tag $tag)
    {
        $this->tags[] = $tag;
        $this->previous_added = $tag;
    }

    /**
     * Удаляет тег из текущего списка и родительского, если таковой назначен.
     * @param Tag $tag Удаляемый тег
     */
    protected function removeTag(Tag $tag)
    {
        foreach($this->tags as $i => $tag_) {
            if($tag_ === $tag) {
                unset($this->tags[$i]);
                $this->tags = array_merge($this->tags);
                if($this->parent_list)
                    $this->parent_list->removeTag($tag);
            }
        }
    }

    /**
     * Добавляет тег со значением $value.
     * @param string $value Значение добавляемого тега
     */
    protected function addTagByValue($value)
    {
        $tag = new Tag($this->name, $value);
        $this->previous_added = $tag;
        $this->tags[] = $tag;
    }

    /**
     * Добавляет тег в родительский список или в текущий,
     *      если родительский не задан.
     * @param Tag $tag Добавляемый тег
     */
    protected function addTagToRoot(Tag $tag)
    {
        if($this->parent_list)
            $this->parent_list->addTagToRoot($tag);
        else
            $this->addTag($tag);
        $this->clearCriteries();
    }

    /**
     * Возвращает последний добавленный тег.
     *      Если в списке нет ни одного тега -
     *      сначала добавляет тег с пустым значением.
     * @return Tag Последний добавленный тег.
     */
    protected function previousAdded()
    {
        if(!$this->previous_added)
            $this->addTag(new Tag($this->name));
        return $this->previous_added;
    }

    /**
     * Возвращает новый список тегов,
     *      первое слово значений которых совпадает с $word.
     * @param string $word Первое слово значения тега;
     *      если начинается с "/" - трактуется как регэксп
     * @return TagList Новый список тегов,
     *      являющийся дочерним списком текущего.
     */
    protected function getByWFirst($word)
    {
        $this->_wfirst = $word;
        $res = new TagList($this->name, $word, $this->_wsecond, $this);
        $compfunc = $this->createComparsionFunction($word);
        foreach($this->tags as $tag) {
            if($compfunc($tag->wfirst()))
                $res->addTag($tag);
        }
        return $res;
    }

    /**
     * Возвращает новый список тегов,
     *      второе слово значений которых совпадает с $word.
     * @param string $word Второе слово значения тега;
     *      если начинается с "/" - трактуется как регэксп
     * @return TagList Новый список тегов,
     *      являющийся дочерним списком текущего.
     */
    protected function getByWSecond($word)
    {
        $this->_wsecond = $word;
        $res = new TagList($this->name, $this->_wfirst, $word, $this);
        $compfunc = $this->createComparsionFunction($word);
        foreach($this->tags as $tag) {
            if($compfunc($tag->wsecond()))
                $res->addTag($tag);
        }
        return $res;
    }

    /**
     * Рендерит все теги в списке.
     * @param int $indent Отступ в пробелах
     * @return string Часть док-блока (не комментарий полностью!),
     *      содержащая все теги в списке.
     */
    protected function render($indent = 0)
    {
        $out = [];
        foreach($this->tags as $tag)
            $out[] = $tag->render($indent);
        return implode("\n", $out);
    }

    /**
     * Создаёт и возвращает функцию сравнения первого или второго слова тега.
     * @param string $word Слово, с которым будет проводиться сравнение;
     *      если начинается с "/" - трактуется как регэксп
     * @return callable
     * @usedby getByWFirst
     * @usedby getByWSecond
     */
    protected function createComparsionFunction($word)
    {
        if(strncmp($word, '/', 1))
            return function ($tag_word) use ($word) {
                return $word === $tag_word;
            };
        else
            return function ($tag_word) use ($word) {
                return (bool) preg_match($word, $tag_word);
            };
    }

}
