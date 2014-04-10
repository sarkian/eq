<?php
/**
 * Last Change: 2014 Apr 08, 00:57
 */

namespace eq\cgen\base\docblock;

/**
 * Класс, наследуемый классами Docblock, TagList, Tag для сокрытия определённых методов.
 * 
 * @author Sarkian <root@dustus.org> 
 * @doc TO_DO Write documentation
 * @test TO_DO Write test
 */
abstract class DocblockAbstract
{

    /**
     * Переопределяется в TagList (protected).
     *
     * @see eq\cgen\base\docblock\TagList::addTag
     */
    protected function addTag(Tag $tag) {}

    /**
     * Переопределяется в TagList (protected).
     * 
     * @see eq\cgen\base\docblock\TagList::addTagByValue
     */
    protected function addTagByValue($value) {}

    /**
     * Переопределяется в TagList (protected).
     *
     * @see eq\cgen\base\docblock\TagList::addTagToRoot
     */
    protected function addTagToRoot(Tag $tag) {}

    /**
     * Переопределяется в TagList (protected).
     *
     * @see eq\cgen\base\docblock\TagList::previousAdded
     */
    protected function previousAdded() {}

    /**
     * Переопределяется в TagList (protected).
     *
     * @see eq\cgen\base\docblock\TagList::getByWFirst
     */
    protected function getByWFirst($word) {}

    /**
     * Переопределяется в TagList (protected).
     *
     * @see eq\cgen\base\docblock\TagList::getByWSecond
     */
    protected function getByWSecond($word) {}

    /**
     * Переопределяется в Tag (protected), TagList (protected) и Docblock (public).
     *
     * @see eq\cgen\base\docblock\Tag::render
     * @see eq\cgen\base\docblock\TagList::render
     * @see eq\cgen\base\docblock\Docblock::render
     */
    protected function render($indent = 0) {}

    /**
     * Переопределяется в Tag (protected) и TagList (public).
     *
     * @see eq\cgen\base\docblock\Tag::append
     * @see eq\cgen\base\docblock\TagList::append
     */
    protected function append($value) {}

    /**
     * Переопределяется в Tag (protected) и TagList (public).
     *  
     * @see eq\cgen\base\docblock\Tag::value
     * @see eq\cgen\base\docblock\TagList::value
     */
    protected function value($value = null) {}

    /**
     * Переопределяется в Tag (protected) и TagList (public).
     *  
     * @see eq\cgen\base\docblock\Tag::wfirst
     * @see eq\cgen\base\docblock\TagList::wfirst
     */
    protected function wfirst($value = null) {}

    /**
     * Переопределяется в Tag (protected) и TagList (public).
     *  
     * @see eq\cgen\base\docblock\Tag::wsecond
     * @see eq\cgen\base\docblock\TagList::wsecond
     */
    protected function wsecond($value = null) {}

    /**
     * Переопределяется в Tag (protected) и TagList (public).
     *
     * @see eq\cgen\base\docblock\Tag:fromfirst
     * @see eq\cgen\base\docblock\TagList::fromfirst
     */
    protected function fromfirst($value = null) {}

    /**
     * Переопределяется в Tag (protected) и TagList (public).
     *  
     * @see eq\cgen\base\docblock\Tag::fromsecond
     * @see eq\cgen\base\docblock\TagList::fromsecond
     */
    protected function fromsecond($value = null) {}

}
