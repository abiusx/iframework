<?php
class Password
{
    const PROTOCOL_SHA512=1;
    /**
     * change this everytime you change the way password is generated to update
     * @var integer
     */
    protected static $protocol=Password::PROTOCOL_SHA512;
    protected $hash,$salt,$m_protocol;
    function protocol()
    {
        return $this->m_protocol;
    }
    function salt()
    {
        return $this->salt;
    }
    public function __construct($password,$salt=null,$protocol=null)
    {
        if ($protocol===null)
            $this->m_protocol=$protocol=self::$protocol;
        if ($salt===null)
            $this->salt=$salt=hash("sha512",rand().rand());
        if ($protocol==self::PROTOCOL_SHA512)
            $this->hash=hash("sha512",$password.$salt);
    }
    /**
     * Validates if a hashed password is the correct hashed password for a given raw password, username and salt
     *
     * @param string $password the textual password (entered by user at login)
     * @param string $hash the hashed password (retrived from database)
     * @param string $salt the dynamic salt (from database)
     * @param integer $protocol optional for backward compatibility
     * @return boolean
     */
    static function validate($password,$hash,$salt, $protocol=null)
    {
        $temp=new Password($password, $salt,$protocol);
        return ($temp->hash()==$hash);
    }
    function hash()
    {
        return $this->hash;
    }
}

class UserManager
{
    static $Timeout=1800; //30min
    /**
    * Removes a user form system users if exists
    * @param Username of the user
    * @return boolean
     */
    function delete($username)
    {
        return (i::sql ( "DELETE FROM i_users WHERE LOWER(username)=LOWER(?)", $username )>=1);
    }
    /**
     * Tells whether or not a user is logged in
     * @param integer|string $UserID or $SessionID
     * @return Boolean
     */
    function isLoggedIn($userid)
    {
        $r=i::sql("SELECT lastAccess FROM i_users WHERE id=?",$userid);
        return ($r[0]->lastAccess+self::$Timeout<time());
    }

    /**
     * Logs out the current user and rolls the session
     * This would not logout all users in mutli-login mode
     * @param $UserID
     */
    function logout($userid=null)
    {
        if ($userid===null)
            if (self::current()===null)
                return false;
            else
                $userid=self::current();
        unset(i::session()->userid);
        i::session()->regenerate();
        return true;
    }

    /**
    *Edits a user credentials
    *@param String $OldUsername
    *@param String $NewUsername
    *@param String $NewPassword leave null to not change
    *@return null on old user doesn't exist, false on new user already exists,  true on success.
     */
    function edit($oldUsername, $newUsername, $newPassword = null)
    {
        if (! $this->exists ( $oldUsername )) return null;
        if ($oldUsername != $newUsername and $this->exists( $newUsername )) return false;
        if ($newPassword)
        {
            $hashedPass=new Password($newUsername, $newPassword);
            i::SQL ( "UPDATE i_users SET username=?, password=?, salt=?, protocol=? WHERE LOWER(username)=LOWER(?)",
                     $newUsername, $hashedPass->hash(),$hashedPass->salt(),$hashedPass->protocol(), $oldUsername);
        }
        else
            i::SQL ( "UPDATE i_users SET username=? WHERE LOWER(username)=LOWER(?)", $newUsername, $oldUsername );
        return true;
    }
    /**
     * Validates a user credentials
     * @param username of the user
     * @param password of the user
     * @return boolean
     */
    function checkCredentials($username, $password)
    {
        $res=i::SQL("SELECT * FROM i_users WHERE LOWER(username)=LOWER(?)",$username);
        if (!$res) return false;
        $res=$res[0];
        return Password::validate( $password, $res->password, $res->salt,$res->protocol);

    }
    /**
     * Logs a user in only by user ID without needing valid credentials. Intended for system use only.
     * This is the core login function, it is called everytime a user is trying to log in
     *
     * @param integer $userid
     * @return boolean
     */
    function login($userid)
    {
        if ($this->idExists($userid))
        {
            i::session()->userid=$userid;
            return true;
        }
        return false;
    }

    function exists($username)
    {
        $res=i::sql( "SELECT * FROM i_users WHERE LOWER(username)=LOWER(?)", $username );
        return $res!=null;
    }
    function idExists($userid)
    {
        $res=i::sql ( "SELECT * FROM i_users WHERE id=?", $userid);
        return $res!=null;
    }
    /**
     * Creates a new user in the system
     * @param Username of the new user
     * @param Password of the new user
     * @return integer UserID on success
     * null on User Already Exists
     */
    function create($username, $password)
    {
        if ($this->exists ( $username )) return false;
        $hash=new Password($password);
        return  i::sql( "INSERT INTO i_users (username,password,salt,protocol,lastAccess)
			VALUES (?,?,?,?,?)", $username, $hash->hash(), $hash->salt(),$hash->protocol(),0);
    }

    /**
     * returns Username of a user
     *
     * @param Integer $userid
     * @return String
     */
    function username($userid=null)
    {
        if ($userid===null)
            $userid=self::current();
        $res= i::sql( "SELECT username FROM i_users WHERE id=?", $userid );
        if ($res)
            return $res[0]->username;
        else
            return null;
    }

    /**
     *
     * @param string $username
     * @return integer UserID null on not exists
     */
    function userId($username)
    {
        $res=i::sql("SELECT ID FROM i_users WHERE LOWER(username)=LOWER(?)",$username);
        if ($res)
            return $res[0]->id;
        else
            return null;

    }

    /**
     * Returns total number of users
     * @return integer
     */
    function count()
    {
        $res=i::sql("SELECT COUNT(*) AS res FROM i_users");
        return $res[0]->res;
    }

    /**
     * Update last access on user
     * @param $userid
     * @return mixed
     */
    function touch($userid)
    {
        return i::sql( "UPDATE i_users SET lastAccess=? WHERE id=?", time(),$userid);
    }
    /**
     * Returns the current logged in user
     * @return null or integer
     */
    function current()
    {
        if (isset(i::session()->userid))
            return i::session()->userid;
        else 
            return null;
    }

}