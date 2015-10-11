<?php
$this->title="User Login";
$attempt_name="login_attempts_from_".i::http()->IP();
if (i::currentUser()) //already logged in
	return $this->view();
//remember me restore
if (isset($_COOKIE['remember_me'])) 
{
	$tokens=explode(":",$_COOKIE['remember_me']);
	if (count($tokens)==2)
	{
		list($user_token,$auth_token)=$tokens;
		$res=i::settings()->{$user_token};
		if ($res and count($res)==2)
		{
			if ($auth_token===$res[1]) //success
			{
				$userid=$res[0];
				if (i::users()->login($userid))
					$this->success[]="Succesfully logged in.";
				return $this->view();
			}
			else //fail, hack attempt
			{
				unset(i::settings()->{$user_token});
				$this->warning[]="Remember me token wsa invalid.";
				setcookie("remember_me","",time()-3600); //delete cookie
			}

		}
	}
}
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
		//remember me
		if ($this->post("remember")) 
		{
			$user_token=md5(random());
			$auth_token=hash("sha256",random());
			// i::userStore()->set($userid,"remember_me",array($user_token,$auth_token),Store.EXPIRATION_WEEK);
			i::settings()->set($user_token,array($userid,$auth_token),Store::EXPIRATION_WEEK);
			setcookie("remember_me","{$user_token}:{$auth_token}");
		}
		
		if (!$res)
			$this->error[]="Unknown error logging in.";
		else
			$this->success[]="User ".safe($this->post('username'))." succesfully logged in. ";
	}
}
return $this->view();