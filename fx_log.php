<?php

////------------------------------------////
////           start function           ////
////------------------------------------////

function writelog($conn, $event, $process){
    $sql_insert_log = "insert into log_backup (event_log, process_log, log_datetime) values ('".$event."', '".$process."', now() )";
    $conn->query($sql_insert_log);
}

function get_agrument(){
    $longopts  = array(
        "ev::",
        "pr::",
        "st::",
        "et::",
    );
    $options = getopt("", $longopts);
    
    return $options;

}
function check_datetime($text, $time){
    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$text)) 
    {
        if ($time == "end") {
            return $text." 23:59:59";
        }else{
            return $text." 00:00:00";
        }

    } else {

        return NULL;

    }
}
function find_log($conn, $event, $process, $time_start, $time_end){
    
    $where_caluse = "";

    if(!is_null($event) || $event <> ""){
        if($where_caluse <> ""){
            $where_caluse .= " and ";
        }else{
            $where_caluse .= " event_log like '%".$event."%' ";
        }
    }
    
    if(!is_null($process) || $process <> ""){
        if($where_caluse <> ""){
            $where_caluse .= " and ";
        }else{
            $where_caluse .= " process_log like '%".$process."%' ";
        }
        
    }

    if ($time_start <> "" || $time_end <> "") {
        if($where_caluse <> ""){
            $where_caluse .= " and  log_datetime > '".$time_start."' and log_datetime < '".$time_end."' ";
        }else{
            $where_caluse .= "  log_datetime > '".$time_start."' and log_datetime < '".$time_end."' ";
        }
    }
    
    if($where_caluse == ""){
        
        $sql_find_log = "select * from log_backup ";
    }else {
        $sql_find_log = "select * from log_backup where ".$where_caluse;
    }
    
    $result = $conn->query($sql_find_log);

    return $result;
}

function format_table($data) {
 
    // Find longest string in each column
    $table_cols = [];
    foreach ($data as $rkey => $row) {
        foreach ($row as $ckey => $cell) {
            $stringlength = strlen($cell);
            if (empty($table_cols[$ckey]) || $table_cols[$ckey] < $stringlength) {
                $table_cols[$ckey] = $stringlength;
            }
        }
    }
     
    // Output table
    $table = '';
    $k = 0;
    $dash ="|";
    foreach ($data as $rkey => $row) {
        $table .= "|";
        foreach ($row as $ckey => $cell){
            $table .= str_pad($cell, $table_cols[$ckey]) . ' | ';
            for($j=0;$j<=$table_cols[$ckey];$j++){
                $dash .= "-";
            }
            if($k == 0){
           
                $dash .= "| ";
            }
        }
        if($k == 0){
           
            $table .= PHP_EOL.$dash."";
        }
        $table .= PHP_EOL;
        $k++;
        
    }
    return $table;
     
}

function display_log($data){
    $i = 1;
    $array_data[0] = array("ID", "Event_detail", "Process", "Date-time");
    
    while($row = $data->fetch_row()){
        $array_data[$i] = $row;
        $i++;
    }

    
    echo format_table($array_data);

}
    
?>