<?php

namespace eq\base;

trait TEvent
{

    private $_events = [];
    private $_triggered = [];
    private $_disabled_events = [];
    private $_save_events = true;

    public function bind($events, $callable)
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $event) {
            if(!isset($this->_events[$event]))
                $this->_events[$event] = [];
            if(!is_callable($callable))
                throw new AppException("Argument is not callable");
            if(!in_array($callable, $this->_events[$event]))
                $this->_events[$event][] = $callable;
        }
        return $this;
    }

    public function unbind($events, $callable = null)
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $event) {
            if($callable) {
                $key = array_search($callable, $this->_events[$event]);
                if($key !== false)
                    unset($this->_events[$event][$key]);
            }
        else
            $this->_events[$event] = [];
        }
        return $this;
    }

    /**
     * @param $events
     * @param mixed $args, ...
     * @return $this
     */
    public function trigger($events, $args = [])
    {
        if(!is_array($events))
            $events = [$events];
        $fargs = func_get_args();
        if(!is_array($args) || count($fargs) > 2)
            $args = array_slice($fargs, 1);
        foreach($events as $event) {
            if($this->_saveEvents())
                $this->_triggered[$event][] = $args;
            if(!isset($this->_events[$event]))
                continue;
            $key = array_search($event, $this->_disabled_events);
            if($key !== false) {
                unset($this->_disabled_events[$key]);
                continue;
            }
            $callbacks = $this->_events[$event];
            $method = "on".ucfirst($event);
            if(method_exists($this, $method))
                $this->{$method}();
            foreach($callbacks as $callback)
                call_user_func_array($callback, $args);
            }
        return $this;
    }

    public function retrigger($events, $callback)
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $event) {
            if(!isset($this->_triggered[$event]) || !is_array($this->_triggered[$event]))
                continue;
            foreach($this->_triggered[$event] as $args)
                call_user_func_array($callback, $args);
        }
    }

    public function switchSaveEvents($value)
    {
        $this->_save_events = (bool) $value;
        $this->_triggered = [];
    }

    public function disableEvents($events)
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $event)
            if(!in_array($event, $this->_disabled_events))
                $this->_disabled_events[] = $event;
        return $this;
    }

    protected function _saveEvents()
    {
        return $this->_save_events;
    }

}
