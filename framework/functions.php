<?php
/**
 * The print_r equivalent formatted for HTML (instead of clear text)
 * @param $var
 */
function print_($var)
{
    if ($var===null)
        $data="NULL";
    elseif ($var===false)
        $data="False";
    elseif ($var===true)
        $data="True";
    else
        $data=print_r($var,true);
    if (php_sapi_name() === 'cli')
        echo $data."\n";
    else
        echo nl2br(str_replace(" ","&nbsp;",htmlspecialchars($data)))."<br/>";
    flush();
    if (ob_get_contents()) ob_flush();
}

/**
 * Makes a parameter XSS-safe
 * @param $data
 * @return string
 */
function safe($data)
{
    if (defined("ENT_HTML401"))
        return htmlspecialchars($data,ENT_QUOTES | ENT_HTML401,"UTF-8");
    else
        return htmlspecialchars($data,ENT_QUOTES,"UTF-8");
}

/**
 * Works like printf, but only the format string is allowed to have HTML.
 * Parameters are escaped for XSS.
 * @return mixed
 */
function prints()
{
    $args=func_get_args();
    for ($i=1;$i<count($args);++$i)
        $args[$i]=safe($args[$i]);
    return call_user_func_array("printf",$args);
}

/**
 * Like prints, but returns the result instead of printing it
 * @return mixed
 */
function sprints()
{
    $args=func_get_args();
    for ($i=1;$i<count($args);++$i)
        $args[$i]=safe($args[$i]);
    return call_user_func_array("sprintf",$args);

}

/**
 * Maps an array (2D) using array_map, to another array only having one field of the original array
 * @param $array
 * @param $field
 * @return array
 */
function map($array,$field)
{
    if ($array===null) return null;
    return array_map(function($item) use ($field) { return $item[$field];},$array);
}

/**
 * Expression map
 * Maps an array into another array by running an expression on every entry
 * e.g emap(array(1,2,3),"_*2") will return array(2,4,6)
 * @warning The placeholder is _, and the function uses eval to run the expression
 * @param $array
 * @param $expression should be an string, with _ as the placeholder for variable
 * @return array
 */
function emap($array,$expression)
{
    return array_map(function ($v) use ($expression) {
        $code="return(".str_replace("_",'$v',$expression).");";
        return eval($code);
    },$array);
}

/**
 * Similar to emap, but does map-reduce instead of map
 * @warning the function uses eval to run the expression
 * @param $array
 * @param $expression __ denotes the carry, _ denotes iteration variable
 * @param null $initial
 * @return mixed
 */
function emapr($array,$expression,$initial=null)
{
    return array_reduce($array,function($carry,$v) use ($expression){
        $expression=str_replace("__",'$carry',$expression);
        $code="return(".str_replace("_",'$v',$expression).");";
        return eval($code);
    },$initial);
}