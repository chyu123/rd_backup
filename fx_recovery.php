<?php

////------------------------------------////
////           start function           ////
////------------------------------------////

function get_routine_id($conn_backup){
    
    $sql_get_routine_id = "select id from routine_backup order by id desc limit 1";
    
    $result = $conn_backup->query($sql_get_routine_id);

    if ($result !== false) {
        $routine_data = $result->fetch_assoc();
    }
    return $routine_data["id"];
}

function get_user_list($conn_working){
    
    $sql_get_user_list = "select username, password from permanent_users";
    
    $result = $conn_working->query($sql_get_user_list);
    $i=0;
    while ($row = $result->fetch_assoc()) {
        $data[$i][0] = $row["username"];
        $data[$i][1] = $row["password"];
        $i++;
    }

    return $data;

}

function get_user_pass_on_racheck_backup($conn_backup, $user_list, $salt, $routine_id){
    
    for($i=0;$i<count($user_list);$i++){

        $sql_get_password_radcheck_backup = "select * from radcheck_backup where attribute = 'Cleartext-Password' and username = '".$user_list[$i][0]."' and routine_id = ".$routine_id ;
        $result = $conn_backup->query($sql_get_password_radcheck_backup);
        
        if ($result !== false) {
            
            $row = $result->fetch_assoc();
            $hash_password = sha1($salt.$row["value"]); 
            
            if($user_list[$i][1] == $hash_password){
                $data[$i][0] = $row["username"];
                
            }
        }
    }

    return $data;

}


function delete_record($conn_working,$user_list,$table){
    
    for($i=0;$i<count($user_list);$i++){
        $sql_del_exist_record = "delete from ".$table." where username = '".$user_list[$i][0]."' ";
        $conn_working->query($sql_del_exist_record);
    }
}

function recover_record($conn_working,$conn_backup, $user_list, $db_working, $table_working, $db_backup, $table_backup, $routine_id){
    
    $check_null = check_fields_null($conn_working , $db_working, $table_working );
    
    for($i=0;$i<count($user_list);$i++){
        $sql_data_from_backup = make_sql_phase_select($user_list[$i], $check_null, $routine_id, $table_backup);
        
        $row = $conn_backup->query($sql_data_from_backup);
        
        if($row->num_rows > 0){
            while($row2 = $row->fetch_assoc()){
                $data_from_backup[] = $row2;
            }
        }
    }
    
    if(isset($data_from_backup)){
        for($i=0;$i<count($data_from_backup);$i++){
            $sql_insert_data = make_sql_phase_insert($data_from_backup[$i], $check_null, $routine_id, $table_working);
            $conn_working->query($sql_insert_data);
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

function make_sql_phase_insert($source, $check_null, $routine_id, $table_working){
    
    
    $sql_start_with =  " insert into ".$table_working." ";
    
    $column_list = " ( ";
       
    for($i=1;$i<count($check_null)-1;$i++){
        $column_list .= $check_null[$i][0];
        $column_list .= ", ";
    }

    $column_list .= $check_null[count($check_null)-1][0];
    $column_list .= " ) ";


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
    $values_list .= " ) ";
    
    return $sql_start_with.$column_list." values ".$values_list;

}

function make_sql_phase_select($source, $check_null, $routine_id, $table_backup){
    
    $sql_start_with =  " select ";
    $sql_from = " from ".$table_backup." ";;
    $column_list = "";
    for($i=1;$i<count($check_null)-1;$i++){
        $column_list .= $check_null[$i][0];
        $column_list .= ", ";
       
    }

    $column_list .= $check_null[count($check_null)-1][0];
    
    $where_clause = " where routine_id = ".$routine_id." and username ='".$source[0]."' ";
    
    return $sql_start_with.$column_list.$sql_from.$where_clause;

}



?>