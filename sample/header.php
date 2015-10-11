<html>

<head>

<?php if (isset($this->title)): ?><title><?=$this->title?></title><?php endif;?>
<script src='static/jquery-1.11.3.min.js'></script>
<link rel="stylesheet" type="text/css" href='static/style.css'/>

</head>
<body>

<div id='body'>
<?php
foreach (array("error",'success','warning') as $type)
if (isset($this->{$type}))
	if (is_array($this->{$type}))
		foreach ($this->{$type} as $item)
			prints("<div class='{$type}'>%s</div>\n",$item);
	else
		prints("<div class='{$type}'>%s</div>\n",$this->{$type});
