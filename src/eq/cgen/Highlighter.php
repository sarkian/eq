<?php

namespace eq\cgen;

use eq\web\html\Html;
use EQ;

// TODO: escaped characters in strings
class Highlighter
{

    protected static $_predefined = null;

    protected $tokens = [];
    protected $options = [
        'nl2br' => true,
        'tag' => "span",
        'class_prefix' => "hl-",
        'default_class' => "unknown",
        'wrap_untok' => true,
        'untok_class' => "unknown",
        'lines' => true,
        'line_tag' => "div",
        'line_num_tag' => "span",
        'line_attrs' => [
            'class' => "line",
        ],
        'line_num_attrs' => [
            'class' => "line-num",
        ],
        'cursor_line_attrs' => [
            'class' => "line cursor",
        ],
    ];
    protected $classes = [
        'phptag' => [
            T_OPEN_TAG,
            T_OPEN_TAG_WITH_ECHO,
            T_CLOSE_TAG,
        ],
        'keyword' => [
            T_NAMESPACE,
            T_USE,
            T_CLASS,
            T_EXTENDS,
            T_PUBLIC,
            T_PROTECTED,
            T_PRIVATE,
            T_FUNCTION,
            T_THROW,
            T_NEW,
            T_REQUIRE,
            T_REQUIRE_ONCE,
            T_RETURN,
            T_IF,
            T_TRY,
            T_TRAIT,
            T_CASE,
            T_CATCH,
            T_ELSE,
            T_ELSEIF,
            T_ECHO,
            T_EXTENDS,
            T_IMPLEMENTS,
            T_EXIT,
        ],
        'default' => [
            T_STRING,
            T_NS_SEPARATOR,
            T_WHITESPACE,
        ],
        'variable' => [
            T_VARIABLE,
        ],
        'comment' => [
            T_COMMENT,
        ],
        'operator' => [
            T_DOUBLE_COLON,
            T_OBJECT_OPERATOR,
            T_DOUBLE_ARROW,
            T_BOOLEAN_AND,
            T_BOOLEAN_OR,
            T_LOGICAL_AND,
            T_LOGICAL_OR,
            T_LOGICAL_XOR,
            '.',
            ',',
            ';',
            '-',
            '+',
            '/',
            '*',
            '=',
            '&',
            '!',
        ],
        'string' => [
            T_CONSTANT_ENCAPSED_STRING,
            T_ENCAPSED_AND_WHITESPACE,
            '"',
        ],
        'number' => [
            T_LNUMBER,
            T_DNUMBER,
        ],
        'constant' => [
            T_FILE,
            T_LINE,
            T_DIR,
        ],
        'braces' => [
            T_CURLY_OPEN,
            '{',
            '}',
        ],
        'parentheses' => [
            '(',
            ')',
        ],
        'brackets' => [
            '[',
            ']',
        ],
    ];
    protected $predefined = [
        'null' => "keyword",
        'true' => "keyword",
        'false' => "keyword",
    ];
    protected $constants = [];
    protected $cls = [];
    protected $nl = "\n";
    protected $line = "";
    protected $lnum = 1;
    protected $prev_lnum = 1;
    protected $startln = 1;
    protected $endln = null;
    protected $cursor = null;
    protected $start = false;
    protected $stop = false;
    protected $processed = [];

    public static function file($fname, array $options = [])
    {
        return new Highlighter(file_get_contents($fname), $options);
    }

    public static function string($str, array $options = [])
    {
        return new Highlighter($str);
    }

    public function __construct($str, array $options = [])
    {
        $this->tokens = token_get_all($str);
        $this->detectNl($str);
        $this->processPredefined();
        $this->constants = get_defined_constants();
    }

    public function setClasses(array $classes)
    {
        $this->classes = $classes;
    }

    public function render($start = 1, $end = null, $cursor = null)
    {
        $this->processClasses();
        $res = "";
        $this->lnum = 1;
        $this->line = "";
        $this->startln = $start;
        $this->endln = $end;
        $this->cursor = $cursor;
        $this->start = false;
        $this->stop = false;
        foreach($this->tokens as $token) {
            $res .= $this->renderToken($token);
            if($this->stop)
                break;
        }
        if(!$this->stop && $this->line)
            $res .= $this->processLine($this->line);
        return $res;
    }

