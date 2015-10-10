<?php
/**
 * User: abiusx
 * Date: 5/24/15
 * Time: 6:31 PM
 */
namespace iframework;
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
         $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param $query
     * @param ... args
     * @return null
     */
    function sql($query)
    {
        $args = func_get_args ();
        array_shift ( $args );
        $statement = $this->connection->prepare ( $query );
        var_dump($statement);
        if (!$statement)
            print_r($this->connection->errorInfo());
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