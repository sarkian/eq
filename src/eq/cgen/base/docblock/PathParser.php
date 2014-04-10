<?php
/**
 * Last Change: 2013 Oct 22, 01:35
 */

namespace eq\cgen\base\docblock;

use eq\base\LoaderException;

/**
 * Парсер ссылок в @see и подобных docblock-тегах. 
 * 
 * @author Sarkian <root@dustus.org> 
 * @since 0.2
 * @doc docs/eq/cgen/base/docblock/PathParser.md
 * @test tests/eq/cgen/base/docblock/PathParserTest.php
 * @uses eq\base\LoaderException
 * @see eq\cgen\base\docblock\Docblock
 * @see eq\cgen\base\docblock\TagList
 */
class PathParser
{

    /**
     * Проверяет, является ли строка Markdown-ссылкой вида `[anchor](url_or_path)`. 
     * 
     * @param string $str Проверяемая строка
     * @return bool TRUE, если строка является Markdown-ссылкой, иначе FALSE.
     * @see createMdLink
     */
    public static function isMdLink($str)
    {
        if(!$str) return false;
        return (bool) preg_match("/^\[[^\[\]]+\]\([^\(\)]+\)/", $str);
    }

    /**
     * Проверяет, является ли строка относительным путём к файлу класса.
     *
     * Имя файла класса считается корректным, только если оно имеет расширение .php
     * и начинается с верхнего регистра.
     * Так же в пути к файлу не должно присутствовать символов кроме `[a-zA-Z0-9_/]`,
     * т.е. путь должен быть легко преобразовываемым в полное (с нэймспейсом) имя класса.
     * Наличие файла не проверяется.
     * 
     * @param string $str Проверяемая строка
     * @return bool TRUE, если строка является путём к файлу класса, иначе FALSE.
     * @see getClassNameFromFilePath
     * @see isClassDocPath
     * @see isMethodDocPath
     */
    public static function isClassFilePath($str)
    {
        if(!$str) return false;
        if(!preg_match("/^[a-zA-Z_][a-zA-Z0-9_\/]*\.php$/", $str))
            return false;
        $parts = explode("/", $str);
        $fname = $parts[ count($parts) - 1 ];
        return (bool) preg_match("/^[A-Z]/", $fname);
    }

    /**
     * Проверяет, является ли строка относительным путём к файлу Markdown-документации класса.
     *
     * Имя файла документации считается корректным, только если оно имеет расширение .md
     * и начинается с верхнего регистра.
     * Так же в пути к файлу не должно присутствовать символов кроме `[a-zA-Z0-9_/]`,
     * т.е. путь должен быть легко преобразовываемым в полное (с нэймспейсом) имя класса.
     * Наличие файла не проверяется.
     * 
     * @param mixed $str Проверяемая строка
     * @return bool TRUE, если строка является путём к файлу Markdown-документации класса, иначе FALSE.
     * @see getClassNameFromDocPath
     * @see isClassFilePath
     * @see isMethodDocPath
     */
    public static function isClassDocPath($str)
    {
        if(!$str) return false;
        if(!preg_match("/^[a-zA-Z_][a-zA-Z0-9_\/]*\.md$/", $str))
            return false;
        $parts = explode("/", $str);
        $fname = $parts[ count($parts) - 1 ];
        return (bool) preg_match("/^[A-Z]/", $fname);
    }

    /**
     * Проверяет, является ли строка относительным путём к файлу Markdown-документации метода.
     * 
     * Имя файла документации метода считается корректным, только если оно имеет
     * расширение .md, начинается с нижнего регистра и имя родительской директории
     * начинается с верхнего регистра.
     * Так же в пути к файлу не должно присутствовать символов кроме `[a-zA-Z0-9_/]`,
     * т.е. путь должен быть легко преобразовываемым в полное (с нэймспейсом) имя класса.
     * Наличие файла и директории не проверяется.
     *
     * @param mixed $str Проверяемая строка
     * @return bool TRUE, если строка является путём к файлу Markdown-документации метода, иначе FALSE.
     * @see getMethodNameFromDocPath
     * @see isClassFilePath
     * @see isClassDocPath
     */
    public static function isMethodDocPath($str)
    {
        if(!$str) return false;
        if(!preg_match("/^[a-zA-Z_][a-zA-Z0-9_\/]+\.md$/", $str))
            return false;
        $parts = explode("/", $str);
        if(count($parts) < 2)
            return false;
        $cfname = $parts[ count($parts) - 2 ];
        $mfname = $parts[ count($parts) - 1 ];
        if(!preg_match("/^[A-Z]/", $cfname))
            return false;
        return (bool) preg_match("/^[a-z]/", $mfname);
    }

    /**
     * Проверяет, является ли строка именем класса. 
     *
     * Имя класса считается корректным, только если оно начинается с верхнего регистра.
     * Наличие класса не проверяется.
     * 
     * @param string $str Проверяемая строка
     * @return bool TRUE, если строка является именем класса, иначе FALSE.
     * @see isMethodName
     * @see isFullMethodName
     */
    public static function isClassName($str)
    {
        if(!$str) return false;
        if(!preg_match("/^[a-zA-Z_][a-zA-Z0-9_\\\]*$/", $str))
            return false;
        $parts = explode("\\", $str);
        $cname = $parts[ count($parts) - 1 ];
        return (bool) preg_match("/^[A-Z]/", $cname);
    }

