<?php
if (file_exists(".htaccess") or file_exists("../.htaccess"))
  die("Please remove .htaccess to enable installation.");
$CLI=(php_sapi_name() === 'cli');
$EOL=$CLI?PHP_EOL:"<br/>";
$tab=$CLI?"    ":str_repeat("&nbsp;",4);
function head($text)
{
    global $CLI,$EOL;
    if ($CLI)
        echo "* {$text} ...",$EOL;
    else
        echo "<h2>{$text}</h2>",$EOL;
}
head("Creating .htaccess");
$htaccess="RewriteEngine on
#RewriteBase ".i::url()."
RewriteCond %{REQUEST_FILENAME} !-d
#RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule static/.* - [L,NC]  #static content, serve via web browser
RewriteRule _view$ - [L]    #MVC view files
RewriteRule footer$ - [L]   #template footer files
RewriteRule header$ - [L]     #template header files
RewriteRule include/.*$ x [F]   #don't allow any access to include folders
RewriteRule ^([^?]*)$ start.php?__base=$1 [NC,L,QSA]
";
if ((file_exists(".htaccess") and is_writable(".htaccess")) or is_writable(getcwd()))
{
    file_put_contents(".htaccess",$htaccess);
    echo ".htaccess successfully created.",$EOL;
}
else
{
    echo $tab,"I don't have access to write '.htaccess'.",$EOL;
    echo $tab,"Please copy the following in '.htaccess' file, inside root of iframework:",$EOL;
    if (!$CLI)
        echo "<pre style='background-color:#CCC'>";
    echo $htaccess;
    if (!$CLI)
        echo "</pre>";
    echo $tab,"Creation of the '.htaccess' file makes me understand that installation is finished.",$EOL;
}

echo str_repeat("_",80),$EOL;

$dbname=(i::db()->dbname);
head("Setting up database");
$queries="
DROP DATABASE {$dbname};
CREATE DATABASE {$dbname};
USE {$dbname};
CREATE TABLE IF NOT EXISTS `i_userstore` (
  `userid` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (userid,name)
);
CREATE TABLE IF NOT EXISTS `i_settings` (
  `name` varchar(200) NOT NULL PRIMARY KEY,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL
);
CREATE TABLE IF NOT EXISTS `i_users` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(255) NOT NULL,
  `protocol` float NOT NULL,
  `lastAccess` int(11) NOT NULL
);
";
if (i::db()->driver=="sqlite")
{
  $queries=str_replace(array("AUTO_INCREMENT","int(11)"),array("AUTOINCREMENT","INTEGER"),$queries);
  if (!is_writable(dirname(i::db()->dbname)))
    die("The SQLite folder is not writable: ".i::db()->dbname);
  file_put_contents(i::db()->dbname,"");
}

    $queries=explode(";",$queries);
array_pop($queries); //remove last
foreach ($queries as $query)
{
    echo $tab,$query."; ";
    try {
      $res=i::sql($query);
    }
    catch (PDOException $e)
    {
      $res=false;
    }
    if ($res)
        echo "(Success)",$EOL;
    else
        echo "(Failed)",$EOL;
}
echo str_repeat("_",80),$EOL;

die("All done.");