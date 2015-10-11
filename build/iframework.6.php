<?php

###framework.php:
###functions.php:
/**
 * The print_r equivalent formatted for HTML (instead of clear text)
 * @param $var
 */
function print_($var)
{
    if ($var===null)
        $data="NULL";
    elseif ($var===false)
        $data="False";
    elseif ($var===true)
        $data="True";
    else
        $data=print_r($var,true);
    if (php_sapi_name() === 'cli')
        echo $data."\n";
    else
        echo nl2br(str_replace(" ","&nbsp;",htmlspecialchars($data)))."<br/>";
    flush();
    if (ob_get_contents()) ob_flush();
}

/**
 * Makes a parameter XSS-safe
 * @param $data
 * @return string
 */
function safe($data)
{
    if (defined("ENT_HTML401"))
        return htmlspecialchars($data,ENT_QUOTES | ENT_HTML401,"UTF-8");
    else
        return htmlspecialchars($data,ENT_QUOTES,"UTF-8");
}

/**
 * Works like printf, but only the format string is allowed to have HTML.
 * Parameters are escaped for XSS.
 * @return mixed
 */
function prints()
{
    $args=func_get_args();
    for ($i=1;$i<count($args);++$i)
        $args[$i]=safe($args[$i]);
    return call_user_func_array("printf",$args);
}

/**
 * Like prints, but returns the result instead of printing it
 * @return mixed
 */
function sprints()
{
    $args=func_get_args();
    for ($i=1;$i<count($args);++$i)
        $args[$i]=safe($args[$i]);
    return call_user_func_array("sprintf",$args);

}

/**
 * Maps an array (2D) using array_map, to another array only having one field of the original array
 * @param $array
 * @param $field
 * @return array
 */
function map($array,$field)
{
    if ($array===null) return null;
    return array_map(function($item) use ($field) { return $item[$field];},$array);
}

/**
 * Expression map
 * Maps an array into another array by running an expression on every entry
 * e.g emap(array(1,2,3),"_*2") will return array(2,4,6)
 * @warning The placeholder is _, and the function uses eval to run the expression
 * @param $array
 * @param $expression should be an string, with _ as the placeholder for variable
 * @return array
 */
function emap($array,$expression)
{
    return array_map(function ($v) use ($expression) {
        $code="return(".str_replace("_",'$v',$expression).");";
        return eval($code);
    },$array);
}

/**
 * Similar to emap, but does map-reduce instead of map
 * @warning the function uses eval to run the expression
 * @param $array
 * @param $expression __ denotes the carry, _ denotes iteration variable
 * @param null $initial
 * @return mixed
 */
function emapr($array,$expression,$initial=null)
{
    return array_reduce($array,function($carry,$v) use ($expression){
        $expression=str_replace("__",'$carry',$expression);
        $code="return(".str_replace("_",'$v',$expression).");";
        return eval($code);
    },$initial);
}
###db.php:
class Database
{
    public $driver;
    public $username,$password,$host,$dbname;
    protected $connection;
    function __construct($username,$password,$dbname=null,$driver="mysql",$host="localhost")
    {
        $this->driver=$driver;
        $this->username=$username;
        $this->password=$password;
        $this->host=$host;
        $this->dbname=$dbname;
        $this->connect();
    }
    function connect()
    {
        if ($this->driver=="sqlite")
            $this->connection=new \PDO("{$this->driver}:{$this->dbname}",$this->username,$this->password);//;username={$this->username};password={$this->password}");
        else
            $this->connection=new \PDO("{$this->driver}:host={$this->host};dbname={$this->dbname}",$this->username,$this->password);//;username={$this->username};password={$this->password}");
         $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); //exception on error
    }

    /**
     * @param $query
     * @param ... args
     * @return null
     */
    function sql($query)
    {
        $args = func_get_args ();
        array_shift ( $args );
        $statement = $this->connection->prepare ( $query );
        $out=$statement->execute ($args);
        $type = substr ( trim ( strtoupper ( $query ) ), 0, 6 );
        if ($type == "INSERT")
        {
            $res = $this->connection->lastInsertId(); // returns 0 if no auto-increment found
            if ($res == 0)
                $res = $statement->rowCount ();
            return $res;
        }
        elseif ($type == "DELETE" or $type == "UPDATE" or $type == "REPLAC")
            return $statement->rowCount ();
        elseif ($type == "SELECT")
        {
            $res=[];
            while ($r=$statement->fetchObject())
                $res[]=$r;
            return $res;
        }
        else
            return $out;
    }
}
###session.php:
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
###user.php:
class Password
{
    const PROTOCOL_SHA512=1;
    /**
     * change this everytime you change the way password is generated to update
     * @var integer
     */
    protected static $protocol=Password::PROTOCOL_SHA512;
    protected $hash,$salt,$m_protocol;
    function protocol()
    {
        return $this->m_protocol;
    }
    function salt()
    {
        return $this->salt;
    }
    public function __construct($password,$salt=null,$protocol=null)
    {
        if ($protocol===null)
            $this->m_protocol=$protocol=self::$protocol;
        if ($salt===null)
            $this->salt=$salt=hash("sha512",rand().rand());
        if ($protocol==self::PROTOCOL_SHA512)
            $this->hash=hash("sha512",$password.$salt);
    }
    /**
     * Validates if a hashed password is the correct hashed password for a given raw password, username and salt
     *
     * @param string $password the textual password (entered by user at login)
     * @param string $hash the hashed password (retrived from database)
     * @param string $salt the dynamic salt (from database)
     * @param integer $protocol optional for backward compatibility
     * @return boolean
     */
    static function validate($password,$hash,$salt, $protocol=null)
    {
        $temp=new Password($password, $salt,$protocol);
        return ($temp->hash()==$hash);
    }
    function hash()
    {
        return $this->hash;
    }
}

