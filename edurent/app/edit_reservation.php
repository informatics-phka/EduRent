<!DOCTYPE HTML>
<?php
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

check_is_admin($user_username);

$is_superadmin = is_superadmin($user_username);
$all_user = get_all_user();


$devices_of_deparment = array();;
$sql;
if($is_superadmin){
	$sql = "SELECT device_type_name, device_type_indicator FROM departments, device_type WHERE department_id=home_department ORDER BY home_department";
}
else{
	$ids;
	for ($i=0; $i < count($department_ids); $i++) {
		if($i == 0) $ids = "(department_id=" . $department_ids[$i];
		else $ids .= " OR department_id=" . $department_ids[$i];
	}
	$ids .= ")"; 
	$sql = "SELECT device_type_name, device_type_indicator FROM departments, device_type WHERE department_id=home_department AND " . $ids . " ORDER BY home_department";
}
if ($result = mysqli_query($link, $sql)) {
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$id = count($devices_of_deparment);
			$devices_of_deparment[$id]['device_type_name'] = $row['device_type_name'];
			$devices_of_deparment[$id]['device_type_indicator'] = $row['device_type_indicator'];
		}
		mysqli_free_result($result);
	}
} else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $_SESSION['reservation_id'] = $reservation_id;

    echo "<script>window.location.href = 'edit_reservation';</script>";
    exit;
} 

$reservation_id = $_SESSION['reservation_id'] ?? null;

//get order
$sql = "SELECT reservations.reservation_id, date_from, date_to, status, fn, user.id AS user_id, ln, departments.department_id, room_from, room_to FROM reservations, user, departments WHERE departments.department_id=reservations.department_id AND reservations.user_id=user.id AND user_id=user.id AND reservations.reservation_id=" . $reservation_id ;
if ($result = mysqli_query($link, $sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $department_id = $row['department_id'];
            $reservation_id = $row['reservation_id'];
            $date_from = $row['date_from'];
            $date_to = $row['date_to'];
            $status = $row['status'];
            $fn = $row['fn'];
            $ln = $row['ln'];
            $room_from = $row['room_from'];
            $room_to = $row['room_to'];
            $user_id = $row['user_id'];
        }
    } else {
        echo "No results found.";
    }
} else {
    echo "Error: " . mysqli_error($link);
}

//get orders you are allowed to see
$orders = array();
if ($is_superadmin) {
    $orders = get_all_orders();
} else {
	foreach ($department_ids as $department_id) {
        $department_orders = get_all_orders_from_department($department_id);
        if ($department_orders) {
            $orders = $orders + $department_orders;
        }
    }
}

uasort($all_user, function($a, $b) {
    $ln_cmp = strcmp($a['ln'], $b['ln']);
    if ($ln_cmp === 0) {
        return strcmp($a['fn'], $b['fn']);
    }
    return $ln_cmp;
});

$names = 0;

//getlimits
$limits = get_limits_of("device_list");
?>

