<?php
require_once "framework/boot.php";
// include "iframework.3.php";
if (defined("iframework_started")) return;
define ("iframework_started",true);
function setup()
{
	// i::__init("db",array(new iframework\Database("root","123456aB","iframework","mysql")));
	i::__init("db",array(new Database("root","123456aB","iframework.sqlite3","sqlite")));
}
if (!file_exists(__DIR__."/.htaccess"))
	echo "<a href='?__install'>Install Now</a>";
else
	i::serve();
