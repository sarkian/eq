<?php

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
        if(!is_array($data)) {
            FileSystem::rm($fname);
            return;
        }
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

    public function clear(array $args = [])
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

    public function append(array $args, array $options)
    {
        array_walk($args, function(&$arg) { $arg = (string) $arg; });
        $options['args'] = $args;
        array_push($this->data, $options);
        return $this;
    }

    public function prepend(array $args, array $options)
    {
        array_walk($args, function(&$arg) { $arg = (string) $arg; });
        $options['args'] = $args;
        array_unshift($this->data, $options);
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

    public function getTasks(array $args = [])
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

    public function shift(array $args = [])
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

    public function pop(array $args = [])
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