    /**
     * Проверяет, является ли строка именем метода.
     *
     * Имя метода считается корректным только если оно начинается с нижнего регистра или `_`.
     * 
     * @param string $str Проверяемая строка
     * @return bool TRUE, если строка является именем метода, иначе FALSE.
     * @see isClassName
     * @see isFullMethodName
     */
    public static function isMethodName($str)
    {
        if(!$str) return false;
        return (bool) preg_match("/^[a-z_][a-zA-Z0-9_]*$/", $str);
    }

    /**
     * Проверяет, является ли строка полным именем метода вида Class::method.
     *
     * Имя метода считается корректным, только если оно начинается с нижнего регистра или `_`
     * и имя класса начинается с верхнего регистра.
     * 
     * @param string $str Проверяемая строка
     * @return bool TRUE, если строка является полным именем метода, иначе FALSE.
     * @see isClassName
     * @see isMethodName
     */
    public static function isFullMethodName($str)
    {
        if(!$str) return false;
        if(!preg_match("/^[a-zA-Z0-9_\\\]+\:\:[a-zA-Z0-9_]+$/", $str))
            return false;
        list($cname, $mname) = explode("::", $str);
        return self::isClassName($cname) && self::isMethodName($mname);
    }

    /**
     * Создаёт и возвращает объект ReflectionClass, сообщающий информацию об указанном классе.
     * 
     * @param string $classname Имя класса
     * @return mixed Объект класса ReflectionClass или FALSE, если класс `$classname` не найден.
     * @see [ReflectionClass](http://php.net/manual/ru/class.reflectionclass.php)
     */
    public static function reflect($classname)
    {
        try {
            return new \ReflectionClass($classname);
        }
        catch(LoaderException $e) {
            return false;
        }
    }

    /**
     * Создаёт Markdown-ссылку. 
     * 
     * @param string $anchor Якорь ссылки
     * @param string $url URL или путь к файлу
     * @param string $descr Описание; если указано - добавляется к ссылке через тире
     * @return string Строка вида `[$anchor]($url) — $descr`
     * @see createClassMdLink
     * @see createMethodMdLink
     */
    public static function createMdLink($anchor, $url, $descr = null)
    {
        $link = "[".$anchor."](".$url.")";
        if(!is_null($descr))
            $link .= " — ".$descr;
        return $link;
    }

    /**
     * Создаёт Markdown-ссылку на описание класса. 
     * 
     * @param string $classname Имя класса
     * @param string $docs_dir Путь к директории с документацией, будет добавлен к началу пути ссылки
     * @param string $descr Описание; если указано - добавляется к ссылке через тире
     * @return string Markdown-ссылка на описание класса.
     * @see createMdLink
     * @see createMethodMdLink
     */
    public static function createClassMdLink($classname, $docs_dir = "", $descr = null)
    {
        if($docs_dir)
            $docs_dir = rtrim($docs_dir, "/")."/";
        return self::createMdLink(
            $classname,
            $docs_dir.str_replace("\\", "/", $classname).".md",
            $descr
        );
    }

    /**
     * Создаёт Markdown-ссылку на описание метода класса. 
     * 
     * @param string $classname Имя класса
     * @param string $methodname Имя метода
     * @param string $docs_dir Путь к директории с документацией, будет добавлен к началу пути ссылки
     * @param string $descr Описание; если указано - добавляется к ссылке через тире
     * @return string Markdown-ссылка на описание метода класса.
     * @see createMdLink
     * @see createClassMdLink
     */
    public static function createMethodMdLink($classname, $methodname, $docs_dir = "", $descr = null)
    {
        if($docs_dir)
            $docs_dir = rtrim($docs_dir, "/")."/";
        return self::createMdLink(
            $classname."::".$methodname,
            $docs_dir.str_replace("\\", "/", $classname)."/".$methodname.".md",
            $descr
        );
    }

    /**
     * Извлекает имя класса из пути к файлу класса.
     *
     * **Внимание!**
     * Метод *не проверяет* корректность пути.
     * Для проверки используйте isClassFilePath().
     * Так же следует обращать внимание на то, чтобы оба пути (`$path` и `$code_dir`)
     * были либо абсолютными, либо относительно одной директории.
     * 
     * @param string $path Путь к файлу класса
     * @param string $code_dir Путь к директории с классами; будет удалён из начала пути к файлу
     * @return string Имя класса.
     * @see isClassFilePath
     */
    public static function getClassNameFromFilePath($path, $code_dir = "")
    {
        if($code_dir) {
            $code_dir = rtrim($code_dir, "/");
            $path = preg_replace("/^".preg_quote($code_dir, "/")."/", "", $path);
        }
        $cname = str_replace("/", "\\", preg_replace("/\.php$/", "", $path));
        return ltrim($cname, "\\");
    }

