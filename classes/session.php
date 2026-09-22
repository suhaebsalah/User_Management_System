<?php
class Session{

    public $id_adimn;//int
    public $role_admin;//int
    private $logg_in;//bool


    public function __construct(){
        session_start();
        $this->check_login();
    }

    public function get_log_in(){
        return $this->logg_in;
    }


    public function login($admin){
        if($admin){
            $this->id_admin= $_SESSION['id_admin']= $admin->id;
            $this->role_admin= $_SESSION['role_admin']= $admin->role;
            $this->logg_in=true;
        }
    }
    public function logout(){
        unset($this->id_admin);
        unset($this->role_admin);
        unset($_SESSION['id_admin']);
        unset($_SESSION['role_admin']);
        $this->logg_in=false;
    }

    public function check_login(){
        if(isset($_SESSION['id_admin']) && isset($_SESSION['role_admin'])){
            $this->id_admin=$_SESSION['id_admin'];
            $this->role_admin=$_SESSION['role_admin'];
            $this->logg_in=true;
        }
        else{
            unset($this->id_admin);
            unset($this->role_admin);
            $this->logg_in=false;
        }
    }
}


?>