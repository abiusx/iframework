<fieldset id='login'>
<legend>Login</legend>
<?php
if (!i::currentUser()):
?>
<form method='post'>
<label>Username:</label><input type='text' name='username' value='<?=$this->post('username')?>'/><br/>
<label>Password:</label><input type='password' name='password'  value='<?=$this->post('password')?>'/><br/>
<label>Remember:</label><input type='checkbox' name='remember' <?=$this->post('remember')?'checked="checked"':'';?>'/><br/>
<?php if ($this->captcha): ?>
<label>CAPTCHA:</label><input type='text' name='captcha' /><img src='captcha?t=login' height='16'/><br/>
<?php endif; ?>
<input type='submit' value='Login' />
</form>
<?php else:
echo "Already logged in. Logout to be able to login again.";
endif;
?>
</fieldset>
<a href='./'>Back</a>