<?php

////////////////////////////////////
//                                //
//     usage: php backup1.php     //
//                                //
////////////////////////////////////

include 'fx_log.php';
include 'fx_backup.php';


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


//===========Do not touch any code below=============//

$processname = "backup";
$event_log = "";

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


//======Create routine ID=========//
create_routine($conn_backup);
//----------------------------------

$routine_id = get_routine_id($conn_backup);
if($routine_id == "error"){
    $event_log = "routine ID - ERROR";
    writelog($conn_backup, $event_log, $processname." - get_routine_id");
    exit($event_log);
}else{
    $event_log = "routine ID = ".$routine_id;
    writelog($conn_backup, $event_log, $processname." - get_routine_id");
}

$num_rows_permanent_users = get_count_record($conn_working, "permanent_users");
$num_rows_radcheck = get_count_record($conn_working, "radcheck");
$num_rows_radreply = get_count_record($conn_working, "radreply");
$event_log = "count record -> permanent_users=".$num_rows_permanent_users.", radcheck=".$num_rows_radcheck.", radreply=".$num_rows_radreply." ";
writelog($conn_backup, $event_log, $processname." - get_routine_id");

//======= log good user condition and bad user condition ===========//

$good_users = get_good_bad_user($conn_working, 1);
$bad_users = get_good_bad_user($conn_working, 0);

$saparated_chunk = 5;
$chunks_good = array_chunk($good_users, $saparated_chunk);
$chunks_bad = array_chunk($bad_users, $saparated_chunk);

//====Generated log for good users====//
foreach ($chunks_good as $row) {
    $msg = "";
    foreach ($row as $value) {
        if($value <> ""){
            $count_record_radcheck = get_count_user_record($conn_working, $value, "radcheck");
            $count_record_radreply = get_count_user_record($conn_working, $value, "radreply");
            $msg .= " ".$value."[".$count_record_radcheck."]"."[".$count_record_radreply."]";    
        }
    }
    if($msg <> ""){
        $event_log = "Routine=".$routine_id." Backup users-> ".$msg;
        writelog($conn_backup, $event_log, $processname." - good users");
    }
}

//====Generated log for bad users====//
foreach ($chunks_bad as $row) {
    $msg = "";
    foreach ($row as $value) {
        if($value <> ""){
            $count_record_radcheck = get_count_user_record($conn_working, $value, "radcheck");
            $count_record_radreply = get_count_user_record($conn_working, $value, "radreply");
            $msg .= " ".$value."[".$count_record_radcheck."]"."[".$count_record_radreply."]";         
        }       
    }
    if($msg <> ""){
        $event_log = "Routine=".$routine_id." Ignore users-> ".$msg;
        writelog($conn_backup, $event_log, $processname." - bad users");
    }
    
}




//=======Check user got password in radcheck========//
$data_check_pass = check_pass_on_radcheck($conn_working , $conn_backup);

if($data_check_pass == "error"){
    $event_log = "error on check_pass_on_radcheck";
    writelog($conn_backup, $event_log, $processname." - check_pass_on_radcheck");
    exit($event_log);
}

//======process permanent_users table=========//
$data_permanent_users = get_table_data($conn_working, $data_check_pass, "permanent_users");

if($data_check_pass == "error"){
    $event_log = "error on get_table_data permanent_users";
    writelog($conn_backup, $event_log, $processname." - get_table_data permanent_users");
    exit($event_log);
}

record_table_data($conn_working, $db_working, $conn_backup, $data_permanent_users, "permanent_users", "permanent_users_backup", $routine_id);

//======process radcheck table=========//
$data_radcheck = get_table_data($conn_working, $data_check_pass, "radcheck");
$event_log = "error on get_table_data radcheck";
if($data_check_pass == "error"){
    writelog($conn_backup, $event_log, $processname." - get_table_data radcheck");
    exit($event_log);
}

record_table_data($conn_working, $db_working, $conn_backup, $data_radcheck, "radcheck", "radcheck_backup", $routine_id);


//======process radreply table=========//
$data_radreply = get_table_data($conn_working, $data_check_pass, "radreply");
$event_log = "error on get_table_data radreply";
if($data_check_pass == "error"){
    writelog($conn_backup, $event_log, $processname." - get_table_data radreply");
    exit($event_log);
}

record_table_data($conn_working, $db_working, $conn_backup, $data_radreply, "radreply", "radreply_backup", $routine_id);

//=======log event success with last ID record =======//

$last_permanent_users = get_latest_id($conn_working, "permanent_users");
$last_permanent_users_bcakup = get_latest_id($conn_backup, "permanent_users_backup");
$last_radcheck = get_latest_id($conn_working, "radcheck");
$last_radcheck_backup = get_latest_id($conn_backup, "radcheck_backup");
$last_radreply = get_latest_id($conn_working, "radreply");
$last_radreply_backup = get_latest_id($conn_backup, "radreply_backup");
$last_log_id = get_latest_id($conn_backup, "log_backup");
$current_log_id = intval($last_log_id) + 1;

$event_log = "last record [w:b] -> permanent_users[".$last_permanent_users.":".$last_permanent_users_bcakup."], ";
$event_log .= "radcheck[".$last_radcheck.":".$last_radcheck_backup."], ";
$event_log .= "radreply[".$last_radreply.":".$last_radreply_backup."], ";
$event_log .= "log_backup[".$last_log_id."] and this is id = ".$current_log_id;

$processname = "backup - finish";
writelog($conn_backup, $event_log, $processname);








?>