<?php
    require_once("Controller/mailer.php");

    function get_partofdepartment(){
        global $mail;
        global $link;
        $part_of_department = array();
        $sql= "SELECT department_id, type_id FROM type_department ORDER BY department_id";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $index = 0;
                    if(array_key_exists($row['department_id'], $part_of_department)) $index = count($part_of_department[$row['department_id']]);
                    $part_of_department[$row['department_id']][$index] = $row['type_id'];
                }
                mysqli_free_result($result);
            }
            return $part_of_department;
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_active_reservations(){
        global $mail;
        global $link;
        $reservated = array();
        $sql = "SELECT date_from, date_to, device_type.device_type_id, device_list.device_id, department_id, status, reservations.reservation_id FROM reservations, devices_of_reservations, device_type, device_list WHERE device_type.device_type_id = device_list.device_type_id AND device_list.device_id = devices_of_reservations.device_id AND devices_of_reservations.reservation_id = reservations.reservation_id AND (reservations.status < 4 OR reservations.status = 5);";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $index = 0;
                    if(exists_and_not_empty($row['department_id'], $reservated)) $index = count($reservated[$row['department_id']]);
                    $reservated[$row['department_id']][$index]['date_from'] = $row['date_from'];
                    $reservated[$row['department_id']][$index]['date_to'] = $row['date_to'];
                    $reservated[$row['department_id']][$index]['device_type_id'] = $row['device_type_id'];
                    $reservated[$row['department_id']][$index]['device_id'] = $row['device_id'];
                    $reservated[$row['department_id']][$index]['status'] = $row['status'];
                    $reservated[$row['department_id']][$index]['id'] = $row['reservation_id'];
                }
                mysqli_free_result($result);
            }
            return $reservated;
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_devicetype2(){
        global $mail;
        global $link;
        $device_type = array();
        $sql = "SELECT * FROM device_list, device_type WHERE blocked = 0 AND max_loan_days != 0 AND device_list.device_type_id=device_type.device_type_id";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $create = 0;
                    if(!(array_key_exists($row['home_department'], $device_type))){
                        $create = 1;
                    }
                    else if(!array_key_exists($row['device_type_id'], $device_type[$row['home_department']])){
                        $create = 1;
                    }
                    if($create){
                        $device_type[$row['home_department']][$row['device_type_id']]['name']=$row['device_type_name'];
                        $device_type[$row['home_department']][$row['device_type_id']]['info']=$row['device_type_info'];
                        $device_type[$row['home_department']][$row['device_type_id']]['img_path']=$row['device_type_img_path'];
                        $device_type[$row['home_department']][$row['device_type_id']]['tooltip']=$row['tooltip'];
                        $device_type[$row['home_department']][$row['device_type_id']]['max_loan_days']=$row['max_loan_days'];
                    }
                }
                mysqli_free_result($result);
                return $device_type;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_devicetype(){
        global $mail;
        global $link;
        $device_type = array();
        $sql = "SELECT DISTINCT device_type_name, device_type_info, device_type_img_path, tooltip, max_loan_days, home_department, device_type_id FROM device_type WHERE max_loan_days != 0";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $create = 0;
                    if(!(array_key_exists($row['home_department'], $device_type))){
                        $create = 1;
                    }
                    else if(!array_key_exists($row['device_type_id'], $device_type[$row['home_department']])){
                        $create = 1;
                    }
                    if($create){
                        $device_type[$row['home_department']][$row['device_type_id']]['name']=$row['device_type_name'];
                        $device_type[$row['home_department']][$row['device_type_id']]['info']=$row['device_type_info'];
                        $device_type[$row['home_department']][$row['device_type_id']]['img_path']=$row['device_type_img_path'];
                        $device_type[$row['home_department']][$row['device_type_id']]['tooltip']=$row['tooltip'];
                        $device_type[$row['home_department']][$row['device_type_id']]['max_loan_days']=$row['max_loan_days'];
                    }
                }
                mysqli_free_result($result);
                return $device_type;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_idbyname(){
        global $mail;
        global $link;
        $id_by_name = array();
        $sql = "SELECT DISTINCT device_type_name, device_type.device_type_id FROM device_list, device_type WHERE blocked = 0 AND device_list.device_type_id=device_type.device_type_id";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $name = preg_replace('/\s+/', '_', $row['device_type_name']);
                    $id_by_name[$name] = $row['device_type_id'];
                }
                mysqli_free_result($result);
                return $id_by_name;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_notblockeddevices(){
        global $mail;
        global $link;
        $not_blocked_devices = array();
        $sql = "SELECT * FROM device_list, device_type WHERE blocked = 0 AND device_list.device_type_id=device_type.device_type_id";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $index = 0;
                    if(array_key_exists($row['device_type_id'], $not_blocked_devices)) $index = count($not_blocked_devices[$row['device_type_id']]);
                    $not_blocked_devices[$row['device_type_id']][$index]=$row['device_id']; 
                }
                mysqli_free_result($result);
                return $not_blocked_devices;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    //Sorting departments alphabetically depending on the language
    function get_departmentnames(){
        global $mail;
        global $link;
        $departments = array();
        $sql= "SELECT * FROM departments ORDER BY department_" . get_language();
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $departments[$row['department_id']]['de'] = $row['department_de'];
                    $departments[$row['department_id']]['en'] = $row['department_en'];
                }
                mysqli_free_result($result);
                return $departments;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_allopeningdays(){
        global $mail;
        global $link;
        $opening_days = array();
        $sql = "SELECT * FROM rent_days ORDER BY dayofweek";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $string = "weekday_long_" . $row['dayofweek'];
                    $opening_days[$row['d_id']][$row['id']]["day"] = translate($string);
                    $opening_days[$row['d_id']][$row['id']]["time"] = $row['time'];
                    $opening_days[$row['d_id']][$row['id']]["dayofweek"] = $row['dayofweek'];
                }
            mysqli_free_result($result);
            return $opening_days;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }
    
    function get_all_orders(){
        global $link;
        global $mail;
        $orders = array();
        $sql = "SELECT device_list.device_tag, device_type_indicator, device_type_name, reservations.reservation_id FROM device_type, device_list, devices_of_reservations, reservations WHERE devices_of_reservations.reservation_id=reservations.reservation_id AND devices_of_reservations.device_id=device_list.device_id AND device_type.device_type_id=device_list.device_type_id ORDER BY reservations.reservation_id, device_type_name, device_list.device_tag";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    if(!array_key_exists($row['reservation_id'], $orders)){
                        $orders[$row['reservation_id']][0] = $row['device_type_indicator'] . $row['device_tag'];
                        $orders[$row['reservation_id']][1] = $row['device_type_name'];
                        $orders[$row['reservation_id']][2] = $row['device_tag'];
                        $orders[$row['reservation_id']][3] = $row['device_type_indicator'];
                    }
                    else{
                        $orders[$row['reservation_id']][0] .= "|". $row['device_type_indicator'] . $row['device_tag'];
                        $orders[$row['reservation_id']][1] .= "|". $row['device_type_name'];
                        $orders[$row['reservation_id']][2] .= "|". $row['device_tag'];
                        $orders[$row['reservation_id']][3] .= "|". $row['device_type_indicator'];
                    }
                }
            }
            mysqli_free_result($result);
            return $orders;
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_all_orders_from_department($department_id){
        global $mail;
        global $link;
        $orders = array();
        $sql = "SELECT device_list.device_tag, device_type_indicator, device_type_name, reservations.reservation_id FROM device_type, device_list, devices_of_reservations, reservations WHERE devices_of_reservations.reservation_id=reservations.reservation_id AND devices_of_reservations.device_id=device_list.device_id AND device_type.device_type_id=device_list.device_type_id AND reservations.department_id=" . $department_id . " ORDER BY reservations.reservation_id, device_type_name, device_list.device_tag";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    if(!array_key_exists($row['reservation_id'], $orders)){
                        $orders[$row['reservation_id']][0] = $row['device_type_indicator'] . $row['device_tag'];
                        $orders[$row['reservation_id']][1] = $row['device_type_name'];
                        $orders[$row['reservation_id']][2] = $row['device_tag'];
                        $orders[$row['reservation_id']][3] = $row['device_type_indicator'];
                    }
                    else{
                        $orders[$row['reservation_id']][0] .= "|". $row['device_type_indicator'] . $row['device_tag'];
                        $orders[$row['reservation_id']][1] .= "|". $row['device_type_name'];
                        $orders[$row['reservation_id']][2] .= "|". $row['device_tag'];
                        $orders[$row['reservation_id']][3] .= "|". $row['device_type_indicator'];
                    }
                }
            }
            mysqli_free_result($result);
            return $orders;
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_all_admins(){
        global $mail;
        global $link;
        $admins = array();
        $sql= "SELECT DISTINCT id, fn, ln FROM admins, user WHERE user.id=admins.u_id AND admins.department!=-1";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $admins[$row['id']]['fn'] = $row['fn'];
                    $admins[$row['id']]['ln'] = $row['ln'];
                }
                mysqli_free_result($result);
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }

        for ($i=0; $i < count($admins); $i++) {
            $sql= "SELECT department FROM admins WHERE u_id=" . array_keys($admins)[$i];
            if($result = mysqli_query($link, $sql)){
                if(mysqli_num_rows($result) > 0){
                    while($row = mysqli_fetch_array($result)){
                        $index = 0;
                        if(array_key_exists('departments', $admins[array_keys($admins)[$i]])) $index = count($admins[array_keys($admins)[$i]]['departments']);
                        $admins[array_keys($admins)[$i]]['departments'][$index] = $row['department'];
                    }
                    mysqli_free_result($result);
                }
            }
            else{
                error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
            }
        }
        return $admins;
    }

    function get_all_user(){
        global $mail;
        global $link;
        $user = array();
        $sql= "SELECT * FROM user ORDER BY ln";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $user[$row['id']]['fn'] = $row['fn'];
                    $user[$row['id']]['ln'] = $row['ln'];
                    $user[$row['id']]['email'] = $row['email'];                    
                }
                mysqli_free_result($result);
                return $user;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_blocked_devices(){
        global $mail;
        global $link;
        $block = array();
        $sql= "SELECT * FROM blocked ORDER BY id";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $block[$row['id']] = $row['reason'];
                }
                mysqli_free_result($result);
                return $block;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_all_device_ids(){
        global $mail;
        global $link;
        $ids = array();
        $sql= "SELECT device_type_id, device_tag FROM device_list";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $index = 0;
                    if(array_key_exists($row['device_type_id'], $ids)) $index = count($ids[$row['device_type_id']]);
                    $ids[$row['device_type_id']][$index] = $row['device_tag'];
                }
                mysqli_free_result($result);
                return $ids;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_type_info(){
        global $mail;
        global $link;
        $type = array();
        $sql = "SELECT device_type_id, device_type_name, device_type_indicator, home_department FROM device_type";
        if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
                while($row = mysqli_fetch_array($result)){
                    $type[$row['device_type_id']]['name'] = $row['device_type_name'];
                    $type[$row['device_type_id']]['indicator'] = $row['device_type_indicator'];
                    $type[$row['device_type_id']]['home_department'] = $row['home_department'];
                }
                mysqli_free_result($result);
                return $type;
            }
        }
        else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
        }
    }

    function get_departments() {
        global $mail;
        global $link;
        $departments = array();
        $sql = "SELECT mail, department_de, department_en, department_id, room FROM departments ORDER BY department_de"; //department with pickupday
        if ($result = mysqli_query($link, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $departments[$row['department_id']]['mail'] = $row['mail'];
                    $departments[$row['department_id']]['de'] = $row['department_de'];
                    $departments[$row['department_id']]['en'] = $row['department_en'];
                    $departments[$row['department_id']]['room'] = $row['room'];
                }
                mysqli_free_result($result);
                return $departments;
            }
        } else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
    }

    function get_department_with_rentdays(){
        global $mail;
        global $link;
        $department_rentable = array();
        $sql = "SELECT DISTINCT d_id FROM rent_days";
        if ($result = mysqli_query($link, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $department_rentable[$row['d_id']] = 1;
                }
                mysqli_free_result($result);
                return $department_rentable;
            }
        } else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
    }

    function get_limits_of($table){
        global $link;
        global $mail;
        $limits = array();
        $sql = "SELECT * FROM " . $table . " LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
              for ($i = 1; $i < count($row); $i += 2) {
                $sql2 = "DESCRIBE " . $table . " " . array_keys($row)[$i];
                if ($result2 = mysqli_query($link, $sql2)) {
                  while ($row2 = mysqli_fetch_array($result2)) {
                    if ($row2['Type'] !== '' && strpos($row2['Type'], 'varchar') !== false) {
                      $teil = explode("varchar(", $row2['Type']);
                      $limits_ = substr($teil[1], 0, -1);
                      $limits[array_keys($row)[$i]] = $limits_;
                    }
                    else if($row2['Type'] !== '' && strpos($row2['Type'], 'int') !== false){
                      $teil = explode("int(", $row2['Type']);
                      $limits_ = substr($teil[1], 0, -1);
                      $limits[array_keys($row)[$i]] = $limits_;
                    }
                  }
                } else {
                  error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
                  return NULL;
                }
              }
              break;
            }
            mysqli_free_result($result);
            return $limits;
          }
        } else {
          error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
          return NULL;
        }
      }
      
      function get_admin_department($username)
      {
        global $mail;
        global $link;
        if (!is_null($username) && !is_null($link)) {
          $sql = "SELECT admins.department FROM admins, user WHERE admins.u_id=user.id AND user.username='" . $username . "'";
          if($result = mysqli_query($link, $sql)){
            if(mysqli_num_rows($result) > 0){
              $departments = array();
              while($row = mysqli_fetch_array($result)){
                $departments[count($departments)] = $row['department'];
              }
              mysqli_free_result($result);
              return $departments;
            }
          }
          else{
            error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
          }
        }
        return NULL;
      }
      
      function is_admin($username)
      { // check if the user is an admin
        global $link;
        if (!is_null($username) && !is_null($link)) {
          $query = mysqli_query($link, "SELECT admins.department FROM admins, user WHERE admins.u_id=user.id AND user.username='" . $username . "'");
          $row = mysqli_fetch_array($query);
          if (isset($row['department']) && $row['department'] != -1) {
            return true;
          }
        }
        if(
            $_SERVER["REQUEST_URI"] != "/edurent/" &&
            $_SERVER["REQUEST_URI"] != "/edurent/style-css/accessability.css" &&
            $_SERVER["REQUEST_URI"] != "/edurent/index.php"
        ){
            save_in_logs("WARNING: " . $username . " tried to log in as admin at: " . $_SERVER["REQUEST_URI"], "Server", "", false);            
        }
        return false;
      }
      
      function is_admin_of_department($username, $department)
      { // check if the user is an admin
        global $link;
        if (!is_null($username) && !is_null($link)) {
          $query = mysqli_query($link, "SELECT admins.department FROM admins, user WHERE admins.u_id=user.id AND user.username='" . $username . "' AND admins.department=" . $department);
          $row = mysqli_fetch_array($query);
          if (isset($row['department']) && $row['department'] != -1) {
            return true;
          }
        }
        if(
            $_SERVER["REQUEST_URI"] != "/edurent/" &&
            $_SERVER["REQUEST_URI"] != "/edurent/style-css/accessability.css" &&
            $_SERVER["REQUEST_URI"] != "/edurent/index"
        ){
            save_in_logs("WARNING: " . $username . " tried to log in as admin at: " . $_SERVER["REQUEST_URI"], "Server", "", false);            
        }
        return false;
      }