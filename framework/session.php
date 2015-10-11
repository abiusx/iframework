<?php
class Session
{
    function __construct()
    {
        session_start();
        if (isset($this->userid))
            i::users()->touch($this->userid);
    }
    function __destroy()
    {
        session_write_close();
    }
    function regenerate($delete=true)
    {
        session_regenerate_id($delete);
    }
    function clear()
    {
        $_SESSION=array();
    }
    function __isset($item)
    {
        return isset($_SESSION[$item]);
    }
    function __unset($item)
    {
        unset($_SESSION[$item]);
    }
    function __set($item,$value)
    {
        return $_SESSION[$item]=$value;
    }
    function __get($item)
    {
        return $_SESSION[$item];
    }
}