class UserManager
{
    static $Timeout=1800; //30min
    /**
    * Removes a user form system users if exists
    * @param Username of the user
    * @return boolean
     */
    function delete($username)
    {
        return (i::sql ( "DELETE FROM i_users WHERE LOWER(username)=LOWER(?)", $username )>=1);
    }
    /**
     * Tells whether or not a user is logged in
     * @param integer|string $UserID or $SessionID
     * @return Boolean
     */
    function isLoggedIn($userid)
    {
        $r=i::sql("SELECT lastAccess FROM i_users WHERE id=?",$userid);
        return ($r[0]->LastAccess+self::$Timeout<time());
    }

    /**
     * Logs out the current user and rolls the session
     * This would not logout all users in mutli-login mode
     * @param $UserID
     */
    function logout($userid=null)
    {
        if ($userid===null)
            if (i::user()===null)
                return false;
            else
                $userid=i::session()->userId;
        unset(i::session()->userId);
    }

    /**
    *Edits a user credentials
    *@param String $OldUsername
    *@param String $NewUsername
    *@param String $NewPassword leave null to not change
    *@return null on old user doesn't exist, false on new user already exists,  true on success.
     */
    function edit($oldUsername, $newUsername, $newPassword = null)
    {
        if (! $this->exists ( $oldUsername )) return null;
        if ($oldUsername != $newUsername and $this->exists( $newUsername )) return false;
        if ($newPassword)
        {
            $hashedPass=new Password($newUsername, $newPassword);
            j::SQL ( "UPDATE i_users SET username=?, password=?, salt=?, protocol=? WHERE LOWER(username)=LOWER(?)",
                     $newUsername, $hashedPass->hash(),$hashedPass->salt(),$hashedPass->protocol(), $oldUsername);
        }
        else
            j::SQL ( "UPDATE i_users SET username=? WHERE LOWER(username)=LOWER(?)", $newUsername, $oldUsername );
        return true;
    }
    /**
     * Validates a user credentials
     * @param username of the user
     * @param password of the user
     * @return boolean
     */
    function checkCredentials($username, $password)
    {
        $res=jf::SQL("SELECT * FROM i_users WHERE LOWER(username)=LOWER(?)",$username);
        if (!$res) return false;
        $res=$res[0];
        return Password::validate($username, $password, $res->password, $res->salt,$res->protocol);

    }
    /**
     * Logs a user in only by user ID without needing valid credentials. Intended for system use only.
     * This is the core login function, it is called everytime a user is trying to log in
     *
     * @param integer $userid
     * @return boolean
     */
    function login($userid)
    {
        if ($this->exists($userid))
        {
            i::session()->userid=$userid;
            return true;
        }
        return false;
    }

