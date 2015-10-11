<?php
function evaluate($tokens,$file)
{
	$str="";
	// array_pop($tokens); //remove semicolon
	foreach ($tokens as $token)
	{
		if (is_array($token))
		{
			$code=$token[0];
			if ($code==T_CONSTANT_ENCAPSED_STRING)
				$str.=substr($token[1],1,-1);
			elseif ($code==T_DIR)
				$str.=dirname($file);
			// echo token_name($token[0]),":",$token[1],"(",$token[2],")",PHP_EOL;
		}
		else ;
			// echo "T_STRING",":",$token,PHP_EOL;
	}
	return $str;
}
function get_code($file)
{
	echo "Processing ".$file.PHP_EOL;
	$code=file_get_contents($file);
	$tokens=token_get_all($code);
	for ($i=0;$i<count($tokens);++$i)
	{
		$token=$tokens[$i];
		if (is_array($token))
		{
			if ($token[0]==T_INCLUDE or $token[0]==T_REQUIRE or $token[0]==T_REQUIRE_ONCE or $token[0]==T_INCLUDE_ONCE)
			{
				$line=$token[2];
				$include_tokens=[];
				do
					$include_tokens[]=$tokens[$i++];
				while ($tokens[$i]!=";");
				$new_file=evaluate($include_tokens,$file);
				if ($new_file and realpath($new_file))
				{
					$start_pos=strpos($code, $token[1]); //include, require, etc.
					$end_pos=strpos($code,";",$start_pos+1); //find the semicolon

					$code=substr($code,0,$start_pos)."###".basename($new_file).":".
					get_code($new_file)
					.substr($code,$end_pos+1);
				}


			}
		}
	}

	if (substr($code,0,2)=="<?")
	{
		if (substr($code,0,5)=="<?php")
			$code=substr($code,5);
		else
			$code=substr($code,2);
	}
	if (substr($code,-2)=="?>")
		$code=substr($code,0,-2);
	return $code;
}
echo "Starting iframework build...",PHP_EOL;
$code=get_code(__DIR__."/../framework/boot.php");
$version=trim(`git rev-list HEAD | wc -l`);
if (!$version)
	$version="1.0";

file_put_contents("iframework.{$version}.php","<?php\n".$code);
echo "Done.",PHP_EOL;