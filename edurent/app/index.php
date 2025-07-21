<!DOCTYPE HTML>
<?php
    //get data
    $device_type = get_devicetype2();
    $id_by_name = get_idbyname();
    $part_of_department = get_partofdepartment();
    $pickupdays = get_allopeningdays();
    $departments = get_departments();
    $department_rentable = get_department_with_rentdays();

    //session unset from request before
    $all_types = array_keys($device_type);
    for ($i = 0; $i < count($all_types); $i++) {
        $name = "type_" . $all_types[$i];
        unset($_SESSION[$name]);
    }

    $all_departments = array_keys($departments);
    for ($i = 0; $i < count($all_departments); $i++) {
        $name = "department_" . $all_departments[$i];
        unset($_SESSION[$name]);
    }

    unset($_SESSION['date_to']);
    unset($_SESSION['date_from']);
    unset($_SESSION['selected_department']);
    // ----------------------------------

    // REMOVE ME AFTER INITIAL SETUP
    $check_query = "SELECT COUNT(*) as count FROM user";
    if ($result = mysqli_query($link, $check_query)) {
        $row = mysqli_fetch_assoc($result);

        if ($row['count'] == 0) {
            // crete user
            $query = "INSERT IGNORE INTO user (username, fn, ln, email) VALUES (?,?,?,?)";
            if ($stmt = mysqli_prepare($link, $query)) {
                mysqli_stmt_bind_param($stmt, "ssss", $user_username, $user_firstname, $user_lastname, $user_email);

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("ERROR: Could not able to execute: " . $query . ": " . mysqli_error($link));
                }
            } else {
                throw new Exception("ERROR: Could not prepare statement. " . mysqli_error($link));
            }
            save_in_logs("Erster Benutzer erfolgreich erstellt.");

            $user_id = get_user_id($user_username, $admin_mail, $lang);

            //set user as admin
            $query = "INSERT INTO admins (u_id, department) VALUES (?, 0)";
            if ($stmt = mysqli_prepare($link, $query)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);

                if (!mysqli_stmt_execute($stmt)) {
                    save_in_logs("ERROR: " . mysqli_error($link));
                    save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
                }
            } else {
                save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
            }
            $stmt->close();
            save_in_logs("Erster Benutzer als Admin erfolgreich erstellt.");
        }
    } else {
        throw new Exception("ERROR: Could not able to execute: " . $check_query . ": " . mysqli_error($link));
    }
    //REMOVE ME AFTER INITIAL SETUP


    /* reservation gets storned */
    if(exists_and_not_empty('ret', $_GET)){
        try {
            //get mail from admin
            $admin_mail;
            $sql = "SELECT user.email FROM admins, user, reservations WHERE reservations.department_id=admins.department AND admins.u_id=user.id AND reservation_id=" . $_GET['ret'];
            if ($result = mysqli_query($link, $sql)) {
                $row = mysqli_fetch_array($result);
                $admin_mail = $row['email'];
            }
            else throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));

            /* get user_id */
            $user_id = get_user_id($user_username, $admin_mail, $lang);
            if ($user_id == -1) {
                throw new Exception("Found no User");
            }

            $query = "UPDATE reservations SET status=6 WHERE user_id = ? AND reservation_id = ?";
            if ($stmt = mysqli_prepare($link, $query)) {
                mysqli_stmt_bind_param($stmt, "ii", $user_id, $_GET['ret']);

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
                } else {
                    //email to user 
                    $messagetext = translate('text_mailCanceled', ['a' => $_GET['ret']]) . "</br></br>" . translate('text_regards');
                    sendamail($mail, $user_email, translate('text_myston') . $_GET['ret'], $messagetext);

                    //email to admin
                    $messagetext = "Die Reservierungsanfrage mit der ID #" . $_GET['ret'] . " wurde von " . $user_firstname . " " . $user_lastname . " storniert.";
                    if ($_GET['rtext'] != "") {
                        $messagetext .= "<br /><br />Stornierungsnachricht: </br>" . $_GET['rtext'];
                    }
                    sendamail($mail, $admin_mail, translate('word_request') . " #" . $_GET['ret'] . " " . translate('text_canceled'), $messagetext);

                    save_in_logs("INFO: Reservierungsanfrage #" . $_GET['ret'] . " wurde vom Benutzer torniert.", $user_firstname, $user_lastname, false);
                    $SESSION->toasttext = translate('word_request') . " #" . $_GET['ret'] . translate('text_canceled');
                }
            } else {
                throw new Exception("ERROR: Could not prepare statement. " . mysqli_error($link));
            }
            $stmt->close();

            echo "<script>window.location.href = 'index';</script>";
        } 
        catch (exception $e) {
            error_to_superadmin(get_superadmins(), $mail, "ERROR: in 76 index: " . $e->getMessage());		
            $SESSION->toasttext = translate('text_error') . " " . $departments[$department]['mail'];	
        }
        echo "<script>window.location.href='index';</script>";
        exit();
    }

    //text to admin  
    if(exists_and_not_empty('t_id', $_GET)){
        $user_email;
        $admin_mail;
        $sql = "SELECT DISTINCT email, mail FROM user, reservations, departments WHERE user.id=user_id AND reservations.department_id=departments.department_id AND reservation_id=" . $_GET['t_id'];
        if ($result = mysqli_query($link, $sql)) {
            $row = mysqli_fetch_array($result);
            $admin_mail = $row['mail'];
            $user_email = $row['email'];
        }

        //email to user
        $messagetext = "Sie haben folgende Nachricht an das Team von Edurent, bezüglich Ihrer Reservierung mit der ID #" . $_GET['t_id'] . ", gesenden:</br></br>" . $_GET['atext'] . "<br/><br/>Unter der Mailadresse " . $admin_mail . " können Sie das Team auch weiterhin erreichen.";
        sendamail($mail, $user_email, "Edurent - Nachricht gesendet", $messagetext);

        //email to admin
        $messagetext = "Sie haben folgende Nachricht bezüglich der Reservierung mit der ID #" . $_GET['t_id'] . " erhalten:<br/><br/>" . $_GET['atext'] . "<br/><br/>Unter der Mailadresse " . $user_email . " können Sie den Nutzer erreichen.";
        sendamail($mail, $admin_mail, "Edurent - Nachricht erhalten ID #" . $_GET['t_id'], $messagetext);
        echo "<script>window.location.href='index';</script>";
    }

    $orders = get_all_orders();
