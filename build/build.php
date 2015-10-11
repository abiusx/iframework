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
	$replacements=[];
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
					$line=$token[2];
					// $init_pos=0;
					// while ($line-->0)
					// 	$init_pos=strpos($code,PHP_EOL,$init_pos+1);
					// $start_pos=strpos($code, $token[1],$init_pos); //include, require, etc.
					// $end_pos=strpos($code,";",$start_pos+1); //find the semicolon

					// $replacements[]=array($start_pos,$end_pos,$new_file);
					$replacements[]=array($line,$new_file);
				}


			}
		}
	}
	$code_array=explode(PHP_EOL,$code);
	$shift=0;
	foreach ($replacements as $replacement)
	{
		list($line,$new_file)=$replacement;
		$new_code_array=explode(PHP_EOL,get_code($new_file));
		$code_array=array_merge(array_slice($code_array,0,$shift+$line-1),
			$new_code_array,array_slice($code_array,$line+$shift));
		$shift+=count($new_code_array)-1;
	}
	$code=implode(PHP_EOL,$code_array);

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