    /**
     * Извлекает имя класса из пути к файлу документации класса.
     *
     * **Внимание!**
     * Метод *не проверяет* корректность пути.
     * Для проверки используйте isClassDocPath().
     * Так же следует обращать внимание на то, чтобы оба пути (`$path` и `$docs_dir`)
     * были либо абсолютными, либо относительно одной директории.
     * 
     * @param string $path Путь к файлу документации класса
     * @param string $docs_dir Путь к директории с документацией; будет удалён из начала пути к файлу
     * @return string Имя класса.
     * @see isClassDocPath
     */
    public static function getClassNameFromDocPath($path, $docs_dir = "")
    {
        if($docs_dir) {
            $docs_dir = rtrim($docs_dir, "/");
            $path = preg_replace("/^".preg_quote($docs_dir, "/")."/", "", $path);
        }
        $cname = str_replace("/", "\\", preg_replace("/\.md/", "", $path));
        return ltrim($cname, "\\");
    }

    /**
     * Извлекает имена метода и класса из пути к файлу документации метода.
     *
     * **Внимание!**
     * Метод *не проверяет* корректность пути.
     * Для проверки используйте isMethodDocPath().
     * Так же следует обращать внимание на то, чтобы оба пути (`$path` и `$docs_dir`)
     * были либо абсолютными, либо относительно одной директории.
     * 
     * @param string $path Путь к файлу документации метода
     * @param string $docs_dir Путь к директории с документацией; будет удалён из начала пути к файлу
     * @return array Массив вида `[class_name, method_name]`.
     * @see isMethodDocPath
     */
    public static function getMethodNameFromDocPath($path, $docs_dir = "")
    {
        if($docs_dir) {
            $docs_dir = rtrim($docs_dir, "/");
            $path = preg_replace("/^".preg_quote($docs_dir, "/")."/", "", $path);
        }
        $path = ltrim(preg_replace("/\.md/", "", $path), "/");
        $parts = explode("/", $path);
        $method = array_pop($parts);
        return [
            implode("\\", $parts),
            $method,
        ];
    }

    /**
     * Парсит строку и если она является именем класса/метода или путём к документации к классу/методу -
     * возвращает Markdown-ссылку на соответствующий файл документации.
     * 
     * @param string $str Исходная строка (например, значение phpdoc-тега @see)
     * @param string $docs_dir Директория с докуметацией
     * @param string $code_dir Директория с кодом
     * @param string $classname Имя класса, в котором содержится тег
     * @param bool $add_descr Добавлять ли описание класса/метода к ссылке
     * @return string Markdown-ссылка или исходная строка, если не удалось найти класс/метод.
     * @see eq\cgen\base\docblock\Docblock
     * @see eq\cgen\base\docblock\TagList
     */
    public static function process($str, $docs_dir, $code_dir, $classname = null, $add_descr = true)
    {
        if($classname && self::isMethodName($str)) {
            $obj = self::reflect($classname);
            if(!$obj) return $str;
            if(!$obj->hasMethod($str)) return $str;
            $descr = $add_descr ?
                (new Docblock($obj->getMethod($str)->getDocComment()))->shortDescription() : null;
            return self::createMethodMdLink($classname, $str, $docs_dir, $descr);
        }
        elseif(self::isClassName($str)) {
            $obj = self::reflect($str);
            if(!$obj) return $str;
            $descr = $add_descr ?
                (new Docblock($obj->getDocComment()))->shortDescription() : null;
            return self::createClassMdLink($str, $docs_dir, $descr);
        }
        elseif(self::isFullMethodName($str)) {
            list($class, $method) = explode("::", $str);
            $obj = self::reflect($class);
            if(!$obj) return $str;
            if(!$obj->hasMethod($method)) return $str;
            $descr = $add_descr ?
                (new Docblock($obj->getMethod($method)->getDocComment()))->shortDescription() : null;
            return self::createMethodMdLink($class, $method, $docs_dir, $descr);
        }
        elseif(self::isClassFilePath($str)) {
            $cname = self::getClassNameFromFilePath($str, $code_dir);
            $obj = self::reflect($cname);
            if(!$obj) return $str;
            $descr = $add_descr ?
                (new Docblock($obj->getDocComment()))->shortDescription() : null;
            return self::createClassMdLink($cname, $docs_dir, $descr);
        }
        elseif(self::isClassDocPath($str)) {
            $cname = self::getClassNameFromDocPath($str, $docs_dir);
            $obj = self::reflect($cname);
            if(!$obj) return $str;
            $descr = $add_descr ?
                (new Docblock($obj->getDocComment()))->shortDescription() : null;
            return self::createClassMdLink($cname, $docs_dir, $descr);
        }
        elseif(self::isMethodDocPath($str)) {
            list($class, $method) = self::getMethodNameFromDocPath($str, $docs_dir);
            $obj = self::reflect($class);
            if(!$obj) return $str;
            if(!$obj->hasMethod($method)) return $str;
            $descr = $add_descr ?
                (new Docblock($obj->getMethod($method)->getDocComment()))->shortDescription() : null;
            return self::createMethodMdLink($class, $method, $docs_dir, $descr);
        }
        else
            return $str;
    }

}