?>

<head>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Tooltip -->
    <script type="text/javascript" src="js/tooltip.js"></script>
    
    <!-- Array controll -->
    <script type="text/javascript" src="js/arraycontroll.js"></script>
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="style-css/rent.css">
    <link rel="stylesheet" href="style-css/toasty.css">
    <link rel="stylesheet" href="style-css/page_colors.scss">
    <link rel="stylesheet" href="style-css/table.scss">
    <link rel="stylesheet" href="style-css/accessability.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    
    <!-- Toast -->
    <?php 
        require_once("Controller/toast.php"); 
    ?>
</head>
<body>
    <?php
        //Runs when the reservation process is completed

        if(exists_and_not_empty('reservation', $_POST)){
            $all_departments = array_keys($departments);
            for ($i = 0; $i < count($all_departments); $i++) {
                $name = "date_from_" . $all_departments[$i];
                if (!$_POST[$name]) continue; //date found, create reservation for that department

                $date_from = $_POST[$name];
                $date_from = date('Y-m-d', strtotime($date_from));

                $name = "date_to_" . $all_departments[$i];
                $date_to = $_POST[$name];
                $date_to = date('Y-m-d', strtotime($date_to));

                //get times
                $abholbar_timestamp = strtotime($date_from);
                $abholbar_wochentag = date("N", $abholbar_timestamp) % 7; //7 = 0
                $abholbar_uhrzeit;

                $days = array_keys($pickupdays[$all_departments[$i]]);
                for ($o = 0; $o < count($days); $o++) {
                    if ($abholbar_wochentag == $pickupdays[$all_departments[$i]][$days[$o]]['dayofweek']) {
                        $abholbar_uhrzeit = $pickupdays[$all_departments[$i]][$days[$o]]['time'];
                        break;
                    }
                }

                $rueckgabe_timestamp = strtotime($date_to);
                $rueckgabe_wochentag = date("N", $rueckgabe_timestamp) % 7; //7 = 0
                $rueckgabe_uhrzeit;

                $days = array_keys($pickupdays[$all_departments[$i]]);
                for ($o = 0; $o < count($days); $o++) {
                    if ($rueckgabe_wochentag == $pickupdays[$all_departments[$i]][$days[$o]]['dayofweek']) {
                        $rueckgabe_uhrzeit = $pickupdays[$all_departments[$i]][$days[$o]]['time'];
                        break;
                    }
                }

                $department = $all_departments[$i];

                $room_from = $departments[$department]['room'];
                $room_to = $room_from;

                $reservation_id;

                try {
                    //check for user
                    $query = "INSERT IGNORE INTO user (username, fn, ln, email) VALUES (?,?,?,?)";
                    if ($stmt = mysqli_prepare($link, $query)) {
                        mysqli_stmt_bind_param($stmt, "ssss", $user_username, $user_firstname, $user_lastname, $user_email);

                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
                        }
                    } else {
                        throw new Exception("ERROR: Could not prepare statement. " . mysqli_error($link));
                    }
                    $stmt->close();

                    //get user id
                    $user_id = get_user_id($user_username, $departments[$department]['mail'], $lang);
                    if ($user_id == -1) {
                        error_to_superadmin(get_superadmins(), $mail, "Found no User");
                        break;
                    }

                    //get a reservation id
                    $sql = "SELECT MAX(reservation_id) FROM reservations";
                    if ($result = mysqli_query($link, $sql)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_array($result);
                            $reservation_id = $row['MAX(reservation_id)'] + 1;
                        }
                        mysqli_free_result($result);
                    } else {
                        throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
                    }

                    //create reservation
                    $query = "INSERT INTO reservations (reservation_id, user_id, date_from, date_to, time_from, time_to, department_id, room_from, room_to) VALUES (?,?,?,?,?,?,?,?,?)";
                    if ($stmt = mysqli_prepare($link, $query)) {
                        mysqli_stmt_bind_param($stmt, "iissssiss", $reservation_id, $user_id, $date_from, $date_to, $abholbar_uhrzeit, $rueckgabe_uhrzeit, $department, $room_from, $room_to);

                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
                        }
                    } else {
                        throw new Exception("ERROR: Could not prepare statement. " . mysqli_error($link));
                    }
                    $stmt->close();

                    $all_devices = array_keys($device_type[$department]);
                    for ($o = 0; $o < count($all_devices); $o++) {
                        $name = $device_type[$department][$all_devices[$o]]['name'];
                        $name = preg_replace('/[\s\.]+/', '_', $name);
                        if (exists_and_not_empty($name, $_POST)) {
                            $amount = $_POST[$name];
                            
                            $name .= "_ids";
                            $not_blocked_devices = $_POST[$name];
                            $devices = explode(",", $not_blocked_devices);
   
                            for ($n = 0; $n < $amount; $n++) {
                                $query = "INSERT INTO devices_of_reservations (reservation_id, device_id) VALUES (?,?)";
                                if ($stmt = mysqli_prepare($link, $query)) {
                                    mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $devices[$n]);
                                    if (!mysqli_stmt_execute($stmt)) throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
                                } else throw new Exception("ERROR: Could not prepare statement. " . mysqli_error($link));
                            }
                        }
                    }

                    //user
                    //echo translate('text_rentMultipleDays', ['a' => $type['max_loan_days']]); 
                    $messagetext = translate('text_resrequest', ['a' => " #".$reservation_id, 'b' => date_format(date_create($date_from), 'd.m.Y'), 'c' => date_format(date_create($date_to), 'd.m.Y')]) . "<br /><br />" . translate('text_questionMail', ['a' => $departments[$department]['mail']]) . "<br /><br />" . translate('text_regards');
                    sendamail($mail, $user_email, translate('word_request') . " #" . $reservation_id, $messagetext);

                    //admin
                    $messagetext = "Es ist eine neue Reservierungsanfrage mit der ID #" . $reservation_id . " eingegangen.<br /><br />Von " . $user_firstname . " " . $user_lastname . "<br />Vom " . date_format(date_create($date_from), 'd.m.Y') . " bis zum " . date_format(date_create($date_to), 'd.m.Y') . "<br /><br />Hier kommst du zum Admin-Panel von Edurent:<br />https://innovationspace.ph-karlsruhe.de/edurent/admini.php";
                    sendamail($mail, $departments[$department]['mail'], "Neue Reservierungsanfrage", $messagetext);

                    save_in_logs("INFO: Reservierungsanfrage #" . $reservation_id . " wurde erstellt.", $user_firstname, $user_lastname, false);
                    $SESSION->toasttext = translate('word_request') . " #" . $reservation_id . translate('word_send');
                } 
                catch (exception $e) {
                    error_to_superadmin(get_superadmins(), $mail, "ERROR: error above 261 index: " . $e->getMessage());
                    $SESSION->toasttext = translate('text_error') . " " . $departments[$department]['mail'];	
                    //set status to buggy
                    $query = "UPDATE reservations SET status=7 WHERE user_id = ? AND reservation_id = ?";
                    if ($stmt = mysqli_prepare($link, $query)) {
                        mysqli_stmt_bind_param($stmt, "ii", $user_id, $reservation_id);

                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
                        }
                    } else {
                        throw new Exception("ERROR: Could not prepare statement. " . mysqli_error($link));
                    }
                    exit();
                }

                $query = "DELETE FROM reservations WHERE reservation_id not in (select distinct reservation_id from devices_of_reservations)";
                if ($stmt = mysqli_prepare($link, $query)) {
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
                    }
                } else {
                    throw new Exception("ERROR: Could not prepare statement. " . mysqli_error($link));
                }
            }
            echo "<script>window.location.href='index';</script>";
            exit();
        }
    ?>    
    <script>
        //select_department onchange
        var device_type = <?php echo is_null($device_type) ? "2" : json_encode($device_type); ?>;
        var part_of_department = <?php echo is_null($part_of_department) ? "2" : json_encode($part_of_department); ?>;
        var departments = <?php echo is_null($departments) ? "2" : json_encode($departments); ?>;
        var department_rentable = <?php echo is_null($department_rentable) ? "2" : json_encode($department_rentable); ?>;
    </script>
    <div class="main">
        <!-- Titel -->
        <h3 class="title" style='text-align:center; width:100%;'> <?php echo translate('text_welcome', ['a' => " <b>$user_firstname $user_lastname</b> "]); ?> </h3>
        <br>
        <br>
            <?php 
            $days_bookable_in_advance_text="";
            $max_loan_duration_text="";
            if($days_bookable_in_advance%30==0){
                $days_bookable_in_advance_text = $days_bookable_in_advance/30;
                if($days_bookable_in_advance_text == 1) $days_bookable_in_advance_text = "1 Monat";
                else $days_bookable_in_advance_text = $days_bookable_in_advance_text . " Monate";
            }
            else{
                $days_bookable_in_advance_text = $days_bookable_in_advance;
                if($days_bookable_in_advance_text == 1) $days_bookable_in_advance_text = "1 Tag";
                else $days_bookable_in_advance_text = $days_bookable_in_advance_text . " Tage";
            }

            if($max_loan_duration%7==0){
                $max_loan_duration_text = $max_loan_duration/7;
                if($max_loan_duration_text == 1) $max_loan_duration_text = "1 Woche";
                else $max_loan_duration_text = $max_loan_duration_text . " Wochen";
            }
            else{
                $max_loan_duration_text = $max_loan_duration;
                if($max_loan_duration_text == 1) $max_loan_duration_text = "1 Tag";
                else $max_loan_duration_text = $max_loan_duration_text . " Tage";
            }
            require "Controller/Rules.php"; 
            new Rules(translate('text_rules_1'),
            translate('text_rules_2', ['a' => $days_bookable_in_advance_text]),
            translate('text_rules_3', ['a' => $max_loan_duration_text]),
            translate('text_rules_4'),
            translate('text_rules_5')); ?>
        <br>
        <form action="index2.php" name="getdepartment" method="post" style="text-align:center; width:100%; margin: 0 auto;" onsubmit="return checkinputs()">

            <?php show_department_select(); ?>
            <br>

            <div id='step2' name='step2' style='display:none;'>
                
                <!-- Device Select -->
                <?php show_device_select($device_type, $department_rentable, $departments); ?>
                <br>

                <!-- Timespan Select -->
                <?php require_once("Controller/Daterange.php"); 
                show_daterangepicker(is_superadmin($user_username));?>

                <div class='row' style='text-align:center;'>
                    <div class='col-9'>
                        <p class='select' style='text-align:left;'><?php echo translate('text_step4')?></p>
                    </div>
                    <div class='col'>
                        <button type="submit" id="submit_button" disabled class="btn btn-primary btn-radius mr-1 mb-1">
                            <?php echo translate('text_buttonNextPage'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php
            show_myreservations($user_username, $lang);
            show_adminbutton($user_username)
        ?>
    </div>
    <script>
        //delete the reservation (userside)
        function order_delete(id) {
            var text = $('#ston_text').val();
            text = text.replace(/\r?\n/g, '<br />');
            location.href = "?ret=" + id + "&rtext=" + text;
        }

        //ask for the reason
        function confirm_delete(id) {
            var mymodal = $('#my_modal');
            var titel = "<?php echo translate('word_reservation'); ?> #" + id;
            mymodal.find('.modal-title').text(titel);

            //buttons
            $("#button_grey").html('<?php echo translate('word_back'); ?>');

            $("#button_green").html('<?php echo translate('word_cancel'); ?>');
            $("#button_green").show();

            $("#button_red").hide();
            $("#button_yellow").hide();

            //body
            var string = "<center><?php echo translate('text_reason'); ?></br><textarea style='overflow: hidden; resize: none;' class='form-control form-rounded' name='ston_text' id='ston_text' rows='4'></textarea></center>";
            mymodal.find('.modal-body').html(string);

            $("#button_green").attr("onclick", "order_delete(" + id + ")");



            mymodal.modal('show');
        }

        function load_order(reservation_id, from, to, status, time_from, time_to, mail, department, room_from, room_to) { //load the selected order
            var mymodal = $('#my_modal');

            //titel
            var titel = "<?php echo translate('word_reservation'); ?> #" + reservation_id;
            mymodal.find('.modal-title').text(titel);

            var orders = <?php echo is_null($orders) ? "2" : json_encode($orders); ?>;

            //buttons
            $("#button_grey").html('<?php echo translate('word_back'); ?>');

            $("#button_green").html('<?php echo translate('word_cancel'); ?>');
            $("#button_green").show();

            $("#button_red").hide();

            $("#button_yellow").html('<?php echo translate('word_message'); ?>');
            $("#button_yellow").attr("onclick", "text_to_admin(" + reservation_id + ")");
            $("#button_yellow").show();

            if (status >= 2) {
                $("#button_green").hide();
            }

            //body
            var string = "";
            if (orders == "2" || orders[reservation_id] == null) {
                string = "<div style='text-align:center;'><?php echo translate('text_error'); ?><br><a href = 'mailto:<?php echo $departments[$all_institutes]['mail']; ?>?subject=Edurent'><?php echo $departments[0]['mail']; ?></a>" + '</div>';
                $("#button_green").hide();
                $("#button_red").hide();
            } else {
                var d_ids = orders[reservation_id][0].split('|');
                var names = orders[reservation_id][1].split('|');
                var names2 = [names[0]];
                var amount = [1];
                for (var i = 1; i < names.length; i++) {
                    if (names2.indexOf(names[i]) > -1) {
                        amount[names2.indexOf(names[i])]++;
                    } else {
                        names2[names2.length] = names[i];
                        amount[amount.length] = 1;
                    }
                }
                var geraete = "<br><?php echo translate('word_devices'); ?>:<br/>";
                for (var i = 0; i < amount.length; i++) {
                    geraete += amount[i] + "x " + names2[i] + "<br>";
                }

                //Reservierungszeitraum
                var time = "<?php echo translate('word_period'); ?>: " + from + " <?php echo translate('word_to'); ?> " + to + "<br><br>";

                var department = "<?php echo translate('word_department'); ?>: " + department + "<br>";

                var status_text = "<?php echo translate('word_status'); ?>: ";
                var translate_pickup = "<?php echo translate('text_pickupReservation'); ?>";
                var translate_return = "<?php echo translate('text_returnReservation'); ?>";
                if (status == 2) {
                    status_text += translate(translate_pickup, [from, time_from, room_from]);
                } else if (status == 3) {
                    status_text += translate(translate_return, [to, time_to, room_to]);
                } else {
                    status_text += "<?php echo translate('text_inProgress'); ?><br>";
                }

                var adminmail = '<br><?php echo translate('text_reachout'); ?><br><a href = "mailto:' + mail + '?subject=Edurent">' + mail + '</a>';
                string += '<div style="text-align:center">' + department + time + status_text + geraete + adminmail + '</div>';
            }

            mymodal.find('.modal-body').html(string);

            //stornierbutton funktion definieren
            $("#button_green").attr("onclick", "confirm_delete(" + reservation_id + ")");

            mymodal.modal('show');
        }


        /* --- Mail to Admin - START --- */
        function send_to_admin(reservation_id) {
            var text = document.getElementById("admin_text").value;
            text = text.replace(/\r?\n/g, '<br />');
            location.href = "?t_id=" + reservation_id + "&atext=" + text;
        }

        function text_to_admin(reservation_id) {
            var mymodal = $('#my_modal');
            var titel = "<?php echo translate('text_createMessage'); ?>";
            mymodal.find('.modal-title').text(titel);

            //buttons
            $("#button_grey").html('<?php echo translate('word_back'); ?>');

            $("#button_red").html('<?php echo translate('word_send'); ?>');
            $("#button_red").show();

            $("#button_green").hide();
            $("#button_yellow").hide();

            //body
            var string = "<center><textarea style='overflow: hidden; resize: none;' class='form-control form-rounded' name='admin_text' id='admin_text' rows='4'></textarea></center>";
            mymodal.find('.modal-body').html(string);

            $("#button_red").attr("onclick", "send_to_admin(" + reservation_id + ")");

            mymodal.modal('show');
        }
        /* --- Mail to Admin - END --- */

        //on submit
        function checkinputs() {
            var keys = Object.keys(device_type);
            for (let key of keys) {
                var keys2 = Object.keys(device_type[key]);
                for (let key2 of keys2) {
                    var type = "type_" + key2;
                    if (document.getElementById(type)) {
                        if (document.getElementById(type).checked) {
                            return true;
                        }
                    }
                }
            }
            document.querySelector('.errormessage').style.display = 'block';
            return false;
        }

        function isset(_var) {
            return !!_var; // converting to boolean.
        }
    </script>
    <?php
    echo $OUTPUT->footer();
    ?>
</body>