<body>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		
		<!-- JQuery -->
		<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
		<script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
		
		<!-- Bootstrap -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
		<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
		
		<!-- stylesheet -->
		<link rel="stylesheet" href="style-css/rent.css">
        <link rel="stylesheet" href="style-css/toasty.css">
		<link rel="stylesheet" href="style-css/page_colors.scss">
        <link rel="stylesheet" href="style-css/accessability.css">
		<link rel="stylesheet" href="style-css/navbar.css">

        <!-- Select2 -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
		
		<!-- Font Awesome -->
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
		
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>

        <!-- daterangepicker -->
        <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
	</head>
    <div class="main">
        <?php require_once("app/navbar.php"); ?>   
        <br>
        <?php
        if (isset($reservation_id )) {
            $formatted_from = date_create($date_from)->format('d.m.Y');
            $formatted_to = date_create($date_to)->format('d.m.Y');
            echo "<h1>" . translate('word_reservation') . " " . htmlspecialchars($reservation_id) . "</h1>";
            echo '<label for="user_select">Ausleihende Person wählen:</label>';
            echo '<select id="user_select" class="form-control" name="selected_user">';
            foreach ($all_user as $id => $user) {
                if (empty($user['fn']) || empty($user['ln'])) {
                    continue;
                }
            
                $value = htmlspecialchars($id);
                $label = htmlspecialchars($user['fn'] . " " . $user['ln']);
                $selected = ($id == $user_id) ? 'selected' : '';
                echo "<option value='$value' $selected>$label</option>";
            }
            echo '</select>';
            echo "<br>";
            echo "Zeitraum: ";
            echo "<input type='text' name='daterange' id='daterange' style='text-align:center; width:100%; max-width: 27ch;' placeholder='Keine Reservierung möglich' />";
            echo "<br>";
            echo "<input type='hidden' id='date_from' name='date_from' value=''>";
            echo "<input type='hidden' id='date_to' name='date_to' value=''>";

            echo "<br>";
            $d_ids = explode('|', $orders[$reservation_id][0]);
            $names = explode('|', $orders[$reservation_id][1]);
            $tag = explode('|', $orders[$reservation_id][2]);
            $type_indicator = explode('|', $orders[$reservation_id][3]);

            $geraete = translate('word_deviceList') . ":<br>";
            $geraete .= "<div id='device_list' name='device_list'>";

            for ($i = 0; $i < count($d_ids); $i++) {
                $geraete .= "<div class='row no-gutters' style='text-align:center;' name='device_list_" . $i . "' id='device_list_" . $i . "'>";
                    $geraete .= "<div class='col col-lg-5' name='type_" . $i . "' id='type_" . $i . "'>";
                        $geraete .= $names[$i];
                    $geraete .= "</div>";
                    $geraete .= "<div class='col input-group'>";
                        $geraete .= "<span style='width:7ch; justify-content: right; display: flex;' class='input-group-text' id='indicator_" . $i . "' name = 'indicator_" . $i . "'>" . $type_indicator[$i] . "</span>";
                        $geraete .= "<input type='text' style='height:100%' maxlength='" . $limits['device_tag'] . "' class='form-control rounded' id='device_" . $i . "' name = 'device_" . $i . "' value='" . $tag[$i] . "'>";
                    $geraete .= "</div>";
                    $geraete .= "<div class='col col-lg-2' style='align-items: center; justify-content: center;display: flex;'>";
                        $geraete .= "<button class='btn btn-outline-secondary' style='height: 90%; display: flex; align-items: center;' onclick='remove_device(" . $i . ")'>-</button>";
                    $geraete .= "</div>";
                $geraete .= "</div>";
            }
            $geraete .= "</div>";
            $geraete .= "<br>";
            $geraete .= "<div class='text-center'>";
                $geraete .= "<button type='button' class='btn btn-secondary add-btn' onclick='add_devices()'>";
                    $geraete .= "<i class='fas fa-plus'></i> Gerät hinzufügen";
                $geraete .= "</button>";
            $geraete .= "</div>";
            echo $geraete;

        } else {
            echo "<script>window.location.href = 'view_reservation';</script>";
        }
        ?>
        <br>
        <!-- Buttons -->        
        <form method="POST" action="edit_reservation" style="display:inline;">
            <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
            <div class='col-md-6 mb-3'>
                <button type='submit' class='btn btn-success btn-block rounded mr-1 mb-1'>
                    <?php echo translate('word_editBig'); ?>
                </button>
            </div>
        </form>

    </div>
</body>

<?php
    echo $OUTPUT->footer();
?>

