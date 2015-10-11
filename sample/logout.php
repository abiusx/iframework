<?php
$res=i::users()->logout();
if ($res)
	$this->success[]='Succesfully logged out.';
else
	$this->warning[]="You are not logged in.";

return $this->view();