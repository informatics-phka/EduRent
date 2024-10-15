<?php
require_once("Controller/mailer.php");

function show_myreservations($user_username, $lang)
{
    global $link;
    global $mail;
    //get userid
    $sql = "SELECT id FROM user WHERE username='$user_username'";
    if ($result = mysqli_query($link, $sql)) {
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $u_id = $row['id'];

            //get orders from user
            $orders = array();
            $sql2 = "SELECT device_list.device_tag, device_type_name, device_type_indicator, reservations.reservation_id FROM device_type, device_list, devices_of_reservations, reservations WHERE devices_of_reservations.reservation_id=reservations.reservation_id AND devices_of_reservations.device_id=device_list.device_id AND device_type.device_type_id=device_list.device_type_id ORDER BY reservations.reservation_id";
            if ($result2 = mysqli_query($link, $sql2)) {
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_array($result)) {
                        if ($orders[$row['reservation_id']][0] == "") {
                            $orders[$row['reservation_id']][0] = $row['device_type_indicator'] . $row['device_tag'];
                            $orders[$row['reservation_id']][1] = $row['device_type_name'];
                        } else {
                            $orders[$row['reservation_id']][0] = $orders[$row['reservation_id']][0] . "|" . $row['device_type_indicator'] . $row['device_tag'];
                            $orders[$row['reservation_id']][1] = $orders[$row['reservation_id']][1] . "|" . $row['device_type_name'];
                        }
                    }
                }
                mysqli_free_result($result2);

                //Display tableheader
                $sql3 = "SELECT reservation_id, status, date_from, date_to, time_from, time_to, mail, department_de, room_from, room_to FROM reservations, departments WHERE reservations.department_id=departments.department_id AND status<4 AND user_id=" . $u_id . " ORDER BY reservation_id";
                if ($result3 = mysqli_query($link, $sql3)) {
                    if (mysqli_num_rows($result3) > 0) { ?>
                        <hr>
                        <h3 style="text-align:center;"><?php echo translate('text_myReservations'); ?></h3>
                        <br>
                        <div class='table-responsive' style="text-align:center;">
                            <table class='table'>
                                <thead>
                                    <tr>
                                        <th class='band'><b><?php echo translate('word_number'); ?></b></th>
                                        <th class='band'><b><?php echo translate('word_status'); ?></b></th>
                                    </tr>
                                </thead>
                                <tbody class='customtable'>
                                    <?php
                                    //Display reservation infos
                                    while ($row = mysqli_fetch_array($result3)) {
                                        $status = "";
                                        $button = "";

                                        switch ($row['status']) {
                                            case 1:
                                                $status = translate('status_1');
                                                $button = "btn-warning";
                                                break;
                                            case 2:
                                                $status = translate('status_2');
                                                $button = "btn-info";
                                                break;
                                            case 3:
                                                $status = translate('status_3');
                                                $button = "btn-success";
                                                break;
                                            default:
                                                $status = translate('status_4');
                                                $button = "btn-danger";
                                                break;
                                        }

                                        //if anfrage überfällig
                                        $current = date("Y-m-d");
                                        $frist = $row['date_from'];
                                        $date1 = date_create($current);
                                        $date2 = date_create($frist);
                                        $diff = date_diff($date1, $date2);
                                        $diff = $diff->format("%R%a");
                                        if ($row['status'] == 1 && $diff < 0) {
                                            $status = "Anfrage Überfällig";
                                            $button = "btn-danger";
                                        }

                                        //if abholbar überfällig
                                        if ($row['status'] == 2 && $diff < 0) {
                                            $status = "Abholung Überfällig";
                                            $button = "btn-danger";
                                        }

                                        //if rückgabe überfällig
                                        $frist = $row['date_to'];
                                        $date2 = date_create($frist);
                                        $diff = date_diff($date1, $date2);
                                        $diff = $diff->format("%R%a");
                                        if ($row['status'] == 3 && $diff < 0) {
                                            $status = "Rückgabe Überfällig";
                                            $button = "btn-danger";
                                        }
                                    ?>
                                        <tr>
                                            <td style="vertical-align: middle"> <button type="button" class="rounded btn mr-1 mb-1 <?php echo $button; ?>" onclick="load_order(<?php echo $row['reservation_id']; ?>,'<?php echo date_format(date_create($row['date_from']), 'd.m.Y'); ?>','<?php echo date_format(date_create($row['date_to']), 'd.m.Y'); ?>','<?php echo $row['status']; ?>', '<?php echo $row['time_from']; ?>', '<?php echo $row['time_to']; ?>', '<?php echo $row['mail']; ?>', '<?php echo $row['department_de']; ?>', '<?php echo $row['room_from']; ?>', '<?php echo $row['room_to']; ?>')">#<?php echo $row['reservation_id']; ?></button></td>
                                        <?php
                                        echo "<td style='vertical-align: middle'>" . $status . "</td>";
                                        echo "</tr>";
                                    } ?>
                                </tbody>
                            </table>
                        </div>
<?php
                        mysqli_free_result($result3);
                    }
                } else {
                    error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql3 . ": " . mysqli_error($link));
                }
            } else {
                error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql2 . ": " . mysqli_error($link));
            }
        }
        mysqli_free_result($result);
    } else {
        error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
    }
}

