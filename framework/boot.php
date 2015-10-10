<?php
/**
 * User: abiusx
 * Date: 5/24/15
 * Time: 6:30 PM
 */

require_once __DIR__."/framework.php";
i::__init("http",new iframework\HTTP());

// require_once "config.php";
if (!function_exists("config"))
	die("You should define a config function before running iframework.");
config();

i::__init("users",new iframework\UserManager());
i::__init("session",new iframework\Session());
if (isset($_GET['__base']))
{
    i::__init("request",$_GET['__base']);
    unset($_GET['__base']);
}

if (php_sapi_name() === 'cli' or isset($_GET['__install'])) //install mode
{
    if (file_exists(".htaccess"))
        die("Please remove .htaccess to enable installation.");
    include __DIR__."/install.php";
}
var_dump(i::request());
