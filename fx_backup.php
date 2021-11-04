<?php

////------------------------------------////
////           start function           ////
////------------------------------------////

function create_routine($conn_backup){
    
    $sql_create_routine = "insert into routine_backup (routine_datetime, routine_note) values ( now(), '');";
    
    $conn_backup->query($sql_create_routine);
    
}

function get_routine_id($conn_backup){
    $sql_get_routine_id = "select id from routine_backup order by id desc limit 1";
    
    $result = $conn_backup->query($sql_get_routine_id);

    if ($result !== false) {
        $routine_data = $result->fetch_assoc();
    }else{
        $routine_id["id"] = "error";
    }
    return $routine_data["id"];
}

function get_good_bad_user($conn_working, $good){
        
    $sql_get_users = "select * from permanent_users";
    $result1 = $conn_working->query($sql_get_users);
    $good_userlist[] = "";
    $bad_userlist[] = "";
    while ($row1 = $result1->fetch_assoc()) {
        $sql_check_good = "select * from radcheck where username = '".$row1["username"]."' and attribute = 'Cleartext-Password' ";
        $result_check = $conn_working->query($sql_check_good);
        if ($result_check->num_rows > 0) {
            $good_userlist[] = $row1["username"];
        }else{
            $bad_userlist[] = $row1["username"];
        }
    }

    if ($good == 1) {
        return $good_userlist;
    }else{
        return $bad_userlist;
    }

}

function check_pass_on_radcheck($conn_working , $conn_backup){
    $sql_get_permanent_user = "select username from permanent_users" ;
    $result = $conn_working->query($sql_get_permanent_user);
    
    if (!$result) {
        $result_countrow = 0;
    }else{
        $result_countrow = $result->num_rows;
    }
    
    $i = 0;
    $user_check_permanent_users[0][0] = "";
    
    if ($result_countrow > 0) {
        
        while($row = $result->fetch_assoc()) {
            $user_check_permanent_users[$i][0] = $row["username"];
            $user_check_permanent_users[$i][1] = get_clearpass($conn_working, $row["username"]);
            $i++;
        }
        return $user_check_permanent_users;
    }else{
        return "error";
    }
    
}

function get_clearpass($conn_working, $username){
    $sql_get_clearpass = "select * from radcheck where username = '".$username."' and attribute = 'Cleartext-Password' ";
    $result = $conn_working->query($sql_get_clearpass);
    if (!$result) {
        $result_countrow = 0;
    }else{
        $result_countrow = $result->num_rows;
    }
    if($result_countrow == 1){
        return 1;
    }else{
        return 0;
    }
}

function get_table_data($conn_working, $data, $table){
    
    $i=0;
    $k=0;
    
    for($i=0;$i<count($data);$i++){
        
        if($data[$i][1] == 1){
            $sql_read_table_data = "select * from ".$table." where username = '".$data[$i][0]."'";
           
            $row_1[$i] = $conn_working->query($sql_read_table_data);
            
            if($row_1[$i]->num_rows > 0){
                while($rw = $row_1[$i]->fetch_assoc()){
                
                    $result[$k] = $rw;
                    $k++;
                }
            }
        }

    }
    if(isset($result)){
        return $result;
    }else{
        return "error";
    }
    
}

function record_table_data($conn_working, $db_working, $conn_backup, $data1, $table_working, $table_backup, $routine_id){
    
    if($data1 <> "error"){
        $i=0;
        $check_null = check_fields_null($conn_working , $db_working, $table_working );
        
        for($i=0;$i<count($data1);$i++){
        
            $data = $data1[$i];
                
            $sql_record_table_data = make_sql_phase_insert($data, $check_null, $routine_id, $table_backup);
        
            $result = $conn_backup->query($sql_record_table_data);
        
        }
    }
    
}

function check_fields_null($conn , $db, $table ){
    
    $i = 0;
    $sql = "select column_name, is_nullable from information_schema.columns where table_schema = '".$db."' and table_name = '".$table."';";
    
    $result1 = $conn->query($sql);
    
    while($row = $result1->fetch_assoc()) {
        
        $result2[$i][0] = $row["column_name"];
        $result2[$i][1] = $row["is_nullable"];
        $i++;

    }
    return $result2;
}

function make_sql_phase_insert($source, $check_null, $routine_id, $table_backup){
    
    $sql_start_with =  " insert into ".$table_backup." ";
    
    $column_list = " ( ";
       
    for($i=1;$i<count($check_null)-1;$i++){
        $column_list .= $check_null[$i][0];
        $column_list .= ", ";
    }

    $column_list .= $check_null[count($check_null)-1][0];
    $column_list .= ", record_date, routine_id )";

    $values_list = " ( ";
    
    for($j=1;$j<count($check_null)-1;$j++){
        
        if($check_null[$j][1] == "YES" && $source[($check_null[$j][0])] == ""){
            $values_list .= "NULL";
            $values_list .= ", ";
        }else{
            $values_list .= "'".$source[($check_null[$j][0])]."'";
            $values_list .= ", ";
        }
        
    }

    $values_list .= "'".$source[($check_null[count($check_null)-1][0])]."'";
    $values_list .= ", now(), ".$routine_id." )";
    
    return $sql_start_with.$column_list." values ".$values_list;

}

function get_latest_id($conn, $table){
    
    $sql_get_last_id = "select id from ".$table." order by id desc limit 1";
    
    $result = $conn->query($sql_get_last_id);

    if ($result !== false) {
        $last_id_data = $result->fetch_assoc();
        
        if (isset($last_id_data["id"])) {
            if(!is_null($last_id_data["id"]))
            {
                return $last_id_data["id"];
            }else{
                return "0";
            }
        }else {
            return "0";
        }

        

    }else{
        return "error";
    }
    

}

function get_count_record($conn, $table) {
    
    $sql_get_count = "select id from ".$table." ";

    $result = $conn->query($sql_get_count);

    return $result->num_rows;
}

function get_count_user_record($conn, $username, $table){
    $sql_get_count = "select id from ".$table." where username = '".$username."' ";
    $result = $conn->query($sql_get_count);
    if(!$result){
        return 0;
    }else{
        return $result->num_rows;
    }
    
}

?>