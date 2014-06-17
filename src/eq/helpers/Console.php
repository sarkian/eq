<?php

namespace eq\helpers;

class Console
{

    /**
     * Foreground colors
     */
    const FG_DEFAULT        = 39;
    const FG_BLACK          = 30;
    const FG_RED            = 31;
    const FG_GREEN          = 32;
    const FG_YELLOW         = 33;
    const FG_BLUE           = 34;
    const FG_MAGENTA        = 35;
    const FG_CYAN           = 36;
    const FG_LIGHT_GRAY     = 37;
    const FG_DARK_GRAY      = 90;
    const FG_LIGHT_RED      = 91;
    const FG_LIGHT_GREEN    = 92;
    const FG_LIGHT_YELLOW   = 93;
    const FG_LIGHT_BLUE     = 94;
    const FG_LIGHT_MAGENTA  = 95;
    const FG_LIGHT_CYAN     = 96;
    const FG_WHITE          = 97;

    /**
     * Background colors
     */
    const BG_DEFAULT        = 49;
    const BG_BLACK          = 40;
    const BG_RED            = 41;
    const BG_GREEN          = 42;
    const BG_YELLOW         = 43;
    const BG_BLUE           = 44;
    const BG_MAGENTA        = 45;
    const BG_CYAN           = 46;
    const BG_LIGHT_GRAY     = 47;
    const BG_DARK_GRAY      = 100;
    const BG_LIGHT_RED      = 101;
    const BG_LIGHT_GREEN    = 102;
    const BG_LIGHT_YELLOW   = 103;
    const BG_LIGHT_BLUE     = 104;
    const BG_LIGHT_MAGENTA  = 105;
    const BG_LIGHT_CYAN     = 106;
    const BG_WHITE          = 107;

    /**
     * Formatting
     */
    const BOLD              = 1;
    const DIM               = 2;
    const ITALIC            = 3;
    const UNDERLINE         = 4;
    const BLINK             = 5;
    const INVERTED          = 7;
    const HIDDEN            = 8;
    const CROSSED_OUT       = 9;
    const FRAMED            = 51;
    const ENCIRCLED         = 52;
    const OVERLINED         = 53;

    /**
     * Formatting reset
     */
    const NORMAL            = 0;
    const END_BOLD          = 21;
    const END_DIM           = 22;
    const END_ITALIC        = 23;
    const END_UNDERLINE     = 24;
    const END_BLINK         = 25;
    const END_INVERTED      = 27;
    const END_HIDDEN        = 28;
    const END_CROSSED_OUT   = 29;

    public static function stdin($raw = false)
    {
        return $raw ? fgets(STDIN) : trim(fgets(STDIN), " \r\n\t");
    }

    public static function stdout($str, $nl = true)
    {
        if($nl)
            $str .= "\n";
        return fwrite(STDOUT, $str);
    }

    public static function stderr($str, $nl = true)
    {
        if($nl)
            $str .= "\n";
        return fwrite(STDERR, $str);
    }

    public static function fmtOut($str)
    {
        $fmt = func_get_args();
        array_shift($fmt);
        self::stdout(self::fmt($str, self::normalizeFmt($fmt)));
    }

    public static function fmtErr($str)
    {
        $fmt = func_get_args();
        array_shift($fmt);
        self::stderr(self::fmt($str, self::normalizeFmt($fmt)));
    }

    /**
     * @param string $str
     * @param int|array $format, ...
     * @return string
     */
    public static function fmt($str, $format = null)
    {
        $fmt = func_get_args();
        array_shift($fmt);
        $fmt = self::normalizeFmt($fmt);
        return $fmt ? self::seq($fmt).$str.self::seq() : $str;
    }

    public static function seq()
    {
        return "\033[".implode(";", self::normalizeFmt(func_get_args()))."m";
    }

    public static function fmtOption($option, $description,
                                     $indent = 4, $color = self::FG_GREEN, $pad = 20)
    {
        return str_pad(" ", $indent)
            .self::fmt(str_pad($option, $pad), $color)."  ".$description;
    }

    protected static function normalizeFmt($fmt_)
    {
        if(!is_array($fmt_))
            $fmt_ = [$fmt_];
        $fmt = [];
        foreach($fmt_ as $f) {
            if(is_array($f))
                $fmt = array_merge($fmt, $f);
            else
                $fmt[] = $f;
        }
        $fmt = array_filter($fmt, function($f) { return is_int($f) && $f >= 0; });
        $fmt = array_unique($fmt);
        $fmt or $fmt = [self::NORMAL];
        return $fmt;
    }

}
