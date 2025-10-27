<?php 
    //fixed
    $unassigned_institute = '-1';
    $all_institutes = '0';

    //debug values
    $days_bookable_in_advance = 30*1;
    $lead_time_days = 8;
    $debug = false;

    global $mail;
    global $link;

    //load from mysql
    $sql = "SELECT days_bookable_in_advance, lead_time_days, debug FROM server";
    if($result = mysqli_query($link, $sql)){
        if(mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_array($result)){
                $days_bookable_in_advance = $row["days_bookable_in_advance"];
                $lead_time_days = $row["lead_time_days"];
                $debug = $row["debug"];
            }
            mysqli_free_result($result);
        }
    }
    else{
        error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
    }

    