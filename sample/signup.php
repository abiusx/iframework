<?php

$this->title="User Signup";

if ($this->post())
{
	if ($this->post("password")!=$this->post("retype"))
		$this->error[]="Password and retype do not match.";
	if (strlen($this->post("password"))<4)
		$this->error[]="Password must be at least 4 characters.";
	if (!preg_match("/[a-z]{1}[a-z0-9_]{2,16}/i", $this->post("username")))
		$this->error[]="Username must be between 3 and 16 characters with only alphanumerics.";
	if (!preg_match("/.+@.+\..+/i",$this->post("email")))
		$this->error[]="Invalid email address.";
}
$this->view();