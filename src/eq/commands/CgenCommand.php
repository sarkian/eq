<?php
/**
 * Last Change: 2014 Apr 08, 00:58
 */

namespace eq\commands;

use eq\cgen\reflection\ReflectionClass;
use eq\cgen\reflection\ReflectionMethod;
use eq\cgen\reflection\ReflectionProperty;
use eq\base\LoaderException;

/**
 * CgenCommand short description
 *
 * Long description line 1
 * Long description line 2
 * Long description line 3
 *
 * some sdfasdf
 *
 * okay
 * 
 * @author Sarkian <root@dustus.org> 
 * @doc TO_DO Write documentation
 * @test TO_DO Write test
 * @see eq\console\Command
 * @see okay
 * @see [sdf](sdf)
 */
class CgenCommand extends \eq\console\Command implements \eq\dev\test\MyInterfaceOne, \eq\dev\test\MyInterfaceTwo
{

    /**
     * Генерирует тест для класса, определённого в указанном файле.
     *
     * @param string $testsdir Путь к директории с тестами
     * @param string $file Путь к файлу, относительно APPROOT или EQROOT
     * @throws eq\base\ExceptionBase если что-то пошло не так
     * @throws eq\base\LoaderException if not ok
     * @throws eq\base\InvalidArgumentException if ...
     * @see eq\commands\CgenCommand::actionCreateDoc
     * @see actionCreateTest
     */
    public function actionCreateTest($testsdir, $file)
    {
        $classname = \str_replace('/', '\\', \preg_replace('/\.php$/', '', $file));
        $class = new ReflectionClass($classname);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PROTECTED);
        $code = \eq\web\Controller::renderViewFile(EQROOT.'/cgen/templates/class_test.php', [
            'class' => $class,
            'rel_root' => \str_repeat('../', \substr_count($class->name, '\\')),
            'file' => $file,
            'methods' => $methods,
        ]);
        $dir = $testsdir.'/'.\dirname($file);
        $fname = $dir.'/'.$class->getShortName().'Test.php';
        $res = $this->tryWriteFile($fname, $code);
        echo $res === true ? "SUCCESS: $fname" : $res;
    }

    /**
     * Генерирует документацию для класса, определённого в указанном файле.
     * 
     * @param string $docdir Путь к директории с документацией
     * @param string $file Путь к файлу, относительно APPROOT или EQROOT
     */
    public function actionCreateDoc($docdir, $file)
    {
        $classname = \str_replace('/', '\\', \preg_replace('/\.php/', '', $file));
        $class = new ReflectionClass($classname);
        $methods = $class->getDeclaredMethods(ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PROTECTED);
        $inh_methods = $class->getInheritedMethods(ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PROTECTED);
        $class_contents = \eq\web\Controller::renderViewFile(EQROOT.'/cgen/templates/class_doc.php', [
            'header' => ($class->isTrait() ? 'Трейт' : 'Класс').' '.$class->name,
            'class' => $class,
            'file' => $file,
            'descr_short' => $class->getDocShortDescr() ? $class->getDocShortDescr() : '**= WRITE ME =**',
            'descr_long' => $class->getDocLongDescr() ? "\n".$class->getDocLongDescr()."\n" : "",
            'namespace' => $class->getNamespaceName(),
            'parent' => $class->getParentClass() ? $class->getParentClass()->name : false,
            'implements' => $class->getInterfaceNames(),
            'constants' => $class->getConstants(),
            'properties' => $class->getProperties(ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED),
            'methods' => $methods,
            'inh_methods' => $inh_methods,
            'see' => \array_map([$this, 'processSeeTag'], $class->getDocSee()),
        ]);
        $dir = $docdir.'/'.\dirname($file);
        foreach($methods as $method) {
            $contents = \eq\web\Controller::renderViewFile(EQROOT.'/cgen/templates/method_doc.php', [
                'header' => 'Метод '.$class->name.'::'.$method->name,
                'class' => $class,
                'file' => $file,
                'method' => $method,
                'descr_short' => $method->getDocShortDescr() ? $method->getDocShortDescr() : '**= WRITE ME =**',
                'descr_long' => $method->getDocLongDescr() ? "\n".$method->getDocLongDescr()."\n" : "",
                'params' => $method->getParameters(),
                'throws' => $this->processThrows($method->getDocThrowsDescr()),
                'see' => $method->getDocSee()
                    ? \array_map([$this, 'processSeeTag'], $method->getDocSee(), \array_fill(0, \count($method->getDocSee()), $class))
                    : [],
            ]);
            $res = $this->tryWriteFile($dir.'/'.$class->getShortName().'/'.$method->name.'.md', $contents);
            if($res !== true) {
                echo $res;
                return;
            }
        }
        $fname = $dir.'/'.$class->getShortName().'.md';
        $res = $this->tryWriteFile($fname, $class_contents);
        echo $res === true ? "SUCCESS: $fname" : $res;
    }

    public function escapeDescr($descr)
    {
        $descr = str_replace("_", "\\_", $descr);
        $descr = str_replace("*", "\\*", $descr);
        return $descr;
    }

    public function processThrows($throws)
    {
        $res = [];
        foreach($throws as $exception => $description)
            $res[] = $this->processSeeTag($exception).$description;
        return $res;
    }

    public function processSeeTag($tag, ReflectionClass $class = null)
    {
        if(\preg_match('/^\[[^\[\]]+\]\([^\(\)]+\)/', $tag)) // md-link
            return $tag;
        elseif(\preg_match('/^[a-zA-Z0-9_\\\]+$/', $tag)) { // class-name or method-name
            try {
                $class_ = new ReflectionClass($tag);
                return '['.$tag.'](docs/'.str_replace('\\', '/', $tag).".md) - \n".
                    '        '.$class_->getDocShortDescr();
            }
            catch(LoaderException $e) {
                if($class) {
                    if($class->hasMethod($tag))
                        return '['.$class->name.'::'.$tag.'](docs/'.str_replace('\\', '/', $class->name).'/'.$tag.".md) - \n".
                            '        '.$class->getMethod($tag)->getDocShortDescr();
                }
                return $tag;
            }
        }
        elseif(\preg_match('/^[a-zA-Z0-9_\\\]+\:\:[a-zA-Z0-9_]+$/', $tag)) { // full method name ( Class::method )
            list($classname, $methodname) = \explode('::', $tag);
            try {
                $class_ = new ReflectionClass($classname);
                if($class->hasMethod($methodname))
                    return '['.$tag.'](docs/'.str_replace('\\', '/', $classname).'/'.$methodname.".md) - \n".
                        '        '.$class_->getMethod($methodname)->getDocShortDescr();
                else return $tag;
            }
            catch(LoaderException $e) {
                return $tag;
            }
        }
        else return $tag;
    }

    private function tryWriteFile($fname, $contents)
    {
        $dir = dirname($fname);
        if(file_exists($fname))
            return "File already exists: $fname";
        if(!file_exists($dir)) {
            if(!mkdir($dir, 0755, true))
                return "Unable to create directory: $dir";
        }
        elseif(!is_dir($dir))
            return "Unable to create directory, file already exists: $dir";
        if(file_put_contents($fname, $contents) !== false)
            return true;
        else
            return "Unable to write file: $fname";
    }

}
