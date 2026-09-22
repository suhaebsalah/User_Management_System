<?php 

class Config{
    public $db;
    public $error = '';
    public $errno = 0;

    public function __construct(){
        $this->connect();
    }

    public function connect(){
        $this->db=new mysqli("localhost","root","","company_system");
        if($this->db->connect_error){
            die("Connection failed: " . $this->db->connect_error);
        }

    }
    public function query($sql){
        try {
            $result = $this->db->query($sql);
            $this->error = $this->db->error;
            $this->errno = $this->db->errno;
            return $result;
        } catch (mysqli_sql_exception $e) {
            $this->error = $e->getMessage();
            $this->errno = (int)$e->getCode();
            return false;
        }
    }
    public function escape($string){
        return $this->db->real_escape_string($string);
    }
}



?>