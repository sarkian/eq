<?php

namespace eq\base;

use EQ;

trait TEvent
{

    protected $events = [];
    protected $triggered = [];
    protected $disabled_events = [];

    public function bind($events, $callable)
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $event) {
            if(!isset($this->events[$event]))
                $this->events[$event] = [];
            if(!is_callable($callable))
                throw new AppException("Argument is not callable");
            if(!in_array($callable, $this->events[$event]))
                $this->events[$event][] = $callable;
        }
        return $this;
    }

    public function unbind($events, $callable = null)
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $event) {
            if($callable) {
                $key = array_search($callable, $this->events[$event]);
                if($key !== false)
                    unset($this->events[$event][$key]);
            }
        else
            $this->events[$event] = [];
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
            if(!EQ_DAEMON)
                $this->triggered[$event][] = $args;
            if(!isset($this->events[$event]))
                continue;
            $key = array_search($event, $this->disabled_events);
            if($key !== false) {
                unset($this->disabled_events[$key]);
                continue;
            }
            $callbacks = $this->events[$event];
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
            if(!isset($this->triggered[$event]) || !is_array($this->triggered[$event]))
                continue;
            foreach($this->triggered[$event] as $args)
                call_user_func_array($callback, $args);
        }
    }

    public function disableEvents($events)
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $event)
            if(!in_array($event, $this->disabled_events))
                $this->disabled_events[] = $event;
        return $this;
    }

}
