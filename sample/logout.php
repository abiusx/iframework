<?php
$res=i::users()->logout();
if ($res)
{
	setcookie("remember_me","",time()-3600); //delete cookie
	$this->success[]='Succesfully logged out.';
}
else
	$this->warning[]="You are not logged in.";

return $this->view();