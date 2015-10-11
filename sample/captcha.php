<?php
if (!isset($_GET['t']))
    $title = "default";
else
    $title = $_GET['t'];
require_once __DIR__."/include/purecaptcha.php";
$p = new PureCaptcha();
i::session()->{"captcha_{$title}"} = $p->show();