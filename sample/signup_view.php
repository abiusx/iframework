<?php

?>
<fieldset id='signup'>
<legend>Sign Up</legend>
<form method='post'>
<label>Username:</label><input type='text' name='username' value='<?=$this->post('username')?>'/><br/>
<label>Password:</label><input type='password' name='password'  value='<?=$this->post('password')?>'/><br/>
<label>Password Retype:</label><input type='password' name='retype'  value='<?=$this->post('retype')?>'/><br/>
<label>Email:</label><input type='text' name='email'  value='<?=$this->post('email')?>'/><br/>
<label>CAPTCHA:</label><input type='text' name='captcha' /><img src='captcha?t=signup' height='16'/><br/>
<input type='submit' value='Submit' />
</form>
</fieldset>

<a href='./'>Back</a>