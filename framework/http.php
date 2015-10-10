<?php
/**
 * User: abiusx
 * Date: 5/24/15
 * Time: 7:17 PM
 */
namespace iframework;
class HTTP
{
    static function IP()
    {
        return $_SERVER['REMOTE_ADDR'];
    }
    static function URL ()
    {
        return (self::protocol()."://".self::host().self::portReadable().self::URI()."?".self::queryString());
    }
    static function port ()
    {
        return isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:"";
    }
    static function portReadable()
    {
        $port=self::port();
        if ($port=="80" && strtolower(self::protocol())=="http")
            $port="";
        else if ($port=="443" && strtolower(self::Protocol())=="https")
            $port="";
        else
            $port=":".$port;
        return $port;
    }
    static function protocol ()
    {
        if (isset($_SERVER['HTTPS']))
            $x = $_SERVER['HTTPS'];
        else
            $x="";
        if ($x=="off" or $x=="")
            return "http";
        else
            return "https";
    }
    /**
     * Contains http://example.com/uri
     * @return string URI
     */
    static function URI()
    {
        if (isset($_SERVER['REDIRECT_URL']))
            return $_SERVER["REDIRECT_URL"];
        else
            return $_SERVER['REQUEST_URI'];
    }
    static function host()
    {
        if (isset($_SERVER['SERVER_NAME']))
            return $_SERVER['SERVER_NAME'];
        else
            return "";

    }
    static function method ()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    static function queryString ($stripBase=true)
    {
        if (isset($_SERVER['REDIRECT_QUERY_STRING']))
            $r=$_SERVER['REDIRECT_QUERY_STRING'];
        else
            $r=isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:"";
        if ($stripBase)
            $r=substr($r,strlen("__base=".i::request()));
        return $r;
    }
}