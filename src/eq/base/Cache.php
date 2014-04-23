<?php
/**
 * Last Change: 2014 Apr 23, 22:52
 */

namespace eq\base;

use EQ;
use eq\helpers\FileSystem;
use eq\cgen\php\PhpValue;

class Cache
{

    protected $data = [];

    public function __get($name)
    {
        return $this->load($name);
    }

    public function __set($name, $value)
    {
        $this->load($name);
        $this->data[$name]->setData($value);
    }

    public function __call($name, $args)
    {
        $this->load($name);
        return call_user_func_array([$this->data[$name], "call"], $args);
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
            echo "$name saved\n";
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
