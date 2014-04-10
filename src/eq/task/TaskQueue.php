<?php
/**
 * Last Change: 2014 Jan 05, 00:52
 */

namespace eq\task;

use eq\helpers\FileSystem;

class TaskQueue
{

    private $fname;
    private $data = [];

    public function __construct($fname)
    {
        $this->fname = $fname;
        if(!file_exists($fname))
            return;
        $data = @unserialize(FileSystem::fgets($fname));
        if(!is_array($data))
            return FileSystem::rm($fname);
        foreach($data as $task)
            if(is_array($task) && is_array($task['args'])
                && isset($task['outlog'], $task['errlog']))
                $this->data[] = $task;
    }

    public function save()
    {
        FileSystem::fputs($this->fname, serialize($this->data));
        if(fileperms($this->fname) !== 0664)
            @chmod($this->fname, 0664);
    }

    public function clear($args = [])
    {
        array_walk($args, function(&$arg) { $arg = (string) $arg; });
        $to_remove = [];
        foreach($this->data as $i => $task)
            if($task['args'] === $args)
                $to_remove[] = $i;
        if($to_remove) {
            foreach($to_remove as $index)
                unset($this->data[$index]);
            $this->data = array_merge($this->data);
        }
        return $this;
    }

    public function clearAll()
    {
        $this->data = [];
        return $this;
    }

    public function append($args = [], $outlog = null, $errlog = null)
    {
        array_walk($args, function(&$arg) { $arg = (string) $arg; });
        array_push($this->data, [
            'args' => $args,
            'outlog' => $outlog,
            'errlog' => $errlog,
        ]);
        return $this;
    }

    public function prepend($args = [], $outlog = null, $errlog = null)
    {
        array_walk($args, function(&$arg) { $arg = (string) $arg; });
        array_unshift($this->data, [
            'args' => $args,
            'outlog' => $outlog,
            'errlog' => $errlog,
        ]);
        return $this;
    }

    public function count($args = [])
    {
        return count($this->getTasks($args));
    }

    public function countAll()
    {
        return count($this->getTasksAll());
    }

    public function getTasks($args = [])
    {
        $tasks = [];
        $args = TaskBase::normalizeArgs($args);
        foreach($this->data as $task)
            if($task['args'] === $args)
                $tasks[] = $task;
        return $tasks;
    }

    public function getTasksAll()
    {
        return $this->data;
    }

    public function shift($args = [])
    {
        $args = TaskBase::normalizeArgs($args);
        foreach($this->data as $i => $task) {
            if($task['args'] === $args) {
                unset($this->data[$i]);
                $this->data = array_merge($this->data);
                return $task;
            }
        }
        return null;
    }

    public function pop($args = [])
    {
        $data = array_reverse($this->data);
        $args = TaskBase::normalizeArgs($args);
        foreach($data as $i => $task) {
            if($task['args'] === $args) {
                unset($data[$i]);
                $this->data = array_reverse(array_merge($data));
                return $task;
            }
        }
        return null;
    }

    public function shiftAll()
    {
        return array_shift($this->data);
    }

    public function popAll()
    {
        return array_pop($this->data);
    }

}
