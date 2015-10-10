<?php
/**
 * User: abiusx
 * Date: 5/24/15
 * Time: 7:00 PM
 */
namespace iframework;
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
    function clear()
    {
        $_SESSION=array();
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