    function exists($username)
    {
        $res=i::sql( "SELECT * FROM i_users WHERE LOWER(username)=LOWER(?)", $username );
        return $res!=null;
    }
    function idExist($userid)
    {
        $res=i::sql ( "SELECT * FROM i_users WHERE id=?", $userid);
        return $res!=null;
    }
    /**
     * Creates a new user in the system
     * @param Username of the new user
     * @param Password of the new user
     * @return integer UserID on success
     * null on User Already Exists
     */
    function create($username, $password)
    {
        if ($this->exists ( $username )) return false;
        $hash=new Password($password);
        return  i::sql( "INSERT INTO i_users (username,password,salt,protocol,lastAccess)
			VALUES (?,?,?,?,?)", $username, $hash->hash(), $hash->salt(),$hash->protocol(),0);
    }

    /**
     * returns Username of a user
     *
     * @param Integer $userid
     * @return String
     */
    function username($userid=null)
    {
        if ($userid===null)
            $userid=i::currentUser();
        $res= i::sql( "SELECT username FROM i_users WHERE id=?", $userid );
        if ($res)
            return $res[0]->username;
        else
            return null;
    }

    /**
     *
     * @param string $username
     * @return integer UserID null on not exists
     */
    function userId($username)
    {
        $res=i::sql("SELECT ID FROM i_users WHERE LOWER(username)=LOWER(?)",$username);
        if ($res)
            return $res[0]->id;
        else
            return null;

    }

    /**
     * Returns total number of users
     * @return integer
     */
    function count()
    {
        $res=i::sql("SELECT COUNT(*) AS res FROM i_users");
        return $res[0]->res;
    }

    /**
     * Update last access on user
     * @param $userid
     * @return mixed
     */
    function touch($userid)
    {
        return i::sql( "UPDATE i_users SET lastAccess=? WHERE id=?", time(),$userid);
    }

}
###http.php:
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
    static function notFound()
    {
        header("404 Not Found");
        echo "<html><head><title>404 Not Found</title></head>\n<body><h1>404 Not Found</h1>\nThe requested page <strong>".safe(i::request())."</strong> not found.\n</body></html>";

    }
}
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
                if (!###install.php:
if (file_exists(".htaccess") or file_exists("../.htaccess"))
  die("Please remove .htaccess to enable installation.");
$CLI=(php_sapi_name() === 'cli');
$EOL=$CLI?PHP_EOL:"<br/>";
$tab=$CLI?"    ":str_repeat("&nbsp;",4);
function head($text)
{
    global $CLI,$EOL;
    if ($CLI)
        echo "* {$text} ...",$EOL;
    else
        echo "<h2>{$text}</h2>",$EOL;
}
head("Creating .htaccess");
$htaccess="RewriteEngine on
#RewriteBase ".i::url()."
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^?]*)$ start.php?__base=$1 [NC,L,QSA]";


if ((file_exists(".htaccess") and is_writable(".htaccess")) or is_writable(getcwd()))
{
    file_put_contents(".htaccess",$htaccess);
    echo ".htaccess successfully created.",$EOL;
}
else
{
    echo $tab,"I don't have access to write '.htaccess'.",$EOL;
    echo $tab,"Please copy the following in '.htaccess' file, inside root of iframework:",$EOL;
    if (!$CLI)
        echo "<pre style='background-color:#CCC'>";
    echo $htaccess;
    if (!$CLI)
        echo "</pre>";
    echo $tab,"Creation of the '.htaccess' file makes me understand that installation is finished.",$EOL;
}

echo str_repeat("_",80),$EOL;


head("Setting up database");
$queries="
DROP DATABASE iframework;
CREATE DATABASE iframework;
USE iframework;
CREATE TABLE IF NOT EXISTS `i_userstore` (
  `userid` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (userid,name)
);
CREATE TABLE IF NOT EXISTS `i_settings` (
  `name` varchar(200) NOT NULL PRIMARY KEY,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL
);
CREATE TABLE IF NOT EXISTS `i_users` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(255) NOT NULL,
  `protocol` float NOT NULL,
  `lastAccess` int(11) NOT NULL
);
";
if (i::db()->driver=="sqlite")
{
  $queries=str_replace(array("AUTO_INCREMENT","int(11)"),array("AUTOINCREMENT","INTEGER"),$queries);
  if (!is_writable(dirname(i::db()->dbname)))
    die("The SQLite folder is not writable: ".i::db()->dbname);
  file_put_contents(i::db()->dbname,"");
}

    $queries=explode(";",$queries);
array_pop($queries); //remove last
foreach ($queries as $query)
{
    echo $tab,$query."; ";
    try {
      $res=i::sql($query);
    }
    catch (PDOException $e)
    {
      $res=false;
    }
    if ($res)
        echo "(Success)",$EOL;
    else
        echo "(Failed)",$EOL;
}
echo str_repeat("_",80),$EOL;

die("All done.");
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
        if ($file=="")
            $file="index";
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


function iframework_boot()
{

	i::__init("http",new HTTP());

	$setup_function='setup';
	if (!function_exists($setup_function))
		die("You should define a '{$setup_function}' function before running iframework.");
	setup();

	$reflFunc = new ReflectionFunction($setup_function);
	i::__init("root",dirname($reflFunc->getFileName()));

	i::__init("users",new UserManager());
	i::__init("session",new Session());
	if (isset($_GET['__base']))
	{
	    i::__init("request",$_GET['__base']);
	    unset($_GET['__base']);
	}

	if (php_sapi_name() === 'cli' or isset($_GET['__install'])) //install mode
	{ //don't remove this brace
	    include __DIR__."/install.php";
	}

}
iframework_boot();