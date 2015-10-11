<?php
#TODO: email validation
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
	if (strtolower($this->post("captcha"))!==strtolower(i::session()->captcha_signup))
		$this->error[]="Invalid CAPTCHA.";
	unset(i::session()->captcha_signup);

	if (empty($this->error))
	{
		$username=$this->post('username');
		$password=$this->post('password');
		$userid=null;
		if (!i::users()->exists($username))
			$userid=i::users()->create($username,$password);
		if ($userid===null)
			$this->error[]="Username already in use.";
		else
		{
			$res=i::users()->login($userid);	
			i::userStore()->email=$this->post('email');
			$this->success[]="User ".safe($this->post('username'))." succesfully created. ";
		}
	}
}
$this->view();