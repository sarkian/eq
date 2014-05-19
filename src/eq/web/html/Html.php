<?php

namespace eq\web\html;

class Html
{

    public static function link($anchor = null, $href = null, $options = array())
    {
        return self::tag('a',
            array_merge(array('href' => $href), $options),
            $anchor,
            false
        ).'</a>';
    }

    /**
     * @param $tag
     * @param array $options
     * @param null $content
     * @param bool $close
     * @return string
     */
    public static function tag($tag, $options = array(), $content = null, $close = true)
    {
        if(is_array($content))
            $content = implode("", $content);
        $html = '<'.$tag.self::renderAttrs($options);
        if($content !== null)
            return $close ? $html.'>'.$content.'</'.$tag.'>' : $html.'>'.$content;
        else
            return $close ? $html.' />' : $html.'>';
    }

    public static function renderAttrs($options)
    {
        $html = "";
        if(!$options)
            return $html;
        foreach($options as $name => $value) {
            if(is_array($value))
                $value = implode(" ", $value);
            if(is_int($name))
                $html .= ' '.$value.'="'.$value.'"';
            elseif(strlen((string) $value))
                $html .= ' '.$name.'="'.$value.'"';
        }
        return $html;
    }

}
