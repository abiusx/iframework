<?php
$this->title="User Login";

$attempt_name="login_attempts_from_".i::http()->IP();

if (i::settings()->{$attempt_name}>500)
{
	$this->captcha=true;
	$this->warning[]="Too many login attempts from your IP address.";
}
else
	$this->captcha=false;
if ($this->post())
{
	if (!i::settings()->exists($attempt_name))
		i::settings()->set($attempt_name,1,Store::EXPIRATION_HOUR);
	else
		i::settings()->{$attempt_name}++;

	if ($this->captcha and strtolower($this->post("captcha"))!==strtolower(i::session()->captcha_login))
		$this->error[]="Invalid CAPTCHA.";
	elseif (!i::users()->checkCredentials($this->post("username"),$this->post("password")))
		$this->error[]="Invalid username or password.";
	unset(i::session()->captcha_login);

	if (empty($this->error))
	{
		$username=$this->post('username');
		$userid=i::users()->userId($username);
		$res=i::users()->login($userid);
		if (!$res)
			$this->error[]="Unknown error logging in.";
		else
			$this->success[]="User ".safe($this->post('username'))." succesfully logged in. ";
	}
}
$this->view();