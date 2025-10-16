<!DOCTYPE HTML>
<?php
//get data
$opening_days = get_allopeningdays();
$reservated = get_active_reservations();
$not_blocked_devices = get_notblockeddevices();

$departments = array();
$sql = "SELECT * FROM departments";
if ($result = mysqli_query($link, $sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $departments[$row['department_id']]['de'] = $row['department_de'];
            $departments[$row['department_id']]['en'] = $row['department_en'];
            $departments[$row['department_id']]['announce1_de'] = $row['announce1_de'];
            $departments[$row['department_id']]['announce1_en'] = $row['announce1_en'];
            $departments[$row['department_id']]['room'] = $row['room'];
        }
        mysqli_free_result($result);
    }
} else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));

$device_type = array();
$sql = "SELECT * FROM device_list, device_type WHERE blocked = 0 AND device_list.device_type_id=device_type.device_type_id;";
if ($result = mysqli_query($link, $sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            if(!(array_key_exists($row['device_type_id'], $device_type))){
                $device_type[$row['device_type_id']]['name'] = $row['device_type_name'];
                $device_type[$row['device_type_id']]['info'] = $row['device_type_info'];
                $device_type[$row['device_type_id']]['img_path'] = $row['device_type_img_path'];
                $device_type[$row['device_type_id']]['tooltip'] = $row['tooltip'];
                $device_type[$row['device_type_id']]['home_department'] = $row['home_department'];
            }
        }
        mysqli_free_result($result);
    }
} else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));

//Get Varibales from post or session
if(exists_and_not_empty('selected_department', $_SESSION)){
    $selected_department = $_SESSION['selected_department'];
}

$ordered_by_department = array();
$selected_departments = array();

$all_types = array_keys($device_type);
$all_departments = array_keys($departments);

//get data from Session
if(not_set('date_to', $_POST)){
    $date_to = (exists_and_not_empty('date_to', $_SESSION)) ? $_SESSION['date_to'] : "2";
    $date_from = (exists_and_not_empty('date_from', $_SESSION)) ? $_SESSION['date_from'] : "2";
    if($date_to == "2" || $date_from == "2"){
        echo "<script>window.location.href = 'index';</script>";
	    exit;
    }

    for ($i = 0; $i < count($all_types); $i++) {
        $name = "type_" . $all_types[$i];
        if(exists_and_not_empty($name, $_SESSION)) {
            $department = $device_type[$all_types[$i]]['home_department'];
            $index = 0;
            if(array_key_exists($department, $ordered_by_department)) $index = count($ordered_by_department[$department]);
            $ordered_by_department[$department][$index] = $all_types[$i];
        }
    }

    for ($i = 0; $i < count($all_departments); $i++) {
        $name = "department_" . $all_departments[$i];
        if(exists_and_not_empty($name, $_SESSION)) {
            $selected_departments[count($selected_departments)] = $all_departments[$i];
        }
    }
} else { //get data from POST
    //session unset
    $all_types = array_keys($device_type);
    for ($i = 0; $i < count($all_types); $i++) {
        $name = "type_" . $all_types[$i];
        unset($_SESSION[$name]);
    }

    $add_departments = array_keys($departments);
    for ($i = 0; $i < count($add_departments); $i++) {
        $name = "department_" . $add_departments[$i];
        unset($_SESSION[$name]);
    }

    $date_to = $_POST['date_to'];
    $date_from = $_POST['date_from'];  

    for ($i = 0; $i < count($all_types); $i++) {
        $name = "type_" . $all_types[$i];
        if ($_POST[$name]) {
            $department = $device_type[$all_types[$i]]['home_department'];

            $index = 0;
            if(array_key_exists($department, $ordered_by_department)) $index = count($ordered_by_department[$department]);
            $ordered_by_department[$department][$index] = $all_types[$i];
            $_SESSION[$name] = $_POST[$name];
        }
    }

    for ($i = 0; $i < count($all_departments); $i++) {
        $name = "department_" . $all_departments[$i];
        if ($_POST[$name]) {
            $selected_departments[count($selected_departments)] = $all_departments[$i];
            $_SESSION[$name] = $_POST[$name];
        }
    }
    $_SESSION['date_to'] = $date_to;
    $_SESSION['date_from'] = $date_from;
    $_SESSION['selected_department'] = $selected_department;
}