<script>
	document.querySelectorAll(".collapse_me th").forEach(headerCell => {
		headerCell.addEventListener("click", () => {
			var tbodyCollapsed = document.querySelector(".collapse_me tbody.collapsed");
			var tbodyVisible = document.querySelector(".collapse_me tbody:not(.collapsed)");

			if (tbodyCollapsed && tbodyVisible) {
				tbodyCollapsed.classList.remove("collapsed");
				tbodyVisible.classList.add("collapsed");
			}
		});
	});

    //Select2 for user
    $(document).ready(function() {
        $('#user_select').select2({
            placeholder: "Person auswählen",
            allowClear: true
        });
    });

    var names = <?php echo is_null($names) ? "2" : json_encode($names); ?>;
    var added = names.length-1;

    //daterangepicker
    var first_day =  "<?php echo date_create($date_from)->format('d.m.Y'); ?>";
    
    var parts = first_day.split('.');
    var jsDate = new Date(parts[2], parts[1] - 1, parts[0]);

    var dd = String(jsDate.getDate()).padStart(2, '0');
    var mm = String(jsDate.getMonth() + 1).padStart(2, '0');
    var yyyy = jsDate.getFullYear();

    first_day = dd + '/' + mm + '/' + yyyy;

    var last_day =  "<?php echo date_create($date_to)->format('d.m.Y'); ?>";
    
    parts = last_day.split('.');
    jsDate = new Date(parts[2], parts[1] - 1, parts[0]);

    dd = String(jsDate.getDate()).padStart(2, '0');
    mm = String(jsDate.getMonth() + 1).padStart(2, '0');
    yyyy = jsDate.getFullYear();

    last_day = dd + '/' + mm + '/' + yyyy;

    $(function() {
        $('input[name="daterange"]').daterangepicker({
            //Daterangepicker gets opened
            "locale": {
                "format": "DD/MM/YYYY",
                "separator": " <?php echo translate('word_to'); ?> ",
                "applyLabel": "<?php echo translate('word_confirm'); ?>",
                "cancelLabel": "<?php echo translate('word_back'); ?>",
                "daysOfWeek": [
                    "<?php echo translate('weekday_short_7'); ?>",
                    "<?php echo translate('weekday_short_1'); ?>",
                    "<?php echo translate('weekday_short_2'); ?>",
                    "<?php echo translate('weekday_short_3'); ?>",
                    "<?php echo translate('weekday_short_4'); ?>",
                    "<?php echo translate('weekday_short_5'); ?>",
                    "<?php echo translate('weekday_short_6'); ?>"
                ],
                "monthNames": [
                    "<?php echo translate('word_month_1'); ?>",
                    "<?php echo translate('word_month_2'); ?>",
                    "<?php echo translate('word_month_3'); ?>",
                    "<?php echo translate('word_month_4'); ?>",
                    "<?php echo translate('word_month_5'); ?>",
                    "<?php echo translate('word_month_6'); ?>",
                    "<?php echo translate('word_month_7'); ?>",
                    "<?php echo translate('word_month_8'); ?>",
                    "<?php echo translate('word_month_9'); ?>",
                    "<?php echo translate('word_month_10'); ?>",
                    "<?php echo translate('word_month_11'); ?>",
                    "<?php echo translate('word_month_12'); ?>"
                ],
                "firstDay": 1
            },
            "startDate": first_day,
            "endDate": last_day,
            "opens": "center",
            "drops": "auto",
            isInvalidDate: function(date) {
                if (date.day() == 6 || date.day() == 0) //disable weekend
                    return true;
                return false;
            }
        });
    });

    $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
        document.getElementById("date_from").value = picker.startDate.format('DD-MM-YYYY');
        document.getElementById("date_to").value = picker.endDate.format('DD-MM-YYYY');
    });

    function add_devices(){
		var devices_of_deparment = <?php echo is_null($devices_of_deparment) ? "2" : json_encode($devices_of_deparment); ?>;
		if(devices_of_deparment == "2") return;
		var string = document.getElementById('device_list').innerHTML;
		added++;

		var string = "<div class='row no-gutters' style='text-align:center;' name='device_list_" + added + "' id='device_list_" + added + "'>";
			string += "<div class='col'>";
				string += "<select class='form-select' name='type_" + added + "' id='type_" + added + "'>";
					for (var i = 0; i < devices_of_deparment.length; i++) {
						string += "<option id='device_" + devices_of_deparment[i]['device_type_indicator'] + "' name='device_" + devices_of_deparment[i]['device_type_indicator'] + "'>" + devices_of_deparment[i]['device_type_name'] + "</option>";
					}
				string += "</select>";
			string += "</div>";
			string += "<div class='col col-lg-2'>";
				string += "<input type='number' name='amount_" + added + "' id='amount_" + added + "' class='form-control' value='1'>"; 
			string += "</div>";
			string += "<div class='col col-lg-2' style='align-items: center; justify-content: center;display: flex;'>";
                string += "<button class='btn btn-outline-secondary' style='height: 90%; display: flex; align-items: center;' onclick='remove_device(" + added + ")'>-</button>";
			string += "</div>";
		string += "</div>";

		var str = '<p>Some more <span>text</span> here</p>',
		div = document.getElementById( 'device_list' );
		div.insertAdjacentHTML( 'beforeend', string );
	}

    function remove_device(id){
		document.getElementById('device_list_' + id).innerHTML = "";
	}
</script>