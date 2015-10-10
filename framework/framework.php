<?php
require_once __DIR__."/functions.php";
require_once __DIR__."/db.php";
require_once __DIR__."/session.php";
require_once __DIR__."/user.php";
require_once __DIR__."/http.php";
class i
{

    static function root()
    {
        return self::$root;

    }
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
    protected static $root;


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
     * @return UserManager
     */
    static function users()
    {
        return self::$users;
    }
    public static function db()
    {
        if (self::$activeDb<0)
            return end(self::$db);
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
     * @return Session
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
     * @return HTTP
     */
    static function http()
    {
        return self::$http;
    }

    /**
     * View a file (with templates)
     * @param  [type] $file [description]
     */
    static function view($file)
    {
        if ($file and $file[0]=="/") //absolute
            $file=realpath($file);
        else
            $file=realpath(i::root()."/{$file}.php");
        if (!$file)
            throw new Exception("View file '{$file}.php' not found.");

        $t=$file;
        do
        {
            $inc=dirname($t)."/header.php";
            if (file_exists($inc))
                if (!include $inc) break;
            $t=dirname($t);
        }
        while($t!==i::root());

        include $file;
        $t=$file;
        do
        {
            $inc=dirname($t)."/footer.php";
            if (file_exists($inc))
                if (!include $inc) break;
            $t=dirname($t);
        }
        while($t!==i::root());
    }
    static function serve($request=null)
    {
        if ($request===null)
            $request=i::request();
        $parts=explode("/",$request);
        $file=array_pop($parts);
        $realpath=realpath(i::root()."/".implode("/",$parts));
        $phpfile=realpath($realpath."/".$file.".php");
        if (!$realpath or substr($realpath,0,strlen(i::root()))!==i::root())
            die(i::http()->notFound());
        elseif ($phpfile)
            require $phpfile;
        else
            die(i::http()->notFound()); 
    }
}
