<?php

include "fx_log.php";

///////===Reading config.ini===////////

$conf_array = parse_ini_file("config.ini", true);

///////===Backup DB detail===////////
$db_server_backup = $conf_array["backup_db"]["host"];
$db_user_backup = $conf_array["backup_db"]["username"];
$db_pass_backup = $conf_array["backup_db"]["password"];
$db_backup = $conf_array["backup_db"]["db"];


//===========Do not touch any code below=============//

//====================DB Connect to Backup DB================//
$conn_backup = mysqli_connect($db_server_backup, $db_user_backup, $db_pass_backup, $db_backup);

// Check connection
if (!$conn_backup) {
  die("Connection failed: " . mysqli_connect_error());
}

//============================================================
$variable_list = get_agrument();

if (isset($variable_list["ev"])) {
    $event = $variable_list["ev"];
}else {
    $event = NULL;
}

if (isset($variable_list["pr"])) {
    $process = $variable_list["pr"];
}else {
    $process = NULL;
}

if (isset($variable_list["st"])) {
    $start = check_datetime($variable_list["st"], "start");
}else {
    $start = NULL;
}

if (isset($variable_list["et"])) {
    $end = check_datetime($variable_list["et"], "end");
}else {
    $end = NULL;
}

if (is_null($start)) {
    $end = NULL;
}elseif (is_null($end)) {
    $end = check_datetime($variable_list["st"], "end");
}

//echo PHP_EOL.$event.PHP_EOL.$process.PHP_EOL.$start.PHP_EOL.$end.PHP_EOL;

$find_data = find_log($conn_backup, $event, $process, $start, $end);
display_log($find_data);

//find_log($conn, $event, $process, $time_start, $time_end){

?>