<?php

//////////////////////////////////////////////////
//                                              //
//     usage: php recover1.php [routine_id]     //
//                                              //
//////////////////////////////////////////////////

include 'fx_log.php';
include 'fx_recovery.php';

///////===Reading config.ini===////////

$conf_array = parse_ini_file("config.ini", true);

///////===Working DB detail===////////
$db_server_working = $conf_array["working_db"]["host"];
$db_user_working = $conf_array["working_db"]["username"];
$db_pass_working = $conf_array["working_db"]["password"];
$db_working = $conf_array["working_db"]["db"];

///////===Backup DB detail===////////
$db_server_backup = $conf_array["backup_db"]["host"];
$db_user_backup = $conf_array["backup_db"]["username"];
$db_pass_backup = $conf_array["backup_db"]["password"];
$db_backup = $conf_array["backup_db"]["db"];

///////===Salt for hash password===////////
$salt = "DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9miAA";

////-----------------------------------------////
////        Radiusdesk hash password         ////
////   $hashpass = sha1($salt.$new_pass1);   ////
////-----------------------------------------////


//===========Do not touch any code below=============//

//====================DB Connect to Working DB================//
$conn_working = mysqli_connect($db_server_working, $db_user_working, $db_pass_working, $db_working);

// Check connection
if (!$conn_working) {
  die("Connection failed: " . mysqli_connect_error());
}


//====================DB Connect to Backup DB================//
$conn_backup = mysqli_connect($db_server_backup, $db_user_backup, $db_pass_backup, $db_backup);

// Check connection
if (!$conn_backup) {
  die("Connection failed: " . mysqli_connect_error());
}

if(isset($argv[1])){
    $last_routine_id = get_routine_id($conn_backup);
    $try_routine_id = $argv[1];
    if($last_routine_id >= $try_routine_id){
        $routine_id = $try_routine_id;
    }
}else{
    $routine_id = get_routine_id($conn_backup);
}

$recover_permanent_users = 0;
if(isset($argv[2])){
  if ($argv[2] == 1 || strtolower($argv[2]) == 'y') {
    $recover_permanent_users = 1;
  }
}

$data_userlist_working = get_user_list($conn_working);

$data_userlist_backup = get_user_pass_on_racheck_backup($conn_backup, $data_userlist_working, $salt, $routine_id);


$processname = "recovery";
$event_log = "";
$event_log = "routine ID = ".$routine_id;
writelog($conn_backup, $event_log, $processname." - get_routine_id");

////////================ Table Permanent_users ================////////

if($recover_permanent_users == 1){
  delete_record($conn_working,$data_userlist_backup,"permanent_users");
  recover_record($conn_working,$conn_backup, $data_userlist_backup, $db_working, "permanent_users", $db_backup, "permanent_users_backup", $routine_id);
  $event_log = "recover permanent_users with routine ID -> ".$routine_id;
  writelog($conn_backup, $event_log, $processname." - permanent_users");
}

////////================ Table radcheck ================////////

delete_record($conn_working,$data_userlist_backup,"radcheck");
recover_record($conn_working,$conn_backup, $data_userlist_backup, $db_working, "radcheck", $db_backup, "radcheck_backup", $routine_id);
$event_log = "recover radcheck with routine ID -> ".$routine_id;
writelog($conn_backup, $event_log, $processname." - radcheck");

////////================ Table radreply ================////////

delete_record($conn_working,$data_userlist_backup,"radreply");
recover_record($conn_working,$conn_backup, $data_userlist_backup, $db_working, "radreply", $db_backup, "radreply_backup", $routine_id);
$event_log = "recover radreply with routine ID -> ".$routine_id;
writelog($conn_backup, $event_log, $processname." - radreply");




?>