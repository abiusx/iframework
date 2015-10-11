<?php
$this->header();
if (i::currentUser())
{
	echo "Email: ",i::userStore()->email;
}
else
{
	echo "Not logged in.",PHP_EOL;
}
?>
<div style='margin:auto;width:200px;font-size:smaller;text-align:center;padding:10px;'>
<a href='login'>Login</a> |
<a href='signup'>Sign Up</a> |
<a href='logout'>Logout</a> |
</div>

<?php
$this->footer();