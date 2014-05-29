<?php

namespace eq\base;

use EQ;
use eq\helpers\FileSystem;
use eq\cgen\php\PhpValue;

/**
 * @method static array getData(string $name)
 * @method static array setData(string $name, array $data)
 * @method static mixed getValue(string $name, mixed $key, mixed $default = null)
 * @method static mixed setValue(string $name, mixed $key, mixed $value)
 * @method static mixed valueExists(string $name, mixed $key)
 * @method static void  unsetValue(string $name, mixed $key)
 * @method static bool  isModified(string $name)
 */
class Cache
{

    /**
     * @var CacheObject[]
     */
    protected $data = [];

    public static function __callStatic($fname, $args)
    {
        if(!count($args))
            throw new InvalidCallException("Missing argument: name");
        $name = array_shift($args);
        $cache = EQ::app()->cache->get($name);
        if(!method_exists($cache, $fname))
            throw new InvalidCallException("Unknown method: CacheObject::".$fname);
        return call_user_func_array([$cache, $fname], $args);
    }

    public function get($name)
    {
        if(!isset($this->data[$name]))
            $this->load($name);
        return $this->data[$name];
    }

    public function data($name, $value = null)
    {
        $cache = $this->get($name);
        if(is_null($value))
            return $cache->getData();
        else
            return $cache->setData($value);
    }

    public function load($name)
    {
        if(isset($this->data[$name]))
            return $this->data[$name]->getData();
        $fname = $this->filePath($name);
        $data = file_exists($fname) ? require $fname : [];
        is_array($data) or $data = [];
        $this->data[$name] = new CacheObject($data);
        return $data;
    }

    public function save($name = null, $data = null)
    {
        if(!$name) {
            foreach($this->data as $name => $data)
                $this->save($name);
            return;
        }
        $this->load($name);
        if($this->data[$name]->isModified()) {
            if(is_null($data))
                $data = $this->data[$name]->getData();
            $fname = $this->filePath($name);
            $strdata = "<?php\nreturn ".PhpValue::create($data)->render().";\n";
            FileSystem::fputs($fname, $strdata);
        }
    }

    public function remove($name)
    {
        FileSystem::rm($this->filePath($name));
    }

    public function __destruct()
    {
        $this->save();
    }

    protected function filePath($name)
    {
        return EQ::getAlias("@runtime/cache/$name.php");
    }

}
