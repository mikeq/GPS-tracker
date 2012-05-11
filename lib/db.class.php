<?php
  /**
     * DatabaseConnection
     *
     * Singleton pattern that handles getting a MySQLi database connection
     *
     * @package util
     * @subpackage DatabaseConnection
     *
     * @author Mike Quinn
     * @version 1.0
    */

class DatabaseConnection{
    protected $dbConnection;
    private $connectionProperties = array();
    protected static $dsnConns = array();
    private $locks = array();

    /**
     * Constructor, declared as private, responsible for instantiating
     * the database connection
     *
     * @access private
     * @param String $dsn
     */
    private function __construct($dsn){
        try{
            if ($dsn == '' || !is_string($dsn) || strpos($dsn,"|")===false){
                throw new DsnException("The DSN needs to be a string with values separated by pipe character (|)\n i.e. host:port|username|password|database");
            }
            $this->connectionProperties = explode("|",$dsn);
            if (count($this->connectionProperties) != 4){
                throw new DsnException("The DSN needs to be a string with values separated by pipe character (|)\n i.e. host:port|username|password|database");
            }
            if (stristr($this->connectionProperties[0],':')){
                $hostport = explode(':',$this->connectionProperties[0]);
                $host = $hostport[0];
                $port = $hostport[1];
                $socket = $hostport[2];
                if ($port == 'null'){
                    $this->dbConnection = new mysqli($host,$this->connectionProperties[1],$this->connectionProperties[2],$this->connectionProperties[3], null, $socket);
                }
                else{
                    $this->dbConnection = new mysqli($host,$this->connectionProperties[1],$this->connectionProperties[2],$this->connectionProperties[3], $port, $socket);
                }
            }
            else{
                $host = $this->connectionProperties[0];
                $port = Settings::DEFAULT_MYSQL_PORT;
                $this->dbConnection = new mysqli($host,$this->connectionProperties[1],$this->connectionProperties[2],$this->connectionProperties[3], $port);
            }


            if (mysqli_connect_errno($this->dbConnection)){
                throw new DatabaseConnectionException();
            }
        }
        catch(DatabaseConnectionException $e){
            throw $e;
        }
    }

    /**
     * This static function is responsible for returning
     * an instance of the DatabaseConnection class
     *
     * @access public
     * @param String $dsn
     */
    public static function getInstance($dsn=Settings::MASTER_DSN){
        if (!array_key_exists($dsn, self::$dsnConns)){
            self::$dsnConns[$dsn] = new DatabaseConnection($dsn);
        }

        return self::$dsnConns[$dsn];
    }

    /**
     * Function returns the database connection object
     *
     * @access public
     */
    public function getConnection(){
        return $this->dbConnection;
    }

    /**
     * Determines if the requested lock name is already used as a lock.
     *
     * @param string $lockName The name of the lock
     * @return boolean
     */
    private function isLocked($lockName){
        $checkLock = "SELECT IS_FREE_LOCK('$lockName')";
        $result = $this->dbConnection->query($checkLock);
        $check = $result->fetch_array();
        $result->free();

        if ($check[0] == 0){
            //is locked
            return true;
        }
        else{
            //is not locked
            return false;
        }
    }

    /**
     * Determines if the lock is in use by this database instance.
     *
     * @param string $lockName The name of the lock
     * @return integer|boolean Will return either the key in the $this->locks array or false
     */
    private function alreadyHaveLock($lockName){
         return array_search($lockName, $this->locks);
    }

    /**
     * Implementation of the mysql function GET_LOCK.  Attempts to obtain a lock with the
     * name passed in.
     *
     * Note that calling a subsequent DatabaseConnection::getLock in your code will automatically release
     * any lock that came before it.  This is the default behaviour of MySQL.
     *
     * @param string $lockName The name of the lock
     * @param integer $timeOut Time in seconds to wait for a lock
     * @return boolean Will return true if lock was successful otherwise will return false
     */
    public function getLock($lockName, $timeOut=10){

        if ($this->isLocked($lockName)){
            if ($this->alreadyHaveLock($lockName) !== false){
                return true;
            }
            else{
                return false;
            }
        }

        $getLock = "SELECT GET_LOCK('$lockName',$timeOut)";
        $result = $this->dbConnection->query($getLock);

        $lockRecord = $result->fetch_array();
        $result->free();

        if ($lockRecord[0] == 0){
            return false;
        }
        else{
            $key = $this->alreadyHaveLock($lockName);

            if ($key === false){
                 $this->locks[] = $lockName;
            }

            return true;
        }
    }

    /**
     * Implementation of the mysql function RELEASE_LOCK.  Attempts to free
     * the lock with the name passed in.
     *
     * @param string $lockName The name of the lock
     * @return void
     */
    public function freeLock($lockName){
        $freeLock = "SELECT RELEASE_LOCK('$lockName')";
        $result = $this->dbConnection->query($freeLock);

        $lockRecord = $result->fetch_array();
        $result->free();

        if ($lockRecord[0] == 1){
            $key = $this->alreadyHaveLock($lockName);

            if ($key !== false){
                unset($this->locks[$key]);
            }
        }

    }

    public function __destruct(){
        foreach($this->locks as $value){
            $this->freeLock($value);
        }
        $this->dbConnection->close();
    }
}

class DatabaseConnectionException extends Exception{
    public function __construct(){
        parent::__construct(mysqli_connect_error(),mysqli_connect_errno());
    }
}

class DsnException extends Exception{}

?>