<?php

namespace eq\modules\cron;

use EQ;
use eq\base\InvalidCallException;

class CrontabTaskTime
{

    protected $special  = "";
    protected $minutes  = "*";
    protected $hours    = "*";
    protected $dom      = "*";
    protected $mon      = "*";
    protected $dow      = "*";

    public function __construct($time = null)
    {
        if(is_string($time)) {
            $time = trim($time, " \r\n\t");
            if($this->specialIsValid($time))
                $this->special = $this->specialNormalize($time);
            else
                $time = array_slice(preg_split("/[\s\t]/", $time), 0, 5);
        }
        if(is_array($time)) {
            $time = array_merge($time);
            if(isset($time[0]))
                $this->minutes = $time[0];
            if(isset($time[1]))
                $this->hours = $time[1];
            if(isset($time[2]))
                $this->dom = $time[2];
            if(isset($time[3]))
                $this->mon = $time[3];
            if(isset($time[4]))
                $this->dow = $time[4];
        }
    }

    public static function __callStatic($name, $args)
    {
        $inst = new CrontabTaskTime();
        if(!method_exists($inst, $name))
            throw new InvalidCallException("Undefined method: $name");
        return call_user_func_array([$inst, $name], $args);
    }

    public function __toString()
    {
        if($this->specialIsValid($this->special))
            return $this->specialNormalize($this->special);
        return $this->minutes." ".$this->hours." ".$this->dom." ".$this->mon
            ." ".$this->dow;
    }

    public function minutely()
    {
        $this->special  = "";
        $this->minutes  = "*";
        $this->hours    = "*";
        $this->dom      = "*";
        $this->mon      = "*";
        $this->dow      = "*";
        return $this;
    }

    public function hourly()
    {
        $this->special  = "";
        $this->minutes  = "0";
        $this->hours    = "*";
        $this->dom      = "*";
        $this->mon      = "*";
        $this->dow      = "*";
        return $this;
    }

    public function daily()
    {
        $this->special  = "";
        $this->minutes  = "0";
        $this->hours    = "0";
        $this->dom      = "*";
        $this->mon      = "*";
        $this->dow      = "*";
        return $this;
    }

    public function monthly()
    {
        $this->special  = "";
        $this->minutes  = "0";
        $this->hours    = "0";
        $this->dom      = "1";
        $this->mon      = "*";
        $this->dow      = "*";
        return $this;
    }

    public function weekly()
    {
        $this->special  = "";
        $this->minutes  = "0";
        $this->hours    = "0";
        $this->dom      = "*";
        $this->mon      = "*";
        $this->dow      = "1";
        return $this;
    }

    public function atReboot()
    {
        $this->special = "@reboot";
        return $this;
    }

    protected function specialIsValid($string)
    {
        if(!$string)
            return false;
        if(!strncmp($string, "@", 1))
            $string = substr($string, 1);
        return in_array($string, [
            "reboot",
            "yearly",
            "annually",
            "monthly",
            "weekly",
            "daily",
            "midnight",
            "hourly",
        ]);
    }

    protected function specialNormalize($string)
    {
        return strncmp($string, "@", 1) ? "@".$string : $string;
    }

}
