<?php
class Controller
{
    protected $error=[],$warning=[],$success=[];
    public $request,$file;
    function __construct($request=null)
    {
        $this->request=$request;
    }
    /**
     * Returns null if not posted, otherwise the post array
     * @return null|array
     */
    function post($index=null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return null;
        if ($index!==null)
            if (isset($_POST[$index]))
                return $_POST[$index];
            else
                return null;
        else
            return $_POST;
    }
    function start()
    {
        if ($this->request===null)
            $this->request=i::request();
        $parts=explode("/",$this->request);
        $file=array_pop($parts);
        if ($file=="")
            $file="index";
        $realpath=realpath(i::root()."/".implode("/",$parts));
        $phpfile=realpath($realpath."/".$file.".php");
        if (!$realpath or substr($realpath,0,strlen(i::root()))!==i::root())
            die(i::http()->notFound());
        elseif ($phpfile)
        {
            $this->file=$phpfile;   
            i::$controller=$this;
            require $phpfile;
        }
        else
            die(i::http()->notFound()); 
    }

    /**
     * View a file (with templates)
     * @param  [type] $file either a file to view (if relative, in form of a request, if absolute, all filename) or empty to
     * use the controller-file_view.php as the view file.
     */
    function view($file=null)
    {
        if ($file===null)
            $file=substr($this->file,0,-4)."_view.php";
        if ($file and $file[0]=="/") //absolute
            $file=realpath($file);
        else
            $file=realpath(i::root()."/{$file}.php");
        if (!$file)
            throw new Exception("View file '{$file}.php' not found.");

        $t=$file;
        do
        {
            $inc=dirname($t)."/header.php";
            if (file_exists($inc))
                if (!include $inc) break;
            $t=dirname($t);
        }
        while($t!==i::root());
        include $file;
        $t=$file;
        
        do
        {
            $inc=dirname($t)."/footer.php";
            if (file_exists($inc))
                if (!include $inc) break;
            $t=dirname($t);
        }
        while($t!==i::root());
    }
}