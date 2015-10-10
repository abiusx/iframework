<?php
/**
 * User: abiusx
 * Date: 5/24/15
 * Time: 6:47 PM
 */
//namespace iframework;
require_once __DIR__."/base.php";
require_once __DIR__."/functions.php";
require_once __DIR__."/db.php";
require_once __DIR__."/session.php";
require_once __DIR__."/user.php";
require_once __DIR__."/http.php";
class i
{

    static function __init($name,$value)
    {
        self::$$name=$value;
    }
    const HOOK_PRE=1;
    const HOOK_POST=2;
    static function hook($function,$type=i::HOOK_PRE)
    {
        if ($type==i::HOOK_POST)
            self::$pre[]=$function;
        elseif ($type==i::HOOK_POST)
            self::$post[]=$function;
    }

    protected static $pre=[],$post=[];
    protected static $db=[];
    protected static $activeDb=-1;
    protected static $request;
    protected static $users,$http,$session;


    /**
     * @return root of web app
     */
    static function url()
    {
        return substr(self::http()->URI(),0,-strlen(self::request()));
    }

    static function request()
    {
        return self::$request;
    }
    /**
     * @return iframework\UserManager
     */
    static function users()
    {
        return self::$users;
    }
    public static function db()
    {
        if (self::$activeDb<0)
            throw new \Exception("No database connection.");
        return self::$db[self::$activeDb];
    }
    /**
     * Runs a SQL query in the database and retrieves result (via DBAL)
     *
     * @param String $Query
     * @param optional $Param1 (could be an array)
     * @return mixed
     */
    static function sql ($Query, $Param1 = null)
    {
        $args=func_get_args();
        if (is_array($Param1))
        {
            $args=$Param1;
            array_unshift($args,$Query);
        }
        return call_user_func_array(array(self::db(), "sql"), $args);
    }

    /**
     * @return iframework\Session
     */
    static function session()
    {
        return self::$session;
    }
    /**
     * Return ID of current user
     */
    static function currentUser()
    {
        return self::session()->userid;
    }

    /**
     * @return iframework\HTTP
     */
    static function http()
    {
        return self::$http;
    }

    static function serve($request)
    {

    }
}