    protected function renderToken($token)
    {
        $res = "";
        if(is_array($token)) {
            $this->lnum = $token[2];
            $this->prev_lnum = $this->lnum;
            $tlines = explode($this->nl, $token[1]);
            foreach($tlines as $i => $tline) {
                if($this->start) {
                    if($this->lnum > $this->prev_lnum) {
                        $res .= $this->processLine($this->line);
                        $this->line = "";
                        $this->prev_lnum = $this->lnum;
                    }
                    $this->line .= $this->renderTokenArray([$token[0], $tline]);
                    if($this->lnum > $this->endln) {
                        $this->stop = true;
                        break;
                    }
                }
                elseif($this->lnum >= $this->startln) {
                    $this->start = true;
                    $this->line = $this->renderTokenArray([$token[0], $tline]);
                }
                $this->prev_lnum = $this->lnum;
                $this->lnum++;
            }
        }
        elseif($this->start)
            $this->line .= $this->renderTokenString($token);
        return $res;
    }

    protected function renderTokenArray($token)
    {
        $tok = $token[0];
        $str = str_replace(" ", "&nbsp;", htmlentities($token[1]));
        $cls = null;
        if($tok === T_STRING) {
            $cls = $this->getPredefinedClass($token[1]);
        }
        $cls or $cls = $this->getConstantClass($token[1]);
        $cls or $cls = isset($this->cls[$tok])
            ? $this->cls[$tok] : $this->opt("default_class", "unknown");
        $opts = [
            'class' => $this->opt("class_prefix", "").$cls,
            'data-token' => token_name($tok),
        ];
        if($this->opt("nl2br", true))
            $str = nl2br($str);
        return Html::tag($this->opt("tag", "span"), $opts, $str);
    }

    protected function renderTokenString($token)
    {
        if(!$this->opt("wrap_untok", true))
            return $token;
        $tok = $token;
        $str = str_replace(" ", "&nbsp;", htmlentities($token));
        $cls = isset($this->cls[$tok]) ? $this->cls[$tok] : $this->opt("untok_class", "unknown");
        $opts = [
            'class' => $this->opt("class_prefix", "").$cls,
        ];
        if($this->opt("nl2br", true))
            $str = nl2br($str);
        return Html::tag($this->opt("tag", "span"), $opts, $str);
    }

    protected function getPredefinedClass($str)
    {
        $tok = strtolower($str);
        if(!isset($this->predefined[$tok]))
            return false;
        return $this->predefined[$tok];
    }

    protected function getConstantClass($str)
    {
        return isset($this->constants[$str])
            ? $this->opt("constant_class", "constant") : false;
    }

    protected function processLine($code)
    {
//        if(in_array($this->prev_lnum, $this->processed))
//            return "";
//        $this->processed[] = $this->prev_lnum;
        if(!$this->opt("line_wrap", true))
            return $code;
        $attrs = $this->prev_lnum === $this->cursor
            ? $this->opt("cursor_line_attrs", ['class' => "line cursor"])
            : $this->opt("line_attrs", ['class' => "line"]);
        $numtag = Html::tag($this->opt("line_num_tag", "span"),
            $this->opt("line_num_attrs", ['class' => "line-num"]), $this->prev_lnum);
        return Html::tag($this->opt("line_tag", "div"), $attrs, $numtag.$code);
    }

    protected function opt($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    protected function processPredefined()
    {
        if(!self::$_predefined) {
            $funcs_all = get_defined_functions();
            if(!isset($funcs_all['internal']))
                return;
            $funcs = $funcs_all['internal'];
            self::$_predefined = array_combine($funcs, array_fill(0, count($funcs), "predefined"));
        }
        $this->predefined = array_merge(self::$_predefined, $this->predefined);
    }

    protected function processClasses()
    {
        $this->cls = [];
        foreach($this->classes as $cls => $tags) {
            foreach($tags as $tag) {
                $this->cls[$tag] = $cls;
            }
        }
    }

    protected function detectNl($str)
    {
        if(strstr("\r\n", $str))
            $this->nl = "\r\n";
        elseif(strstr("\n\r", $str))
            $this->nl = "\n\r";
        elseif(strstr("\r", $str))
            $this->nl = "\r";
        else
            $this->nl = "\n";
    }

} 