function show_adminbutton($user_username)
{
    if (is_admin($user_username)) {
        
        echo "<br>";
        echo "<div class='row justify-content-center'>";
            echo "<div class='col-md-6 mb-3'>";
                echo "<a class='btn btn-outline-dark btn-block' href='admini'><i class='fas fa-user-cog mr-2'></i>" . translate("text_adminPage") . "</a>";
            echo "</div>";
		echo "</div>";
    }
}

function available_devices($reservated, $department, $not_blocked_devices, $date_from, $date_to)
{
    if ($reservated[$department]) {
        $all_reservations = array_keys($reservated[$department]);
        for ($p = 0; $p < count($all_reservations); $p++) {
            //in timespan or not returned

            //if rückgabe überfällig
            $Difference_In_Days = date_diff(new DateTime("now"), date_create($reservated[$department][$all_reservations[$p]]['date_to']))->format("%R%a");
            if (
                $date_from >= $reservated[$department][$all_reservations[$p]]['date_from'] && $date_from <= $reservated[$department][$all_reservations[$p]]['date_to'] || //from in timespam
                $date_to >= $reservated[$department][$all_reservations[$p]]['date_from'] && $date_to <= $reservated[$department][$all_reservations[$p]]['date_to'] || //to in timespam
                $reservated[$department][$all_reservations[$p]]['status'] == 3 && $Difference_In_Days < 0
            ) { //overdue
                $device_ids = array_keys($not_blocked_devices[$reservated[$department][$all_reservations[$p]]['device_type_id']]);
                for ($m = 0; $m < count($device_ids); $m++) {
                    if ($not_blocked_devices[$reservated[$department][$all_reservations[$p]]['device_type_id']][$device_ids[$m]] == $reservated[$department][$all_reservations[$p]]['device_id']) {
                        unset($not_blocked_devices[$reservated[$department][$all_reservations[$p]]['device_type_id']][$device_ids[$m]]);
                    }
                }
            }
        }
    }
    else{
        save_in_logs("INFO: no reservated devices");
    }
    return $not_blocked_devices;
}

function get_user_id($user_username, $mail, $lang)
{
    global $SESSION;
    global $link;
    $sql = mysqli_query($link, "SELECT id FROM user WHERE username='$user_username'");
    if (mysqli_num_rows($sql) > 0) {
        $row = mysqli_fetch_array($sql);
        $user_id = $row['id'];
        return $user_id;
    } else {
        error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not find user " . $user_username);
        $SESSION->toasttext = translate('text_error') . " " . $mail;
        return -1;
    }
}

?>