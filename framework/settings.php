<?php

abstract class Store
{
    const EXPIRATION_INDEFINITE=788000000;  //25 years
    const EXPIRATION_YEAR=31536000;     //1 year
    const EXPIRATION_MONTH=2628000;     //1 month
    const EXPIRATION_WEEK=604800;       //1 week
    const EXPIRATION_DAY=86400;         //1 day
    const EXPIRATION_HOUR=3600;         //1 hour
    const EXPIRATION_MINUTE=60;         //1 minute
    static $RefreshRate=.01;
   
}
class Settings extends Store
{
    function clear()
    {
        return i::sql("DELETE FROM i_settings");
    }
    function __set($item,$value)
    {
        return $this->set($item,$value);
    }
  
    function __isset($item)
    {
        return $this->exists($item);
    }
    function __unset($item)
    {
        $this->delete($item);
    }
    function __get($item)
    {
        return $this->get($item);
    }
    function set($name,$value,$expiration=null)
    {
        if ($this->exists($name))
            if ($expiration!==null)
                $res=i::sql("UPDATE i_settings SET value=? AND expiration=? WHERE name=? ",serialize($value),$expiration,$name,time());
            else
                $res=i::sql("UPDATE i_settings SET value=? WHERE name=?",serialize($value),$name);
        else
        {
            if ($expiration===null)
                $expiration=UserStore::EXPIRATION_INDEFINITE;
            $res=i::sql("INSERT INTO i_settings (name,value,expiration) VALUES (?,?,?)",$name,serialize($value),time()+$expiration);
        }
        $this->refresh();
        return $value;
    }
    protected function refresh($force=false)
    {
        if ($force or rand()%1000 < self::$RefreshRate*1000) //1 percent chance
            i::sql("DELETE FROM i_settings WHERE expiration<?",time());
    }
    function delete($name)
    {
        $res=i::sql("DELETE FROM i_settings WHERE name=? LIMIT 1",$name);
        return $res>0;
    }
    function exists($name)
    {
        $tres=i::sql("SELECT * FROM i_settings WHERE name=?",$name);
        $res=i::sql("SELECT * FROM i_settings WHERE name=? AND expiration>=?",$name,time());
        if (!empty($tres) and empty($res)) //expired, flush
            $this->refresh(true);
        return !empty($res);
    }
    function get($name)
    {
        $res=i::sql("SELECT value FROM i_settings WHERE name=? AND expiration>=?",$name,time());
        $this->refresh();
        if (!empty($res))
            return unserialize($res[0]->value);
        else
            return null;
    }

}