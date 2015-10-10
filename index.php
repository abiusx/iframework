<?php
/**
 * User: abiusx
 * Date: 5/24/15
 * Time: 7:13 PM
 */
include "framework/boot.php";
function config()
{

	i::__init("db",array(new iframework\Database("root","123456aB","iframework.sqlite3","sqlite")));
	i::__init("activeDb",0);
}
