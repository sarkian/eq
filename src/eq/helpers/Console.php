<?php

namespace eq\helpers;

use eq\base\InvalidCallException;

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

    /**
     * Strip
     */
    const STRIP_VARS        = 1;
    const STRIP_ALIGN       = 2;
    const STRIP_FORMATTING  = 4;
    const STRIP_ESCAPE      = 8;
    const STRIP_DEFAULT     = 12;
    const STRIP_ALL         = 15;

    protected static $conversions = [
        '%n' => self::FG_DEFAULT,
        '%k' => self::FG_BLACK,
        '%r' => self::FG_RED,
        '%g' => self::FG_GREEN,
        '%y' => self::FG_YELLOW,
        '%b' => self::FG_BLUE,
        '%m' => self::FG_MAGENTA,
        '%c' => self::FG_CYAN,
        '%a' => self::FG_GRAY,
        '%d' => self::FG_DARK_GRAY,
        '%N' => self::BG_DEFAULT,
        '%K' => self::BG_BLACK,
        '%R' => self::BG_RED,
        '%G' => self::BG_GREEN,
        '%Y' => self::BG_YELLOW,
        '%B' => self::BG_BLUE,
        '%M' => self::BG_MAGENTA,
        '%C' => self::BG_CYAN,
        '%A' => self::BG_GRAY,
        '%D' => self::BG_DARK_GRAY,
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

    public static function ask($msg)
    {
        do {
            static::stdout("$msg [y/n]: ", false);
            $r = strtolower(static::stdin());
            if($r === "y")
                return true;
            elseif($r === "n")
                return false;
        }
        while(true);
    }

    public static function input($msg)
    {
        // TODO: Implement
    }

    public static function shortDump($var, $indent = 8, $limit = null)
    {
        return str_repeat(" ", $indent).Debug::shortDump($var, [
            'limit' => is_int($limit) ? $limit : self::width() - $indent,
            'typename_wrapfunc' => function($s) {
                return self::seq(self::FG_BLUE).$s.self::seq();
            },
            'operator_wrapfunc' => function($s) {
                return self::seq(self::FG_DARK_GRAY).$s.self::seq();
            },
            'keyword_wrapfunc' => function($s) {
                return self::seq(self::FG_CYAN).$s.self::seq();
            },
            'number_wrapfunc' => function($s) {
                return self::seq(self::FG_MAGENTA).$s.self::seq();
            },
            'string_wrapfunc' => function($s, $cn = "") {
                // \x80-\xFF
                $s = preg_replace_callback('/[\x00-\x1F]/', function($m) {
                    return self::seq(self::FG_MAGENTA)
                        .sprintf('\0%o', ord($m[0])).self::seq(self::FG_GREEN);
                }, $s);
                return self::seq(self::FG_GREEN)
                    .'"'.$s.'"'.self::seq(self::FG_DARK_GRAY).$cn.self::seq();
            },
            'classname_wrapfunc' => function($s) {
                return self::seq(self::FG_CYAN).$s.self::seq();
            },
            'id_wrapfunc' => function($s) {
                return self::seq(self::FG_MAGENTA).$s.self::seq();
            },
            'refcount_wrapfunc' => function($s) {
                return self::seq(self::FG_MAGENTA).$s.self::seq();
            },
        ]);
    }

    public static function fullDump($var, $indent = 0)
    {
        // TODO: Implement
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

    public static function render($string, $vars = [])
    {
        $vars = self::normalizeArgs(func_get_args());
        $string = preg_replace_callback('/(.?)\%\$/', function($m) use(&$vars) {
            if($m[1] === '\\')
                return $m[0];
            if(!count($vars))
                throw new InvalidCallException(get_called_class()."::render(): Too few arguments");
            return $m[1].self::escape(array_shift($vars));
        }, $string);
        if(count($vars))
            throw new InvalidCallException(get_called_class()."::render(): Too many arguments");
        $string = str_replace('\\\\', '\ ', $string);
        $string = self::renderAlign($string);
        $string = str_replace('\%', '% ', $string);
        $string = str_replace('\#', '# ', $string);
        foreach(self::$conversions as $key => $fmt)
            $string = str_replace($key, self::seq($fmt), $string);
        $string = str_replace("% ", "%", $string);
        $string = str_replace("# ", "#", $string);
        $string = str_replace('\ ', '\\', $string);
        return $string;
    }

    public static function renderAlign($string)
    {
        $string = preg_replace("/\r\n|\n\r|\r/", "\n", $string);
        $lines = explode("\n", $string);
        $res = [];
        foreach($lines as $line) {
            $res[] = self::renderAlignLine($line);
        }
        return implode("\n", $res);
    }

    protected static function renderAlignLine($line)
    {
        $line = self::alignPreprocess($line);
        preg_match_all(self::alignRegex(), $line, $matches, PREG_OFFSET_CAPTURE);
        $parts = [];
        $spos = 0;
        $fixed_len = 0;
        $adapt_cnt = 0;
        $last_adapt = null;
        foreach($matches[2] as $i => $m) {
            $d = $matches[1][$i][0] ? (int) mb_substr($matches[1][$i][0], 1, null, "utf-8") : 0;
            $pos = $matches[0][$i][1];
            $str = mb_substr($line, $spos, $pos - $spos, "utf-8");
            $spos = $pos + mb_strlen($matches[0][$i][0], "utf-8");
            list($s, $align) = self::alignProcessTag($m[0]);
            $parts[] = $str;
            $parts[] = [$s, $d, $align];
            if(!$d)
                $last_adapt = count($parts) - 1;
            $fixed_len += mb_strlen(self::strip($str), "utf-8");
            $fixed_len += $d;
            if(!$d)
                $adapt_cnt++;
        }
        $str = mb_substr($line, $spos, null, "utf-8");
        $parts[] = $str;
        $fixed_len += mb_strlen(self::strip($str), "utf-8");
        $width = $adapt_cnt
            ? (int) floor((self::width() - $fixed_len) / $adapt_cnt) : self::width();
        $w_sum = 0;
        $res = "";
        foreach($parts as $i => $part) {
            if(is_string($part)) {
                if(!strlen($part))
                    continue;
                $res .= $part;
            }
            elseif(is_array($part)) {
                if($i === $last_adapt)
                    $w = self::width() - $fixed_len - $w_sum;
                elseif($part[1])
                    $w = $part[1];
                else {
                    $w = $width;
                    $w_sum += $w;
                }
                $str = $part[0];
                if(strlen($str) > $w)
                    $str = substr($str, 0, $w - 2)."..";
                $res .= self::pad($str, $w, $part[2]);
            }
        }
        return $res;
    }

    protected static function alignPreprocess($line)
    {
        $line = preg_replace('/(?<![\\\\\{])\{(?![\{])/', '\{', $line);
        $line = preg_replace('/(?<![\\\\\}])\}(?![\}])/', '\}', $line);
        $line = str_replace('\{', '{ ', $line);
        return str_replace('\}', '} ', $line);
    }

    protected static function alignProcessTag($str)
    {
        $str = str_replace('{ ', '{', $str);
        $str = str_replace('} ', '}', $str);
        $lspace = !substr_compare($str, " ", 0, 1);
        $rspace = !substr_compare($str, " ", -1, 1);
        if($lspace && $rspace) {
            $align = self::CENTER;
            $str = mb_substr($str, 1, -1, "utf-8");
        }
        elseif($lspace) {
            $align = self::RIGHT;
            $str = mb_substr($str, 1, null, "utf-8");
        }
        elseif($rspace) {
            $align = self::LEFT;
            $str = mb_substr($str, 0, -1, "utf-8");
        }
        else {
            $align = self::CENTER;
        }
        return [$str, $align];
    }

    protected static function alignRegex()
    {
        $subs = [
            '[^\{\}]*\{?(?!\{)[^\{\}]*',
            '[^\{\}]*\}?(?!\})[^\{\}]*',
        ];
        $sub = '('.implode("|", $subs).')*';
        return '/(@[0-9]+|)\{\{('.$sub.')\}\}/';
    }

    public static function pad($str, $lenght, $type)
    {
        $str = trim($str, " ");
        $strlen = mb_strlen(self::strip($str), "utf-8");
        if($strlen >= $lenght)
            return $str;
        $len = $lenght - $strlen;
        switch($type) {
            case self::LEFT:
                $left = 0;
                $right = $len;
                break;
            case self::RIGHT:
                $left = $len;
                $right = 0;
                break;
            case self::CENTER:
                $left = (int) ($len / 2);
                $right = $len - $left;
                break;
            default:
                return $str;
        }
        return str_repeat(" ", $left).$str.str_repeat(" ", $right);
    }

    public static function strip($str, $strip = self::STRIP_DEFAULT)
    {
        if($strip & self::STRIP_VARS)
            $str = self::stripVars($str);
        if($strip & self::STRIP_ALIGN)
            $str = self::stripAlign($str);
        if($strip & self::STRIP_FORMATTING)
            $str = self::stripFormatting($str);
        if($strip & self::STRIP_ESCAPE)
            $str = self::stripEscape($str);
        return $str;
    }

    /**
     * Remove variables ("%$") from formatted string
     *
     * @param string $str
     * @return string
     */
    public static function stripVars($str)
    {
        $str = preg_replace_callback('/(.?)\%\$/', function ($m) {
            return $m[1] === '\\' ? $m[0] : $m[1];
        }, $str);
        return $str;
    }

    /**
     * Remove align (e.g. "{{ text }}") from string
     *
     * @param string $str
     * @return string
     */
    public static function stripAlign($str)
    {
        return preg_replace_callback(self::alignRegex(), function($m) {
            return self::alignProcessTag($m[2])[0];
        }, $str);
    }

    /**
     * Remove color formatting ("%y", etc.) from string
     *
     * @param string $str
     * @return string
     */
    public static function stripFormatting($str)
    {
        $str = str_replace('\%', '% ', $str);
        $str = str_replace('\#', '# ', $str);
        foreach(self::$conversions as $key => $fmt)
            $str = str_replace($key, "", $str);
        $str = str_replace("% ", "%", $str);
        $str = str_replace("# ", "#", $str);
        return str_replace('\ ', '\\', $str);
    }

    /**
     * Remove terminal escape sequences ("\033[49;m", etc.) from string
     *
     * @param string $str
     * @return string
     */
    public static function stripEscape($str)
    {
        return preg_replace("/\033\\[[0-9;]+m/", "", $str);
    }

    public static function renderOut($string)
    {
        self::stdout(self::render($string, self::normalizeArgs(func_get_args())));
    }

    public static function renderErr($string)
    {
        self::stderr(self::render($string, self::normalizeArgs(func_get_args())));
    }

    public static function escape($str)
    {
        foreach(['\\', '{', '}', '%', '#'] as $ch)
            $str = str_replace($ch, "\\$ch", $str);
        return $str;
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

    public static function clearLine($cr = false)
    {
        echo "\033[2K";
        if($cr)
            echo "\r";
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
        return self::$size = [
            'width' => 80,
            'height' => 24,
        ];
    }

    public static function isWindows()
    {
        return DIRECTORY_SEPARATOR == '\\';
    }

    public static function isUnix()
    {
        return DIRECTORY_SEPARATOR == '/';
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

    protected static function normalizeArgs(array $args, $shift = true)
    {
        if($shift)
            array_shift($args);
        if(!count($args))
            return [];
        $first = array_shift($args);
        if(is_array($first))
            return $first;
        array_unshift($args, $first);
        return $args;
    }

}
