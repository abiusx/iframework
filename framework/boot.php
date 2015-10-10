<?php
require_once __DIR__."/framework.php";

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