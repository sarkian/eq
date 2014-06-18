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
    const FG_GRAY           = 37;
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
    const BG_GRAY           = 47;
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

    /**
     * Align
     */
    const LEFT              = STR_PAD_RIGHT;
    const RIGHT             = STR_PAD_LEFT;
    const CENTER            = STR_PAD_BOTH;

    protected static $conversions = [
        '%n' => self::FG_DEFAULT,
        '%k' => self::FG_BLACK,
        '%r' => self::FG_RED,
        '%g' => self::FG_GREEN,
        '%y' => self::FG_YELLOW,
        '%b' => self::FG_BLUE,
        '%m' => self::FG_MAGENTA,
        '%c' => self::FG_CYAN,
        '%N' => self::BG_DEFAULT,
        '%K' => self::BG_BLACK,
        '%R' => self::BG_RED,
        '%G' => self::BG_GREEN,
        '%Y' => self::BG_YELLOW,
        '%B' => self::BG_BLUE,
        '%M' => self::BG_MAGENTA,
        '%C' => self::BG_CYAN,
        '%0' => self::NORMAL,
        '%1' => self::BOLD,
        '%2' => self::DIM,
        '%3' => self::ITALIC,
        '%4' => self::UNDERLINE,
        '%5' => self::BLINK,
        '%7' => self::INVERTED,
        '%8' => self::HIDDEN,
        '%9' => self::CROSSED_OUT,
        '#1' => self::END_BOLD,
        '#2' => self::END_DIM,
        '#3' => self::END_ITALIC,
        '#4' => self::END_UNDERLINE,
        '#5' => self::END_BLINK,
        '#7' => self::END_INVERTED,
        '#8' => self::END_HIDDEN,
        '#9' => self::END_CROSSED_OUT,
    ];

    protected static $size = null;

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

    public static function begin($format)
    {
        echo self::seq(self::normalizeFmt(func_get_args()));
    }

    public static function end()
    {
        echo self::seq();
    }

    public static function fmtOption($option, $description,
                                     $indent = 4, $color = self::FG_GREEN, $pad = 20)
    {
        return str_pad(" ", $indent)
            .self::fmt(str_pad($option, $pad), $color)."  ".$description;
    }

    public static function render($string)
    {
        $string = str_replace("%%", "% ", $string);
        $string = str_replace("##", "# ", $string);
        foreach(self::$conversions as $key => $fmt)
            $string = str_replace($key, self::seq($fmt), $string);
        $string = str_replace("% ", "%", $string);
        $string = str_replace("# ", "#", $string);
        return $string;
    }

    public static function align($str, $align = self::CENTER)
    {
        return str_pad($str, self::width(), " ", $align);
    }

    public static function moveUp($rows = 1)
    {
        echo "\033[".(int) $rows."A";
    }

    public static function moveDown($rows = 1)
    {
        echo "\033[".(int) $rows."B";
    }

    public static function moveRight($cols = 1)
    {
        echo "\033[".(int) $cols."C";
    }

    public static function moveLeft($cols = 1)
    {
        echo "\033[".(int) $cols."D";
    }

    public static function moveNextLine($lines = 1)
    {
        echo "\033[".(int) $lines."E";
    }

    public static function movePrevLine($lines = 1)
    {
        echo "\033[".(int) $lines."E";
    }

    public static function moveTo($col, $row = null)
    {
        if($row === null)
            echo "\033[".(int) $col.'G';
        else
            echo "\033[".(int) $row.';'.(int) $col.'H';
    }

    public static function scrollUp($lines = 1)
    {
        echo "\033[".(int) $lines."S";
    }

    public static function scrollDown($lines = 1)
    {
        echo "\033[".(int) $lines."T";
    }

    public static function savePos()
    {
        echo "\033[s";
    }

    public static function restorePos()
    {
        echo "\033[u";
    }

    public static function hideCursor()
    {
        echo "\033[?25l";
    }

    public static function showCursor()
    {
        echo "\033[?25h";
    }

    public static function clear()
    {
        echo "\033[2J";
    }

    public static function clearBeforeCursor()
    {
        echo "\033[1J";
    }

    public static function clearAfterCursor()
    {
        echo "\033[0J";
    }

    public static function clearLine()
    {
        echo "\033[2K";
    }

    public static function clearLineBeforeCursor()
    {
        echo "\033[1K";
    }

    public static function clearLineAfterCursor()
    {
        echo "\033[0K";
    }
    
    public static function width($refresh = false)
    {
        return self::size($refresh)['width'];
    }

    public static function height($refresh = false)
    {
        return self::size($refresh)['height'];
    }

    public static function size($refresh = false)
    {
        if(!$refresh && self::$size !== null)
            return self::$size;
        if(self::isWindows()) {
            $output = [];
            exec('mode con', $output);
            if(isset($output) && strpos($output[1], 'CON') !== false) {
                return self::$size = [
                    'width' => (int) preg_replace('~[^0-9]~', '', $output[3]),
                    'height' => (int) preg_replace('~[^0-9]~', '', $output[4]),
                ];
            }
        }
        else {
            $stty = [];
            $rexp = '/rows\s+(\d+);\s*columns\s+(\d+);/mi';
            if(exec("stty -a 2>&1", $stty) && preg_match($rexp, implode(" ", $stty), $matches))
                return self::$size = [
                    'width' => $matches[2],
                    'height' => $matches[1],
                ];
            $width = (int) exec("tput cols 2>&1");
            $height = (int) exec("tput lines 2>&1");
            if($width > 0 && $height > 0)
                return self::$size = [
                    'width' => $width,
                    'height' => $height,
                ];
            $width = (int) getenv('COLUMNS');
            $height = (int) getenv('LINES');
            if($width > 0 && $height > 0)
                return self::$size = [
                    'width' => $width,
                    'height' => $height,
                ];
        }
        return self::$size = [80, 24];
    }

    public static function isWindows()
    {
        return DIRECTORY_SEPARATOR == '\\';
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
