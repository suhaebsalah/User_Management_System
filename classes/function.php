<?php 
function reduse($url){
    header("Location: $url");
    exit();
}



function checking($i){
   global $session;

   if($i===1){
      if($session->get_log_in()){
         reduse("index.php");
      }
   }
    if($i===2){
        if(!$session->get_log_in()){
            reduse("login.php");
        }
    }
    if($i===3){
        if(($session->get_log_in() || !$session->get_log_in()) && (!isset($_SESSION['role_admin']) || $_SESSION['role_admin']!=2)){
            reduse("index.php");
        }
    }
}

if(isset($_GET['logout'])){
    if(isset($session)){
        $session->logout();
    }
    reduse("login.php");
    exit;
}


?>