<?php
class Database
{
    public $driver;
    public $username,$password,$host,$dbname;
    protected $connection;
    function __construct($username,$password,$dbname=null,$driver="mysql",$host="localhost")
    {
        $this->driver=$driver;
        $this->username=$username;
        $this->password=$password;
        $this->host=$host;
        $this->dbname=$dbname;
        $this->connect();
    }
    function connect()
    {
        if ($this->driver=="sqlite")
            $this->connection=new \PDO("{$this->driver}:{$this->dbname}",$this->username,$this->password);//;username={$this->username};password={$this->password}");
        else
            $this->connection=new \PDO("{$this->driver}:host={$this->host};dbname={$this->dbname}",$this->username,$this->password);//;username={$this->username};password={$this->password}");
         $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); //exception on error
    }

    /**
     * @param $query
     * @param ... args
     * @return rowCount on INSERT/DELETE, empty array or result array on SELECT, direct query result otherwise
     */
    function sql($query)
    {
        $args = func_get_args ();
        array_shift ( $args );
        $statement = $this->connection->prepare ( $query );
        $out=$statement->execute ($args);
        $type = substr ( trim ( strtoupper ( $query ) ), 0, 6 );
        if ($type == "INSERT")
        {
            $res = $this->connection->lastInsertId(); // returns 0 if no auto-increment found
            if ($res == 0)
                $res = $statement->rowCount ();
            return $res;
        }
        elseif ($type == "DELETE" or $type == "UPDATE" or $type == "REPLAC")
            return $statement->rowCount ();
        elseif ($type == "SELECT")
        {
            $res=[];
            while ($r=$statement->fetchObject())
                $res[]=$r;
            return $res;
        }
        else
            return $out;
    }
}