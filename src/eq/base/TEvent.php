<?php

namespace eq\base;

trait TEvent
{

    protected $events = [];
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

    public function trigger($events, $args = [])
    {
        if(!is_array($events))
            $events = [$events];
        foreach($events as $event) {
            if(!isset($this->events[$event]))
                continue;
            $key = array_search($event, $this->disabled_events);
            if($key !== false) {
                unset($this->disabled_events[$key]);
                continue;
            }
            $callbacks = $this->events[$event];
            // $this->events[$event] = [];
            $method = "on".ucfirst($event);
            if(method_exists($this, $method))
                $this->{$method}();
            foreach($callbacks as $callback)
                call_user_func_array($callback, $args);
            }
        return $this;
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