function is_opening_days($date, $depart, $opening_days){
    $all_days = array_keys($opening_days[$depart]);
    for ($i = 0; $i < count($all_days); $i++) {
        if ($opening_days[$depart][$all_days[$i]]['dayofweek'] == date('w', strtotime($date))) return true;
    }
    return false;
}

function next_opening_day($date, $depart, $opening_days){
    $date = date('d-m-Y', strtotime("+1 day", strtotime($date)));
    if (!is_opening_days($date, $depart, $opening_days)) return next_opening_day($date, $depart, $opening_days);
    return $date;
}

function before_opening_day($date, $depart, $opening_days){
    $date = date('d-m-Y', strtotime("-1 day", strtotime($date)));
    if (!is_opening_days($date, $depart, $opening_days)) return before_opening_day($date, $depart, $opening_days);
    return $date;
}
?>

<head>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- stylesheet -->
    <link rel="stylesheet" href="style-css/rent.css">
    <link rel="stylesheet" href="style-css/toasty.css">
    <link rel="stylesheet" href="style-css/page_colors.scss">
    <link rel="stylesheet" href="style-css/table.scss">
    <link rel="stylesheet" href="style-css/accessability.css">
    
    <!-- moment -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

    <!-- Tooltip -->
    <script type="text/javascript" src="js/tooltip.js"></script>
    
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <script>
        //send the reservation
        function send_res() {
            document.getElementById('theForm').submit();
        }

        //triggers on change of date_from and gets the amount of devices
        function date_from_change(selected) {
            var name = selected.name.replace("from", "to");
            var department = name.replace(/[\w]+_[\w]+_/, "");
            if (document.getElementById(name).value) {
                var from = selected.value;
                var to = document.getElementById(name).value;
                check_amount(to, from, department);
            }
        }

        //triggers on change of date_to and gets the amount of devices
        function date_to_change(selected) {
            var name = selected.name.replace("to", "from");
            var department = name.replace(/[\w]+_[\w]+_/, "");
            if (document.getElementById(name).value) {
                var to = selected.value;
                var from = document.getElementById(name).value;
                check_amount(to, from, department);
            }
        }

        var device_type = <?php echo is_null($device_type) ? "2" : json_encode($device_type); ?>;

        //-- look here
        function check_amount(to, from, department) {
            var not_blocked_devices = <?php echo is_null($not_blocked_devices) ? "2" : json_encode($not_blocked_devices); ?>;
            var reservated = <?php echo is_null($reservated) ? "2" : json_encode($reservated); ?>;

            from = moment(from, "DD-MM-YYYY").format('YYYY-MM-DD');
            to = moment(to, "DD-MM-YYYY").format('YYYY-MM-DD');
            var today_date = new Date();

            if (reservated[department]) { //if a reservation for that department exists
                var all_reservations = Object.keys(reservated[department]);
                for (p = 0; p < all_reservations.length; p++) { //for every device in that reservation

                    var overdue = new Date(reservated[department][all_reservations[p]]['date_to']);
                    var Difference_In_Time = overdue.getTime() - today_date.getTime();
                    var Difference_In_Days = Math.floor(Difference_In_Time / (1000 * 3600 * 24));
                    

                    //in timespan or not returned
                    if ((from >= reservated[department][all_reservations[p]]['date_from'] && from <= reservated[department][all_reservations[p]]['date_to']) || //from in timespam
                        (to >= reservated[department][all_reservations[p]]['date_from'] && to <= reservated[department][all_reservations[p]]['date_to']) || //to in timespan
                        (reservated[department][all_reservations[p]]['status'] == 3 && Difference_In_Days < 0)
                    ) { //overdue
                        var device_ids = Object.keys(not_blocked_devices[reservated[department][all_reservations[p]]['device_type_id']]);
                        for (m=0; m<device_ids.length; m++) {
                            if (not_blocked_devices[reservated[department][all_reservations[p]]['device_type_id']][device_ids[m]] == reservated[department][all_reservations[p]]['device_id']) {
                                not_blocked_devices[reservated[department][all_reservations[p]]['device_type_id']].splice(device_ids[m], 1);
                            }
                        }
                    }
                }
            }

            //show amount
            var typs = Object.keys(not_blocked_devices);
            for (let type of typs) {
                if (device_type[type]['home_department'] == department) {
                    name = device_type[type]['name'].replace(/[\s\.]+/g, '_');
                    if (document.getElementById(name)) { //if input exists
                        //Set max for input
                        var input = document.getElementById(name);
                        input.disabled = false;
                        input.setAttribute("max", not_blocked_devices[type].length);

                        //set max text
                        var input = name + '_max';
                        document.getElementById(input).innerText = " max. " + not_blocked_devices[type].length;

                        //set ids
                        var input = name + '_ids';
                        document.getElementById(input).value =  not_blocked_devices[type];
                    }
                }
            }
        }
        function checkmax(name){
            var input = document.getElementById(name);
            if(input.value[0] == 0){
                input.value = input.value.substring(1);
            }
            if(isNaN(parseInt(input.value)) || parseInt(input.value) < 0){
                input.value = 0;
            }
            if(parseInt(input.value) > input.max){
                input.value = input.max;
            }
        }

        function show_modal() { //reservation summary
            var not_blocked_devices = <?php echo is_null($not_blocked_devices) ? "2" : json_encode($not_blocked_devices); ?>;
            var mymodal = $('#my_modal');

            mymodal.find('.modal-title').text("<?php echo translate('word_request'); ?>");

            $("#button_grey").html('<?php echo translate('word_back'); ?>');

            $("#button_green").attr("onclick", "send_res()");
            $("#button_green").html('<?php echo translate('text_buttonConfirm'); ?>');
            $("#button_green").removeClass("btn-danger");
            $("#button_green").addClass("btn-success");
            $("#button_green").show();

            $("#button_red").hide();
            $("#button_yellow").hide();

            var content = "";

            var selected_departments = <?php echo is_null($selected_departments) ? "2" : json_encode($selected_departments); ?>;
            var departments = <?php echo is_null($departments) ? "2" : json_encode($departments); ?>;
            var opening_days = <?php echo is_null($opening_days) ? "2" : json_encode($opening_days); ?>;

            var all_departments = Object.keys(selected_departments);
            for (let department of all_departments) { //for all selected departments
                department_id = selected_departments[department];
                var puffer1 = "";
                puffer1 += departments[department_id]['<?php echo get_language(); ?>'] + "<br><br>";

                var name = "date_from_" + selected_departments[department];
                if (!document.getElementById(name)) {
                    continue;
                }
                var date_from = document.getElementById(name).value.replace("-", ".");
                var date_from = date_from.replace("-", ".");

                var dayofweek = moment(date_from, "DD.MM.YYYY").format('d');

                var time_from;

                var keys3 = Object.keys(opening_days[department_id]);
                for (let key3 of keys3) { //search for time
                    if (parseInt(opening_days[department_id][key3]["dayofweek"], 10) == dayofweek) {
                        time_from = opening_days[department_id][key3]["time"];
                        break;
                    }
                }

                var name = "date_to_" + selected_departments[department];
                var date_to = document.getElementById(name).value.replace("-", ".");
                var date_to = date_to.replace("-", ".");

                var dayofweek = moment(date_to, "DD.MM.YYYY").format('d');
                var time_to;

                var keys3 = Object.keys(opening_days[department_id]);
                for (let key3 of keys3) { //search for time
                    if (parseInt(opening_days[department_id][key3]["dayofweek"], 10) == dayofweek) {
                        time_to = opening_days[department_id][key3]["time"];
                        break;
                    }
                }

                puffer1 += "<?php echo translate('word_pickupDay'); ?>: " + date_from + ", <?php echo translate('word_time'); ?>: " + time_from + "<br>";
                puffer1 += "<?php echo translate('word_returnDay'); ?>: " + date_to + ", <?php echo translate('word_time'); ?>: " + time_to + "<br>";
                puffer1 += "<?php echo translate('word_room'); ?>: " + departments[department_id]["room"] + "<br><br>";

                var puffer2 = "";
                var inputs = document.querySelectorAll('input[type="number"]');
                for (var index = 0; index < inputs.length; ++index) {
                    var keys2 = Object.keys(not_blocked_devices);
                    for (let key2 of keys2) {
                        name = device_type[key2]['name'].replace(/[\s\.]+/g, '_');
                        if (name == inputs[index].name && device_type[key2]['home_department'] == selected_departments[department]) {
                            if (document.getElementById(name)) { //if input exists
                                if (inputs[index].value > 0) {
                                    name = name.replace('_', /[\s\.]+/g);
                                    puffer2 += inputs[index].value + "x " + inputs[index].name + "<br>";
                                }
                            }
                        }
                    }
                }
                if (puffer2 != "") {
                    content += puffer1;
                    content += puffer2;
                    content += "<hr><br>";
                } else {
                    document.getElementById("date_from_" + selected_departments[department]).value = "";
                    document.getElementById("date_to_" + selected_departments[department]).value = "";
                }
            }

            if (content == "") {
                content = "<center><?php echo translate('text_noDevicesInTimespan'); ?></center>";
                $("#button_green").hide();
            }

            mymodal.find('.modal-body').html(content);
            mymodal.modal('show');
        }

        function isset(_var) {
            return !!_var; // converting to boolean.
        }
    </script>
    <div class="main">
        <!-- Titel -->
        <h3 style='text-align:center; width:100%;'>
            <?php
            echo translate('text_titleIndex2', ['a' => date_format(date_create($date_from), 'd.m.Y'), 'b' => date_format(date_create($date_to), 'd.m.Y')]);
            
            ?>
        </h3>
        <br>
        <br>
            <?php 
            if (isset($_POST['selected_department'])) {
                $_SESSION['selected_department'] = $_POST['selected_department'];
            }
            if (isset($_SESSION['selected_department'])) {
                $department_id = $_SESSION['selected_department'];

                $query = "SELECT max_loan_duration FROM department_settings WHERE department_id = ?";
                if ($stmt = mysqli_prepare($link, $query)) {
                    mysqli_stmt_bind_param($stmt, "s", $department_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $max_loan_duration_department);
                    mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);
                }    
            }                           


            if(!isset($max_loan_duration_department) || empty($max_loan_duration_department)) $max_loan_duration_department = $max_loan_duration;
            

            $days_bookable_in_advance_text="";
            $max_loan_duration_text="";
            if($days_bookable_in_advance_text == 1) $days_bookable_in_advance_text = "1 Tag";
            else $days_bookable_in_advance_text = $days_bookable_in_advance_text . " Tage";

            if($max_loan_duration_department%7==0){
                $max_loan_duration_text = $max_loan_duration_department/7;
                if($max_loan_duration_text == 1) $max_loan_duration_text = "1 Woche";
                else $max_loan_duration_text = $max_loan_duration_text . " Wochen";
            }
            else{
                $max_loan_duration_text = $max_loan_duration_department;
                if($max_loan_duration_text == 1) $max_loan_duration_text = "1 Tag";
                else $max_loan_duration_text = $max_loan_duration_text . " Tage";
            }
            require "Controller/Rules.php"; 
            new Rules(translate('text_rules_1'),
            translate('text_rules_2', ['a' => $days_bookable_in_advance_text]),
            translate('text_rules_4'),
            translate('text_rules_5')); ?>
        <br>
        <form action="index.php" name="theForm" id="theForm" METHOD="POST">
            <?php
            $all_departments = array_keys($selected_departments);
            for ($i = 0; $i < count($all_departments); $i++) {
                $department_id = $selected_departments[$all_departments[$i]];
                $amount = 0;
                if(array_key_exists($department_id, $ordered_by_department)) $amount = count($ordered_by_department[$department_id]);
                if ($amount == 0) continue; //skip departments without pickup days

                echo "<h5 class='title' style='text-align:center; width:100%;'>" . $departments[$department_id][get_language()] . "</h5><br>"; //departmentname
                echo "<h5 style='text-align:center; width:100%;'>" . translate("text_deviceTypeInfos") . "</h5><br>"; //departmentname

                //pickup
                echo "<div class='row'>";
                echo "<div class='col'>";
                echo "<i style='color:#259cca' data-feather='clock'></i>";
                echo "</div>";
                echo "<div class='col-11'>";
                $pickup_days = array_keys($opening_days[$department_id]);
                for ($o = 0; $o < count($pickup_days); $o++) {
                    echo "<b>" . $opening_days[$department_id][$pickup_days[$o]]['day'] . ": " . $opening_days[$department_id][$pickup_days[$o]]['time'] . "</b><br>";
                }
                echo "</div>";
                echo "</div>";
                echo "<br>";
                //room
                echo "<div class='row'>";
                echo "<div class='col'>";
                echo "<i style='color:#259cca' data-feather='home'></i>";
                echo "</div>";
                echo "<div class='col-11'>";
                echo $departments[$department_id]["room"];
                echo "</div>";
                echo "</div>";
                echo "<br>";
                if ($departments[$department_id]["announce1_de"] != "") { //announcement
                    echo "<div class='row'>";
                    echo "<div class='col'>";
                    echo "<i style='color:#259cca' data-feather='alert-triangle'></i>";
                    echo "</div>";
                    echo "<div class='col-11'>";
                    echo $departments[$department_id]["announce1_de"];
                    echo "</div>";
                    echo "</div>";
                }
                echo "<br>";

                $first_return = next_opening_day($date_to, $department_id, $opening_days);
                $secound_return = next_opening_day($first_return, $department_id, $opening_days);

                $first_pickup = before_opening_day($date_from, $department_id, $opening_days);
                $secound_pickup = before_opening_day($first_pickup, $department_id, $opening_days);
                if (date_diff(date_create(date("Y-m-d")), date_create($first_pickup))->format("%R%a") > 0) { //both pickup days in past
            ?>

                    <div id="dates" class='row' name="dates" style="text-align:center;">
                        <p><?php echo translate('text_pickupDays'); ?></p>
                        <div class='col'>

                            <label for="date_from_<?php echo $department_id; ?>"><?php echo translate('text_pickupDate'); ?></label>
                            <select class="form-select form-select" name="date_from_<?php echo $department_id; ?>" id="date_from_<?php echo $department_id; ?>" onchange='date_from_change(this)'>
                                <option value='' selected disabled hidden><?php echo translate('word_none2'); ?></option>
                                <?php
                                if (date_diff(date_create(date("Y-m-d")), date_create($secound_pickup))->format("%R%a") > 0) echo "<option value='" . $secound_pickup . "'>" . $secound_pickup . "</option>";
                                ?>
                                <option value='<?php echo $first_pickup; ?>'><?php echo $first_pickup; ?></option>
                            </select>
                        </div>
                        <div class='col'>
                            <label for="date_to_<?php echo $department_id; ?>"><?php echo translate('text_returnDate'); ?></label>
                            <select class="form-select" name="date_to_<?php echo $department_id; ?>" id="date_to_<?php echo $department_id; ?>" onchange='date_to_change(this)'>
                                <option value='' selected disabled hidden value=''><?php echo translate('word_none2'); ?></option>
                                <option name='date_to_1_<?php echo $department_id; ?>' id='date_to_1_<?php echo $department_id; ?>' value='<?php echo $first_return; ?>'><?php echo $first_return; ?></option>
                                <option name='date_to_2_<?php echo $department_id; ?>' id='date_to_2_<?php echo $department_id; ?>' value='<?php echo $secound_return; ?>'><?php echo $secound_return; ?></option>
                            </select>
                        </div>
                    </div>
                    <br>

                    <div class='table-responsive'>
                        <table class='table table-condensed'>
                            <thead>
                                <tr >
                                    <th class='band' colspan='2'><?php echo translate('word_device'); ?></th>
                                    <th class='band'><?php echo translate('word_description'); ?></th>
                                    <th class='band'><?php echo translate('word_amount'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $all_types = array_keys($ordered_by_department[$department_id]);
                                for ($o = 0; $o < count($all_types); $o++) {
                                    $type_id = $ordered_by_department[$department_id][$all_types[$o]];
                                    $name = preg_replace('/[\s\.]+/', '_', $device_type[$type_id]['name']);
                                ?>
                                    <tr>
                                        <td style='text-align:center'>
                                            <?php if ($device_type[$type_id]['img_path']) { ?>
                                                <img src='<?php echo $device_type[$type_id]['img_path']; ?>' height='96' width='128' class='zoomD'>
                                            <?php } ?>
                                        </td>
                                        <td style='vertical-align: middle; text-align:center;'>
                                            <?php echo $device_type[$type_id]['name']; ?>
                                        </td>
                                        <td style='vertical-align: middle; text-align:center;'>
                                            <?php if (strlen($device_type[$type_id]['tooltip']) > 0) { ?>
                                                <?php echo $device_type[$type_id]['info']; ?> <a href='#' data-toggle='tooltip' data-html='true' title='<?php echo $device_type[$type_id]['tooltip']; ?>'><?php echo htmlspecialchars_decode("&#9432;"); ?></a>
                                            <?php } else echo $device_type[$type_id]['info']; ?>
                                        </td>
                                        <td style='text-align:center; vertical-align:middle'>
                                            <input type='number' name='<?php echo $name; ?>' id='<?php echo $name; ?>' disabled value=0 min='0' max='0' onchange="checkmax('<?php echo $name; ?>')" onkeypress = "this.onchange();" onpaste = "this.onchange();" oninput = "this.onchange();"/>
                                            <p style='margin:0 auto' class='color_red' id='<?php echo $name; ?>_max'></p>
                                            <input type='text' name='<?php echo $name; ?>_ids' id='<?php echo $name; ?>_ids' value='' hidden/>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                } else echo translate("text_noDevicesInTimespan") . "<br><br>";
                echo "<br>";
            }
            //add note to reservation
            ?>

            <!-- hidden values -->
            <input type='hidden' id='reservation' name='reservation' value='1'>
        </form>
        <br>
        <!-- buttons -->
        <div class='row' style='text-align:center;'>
            <div class='col'>
                <button type='button' class='btn rounded btn-secondary mr-1 mb-1' onclick=window.location.href='index'><?php echo translate('word_back') ?></button>
            </div>
            <div class='col'>
                <button type='button' class='btn rounded btn-primary mr-1 mb-1' onclick='show_modal()'>
                <?php echo translate('word_submit') ?>
                </button>
            </div>
        </div>
    </div>
    <script>
        feather.replace()
    </script>
    <?php
    echo $OUTPUT->footer();
    ?>
</body>