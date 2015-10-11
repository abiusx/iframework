<?php
#FIXME: force refresh on exists, or insert will fail
class UserStore extends Store
{
    function __construct()
    {
    }
    function __destroy()
    {
    }
    private function getUserId($userid)
    {
        if ($userid===null)
            $userid=i::currentUser();
        if ($userid===null)
            throw new Exception("UserStore can not store without a userid or logged in user.");
        return $userid;
    }
    function clear($userid=null)
    {
        $userid=$this->getUserId($userid);
        return i::sql("DELETE FROM i_userstore WHERE userid=?",$userid);
    }
    function __set($item,$value)
    {
        return $this->set(null,$item,$value);
    }
    function set($userid,$name,$value,$expiration=null)
    {
        $userid=$this->getUserId($userid);
        if ($this->exists($userid,$name))
            if ($expiration!==null)
                $res=i::sql("UPDATE i_userstore SET value=? AND expiration=? WHERE userid=? AND name=? ",serialize($value),$expiration,$userid,$name,time());
            else
                $res=i::sql("UPDATE i_userstore SET value=? WHERE userid=? AND name=?",serialize($value),$userid,$name);
        else
        {
            if ($expiration===null)
                $expiration=UserStore::EXPIRATION_INDEFINITE;
            $res=i::sql("INSERT INTO i_userstore (userid,name,value,expiration) VALUES (?,?,?,?)",$userid,$name,serialize($value),time()+$expiration);
        }
        $this->refresh();
        return $value;
    }
    protected function refresh($force=false)
    {
        if ($force or rand()%1000 < self::$RefreshRate*1000) //1 percent chance
            i::sql("DELETE FROM i_userstore WHERE expiration<?",time());
    }
    function __isset($item)
    {
        return $this->exists(null,$item);
    }
    function __unset($item)
    {
        $this->delete(null,$item);
    }
    function delete($userid,$name)
    {
        $userid=$this->getUserId($userid);
        $res=i::sql("DELETE FROM i_userstore WHERE userid=? AND name=? LIMIT 1",$userid,$name);
        return $res>0;
    }
    function exists($userid,$name)
    {
        $userid=$this->getUserId($userid);
        $tres=i::sql("SELECT * FROM i_userstore WHERE userid=? AND name=?",$userid,$name);
        $res=i::sql("SELECT * FROM i_userstore WHERE userid=? AND name=? AND expiration>=?",$userid,$name,time());
        if (!empty($tres) and empty($res)) //expired, flush
            $this->refresh(true);        
        return !empty($res);
    }
    function get($userid,$name)
    {
        $userid=$this->getUserId($userid);
        $res=i::sql("SELECT value FROM i_userstore WHERE userid=? AND name=? AND expiration>=?",$userid,$name,time());
        $this->refresh();
        if (!empty($res))
            return unserialize($res[0]->value);
        else
            return null;
    }
    function __get($item)
    {
        return $this->get(null,$item);
    }
}