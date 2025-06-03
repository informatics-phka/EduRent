<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_is_admin($user_username);

//get data
$departments;
$sql = "SELECT * FROM departments";
if ($result = mysqli_query($link, $sql)) {
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$departments[$row['department_id']]['mail'] = $row['mail'];
			$departments[$row['department_id']]['de'] = $row['department_de'];
			$departments[$row['department_id']]['en'] = $row['department_en'];
		}
		mysqli_free_result($result);
	}
} else error_to_superadmin(get_blocked_devices(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));

$pickupdays = get_allopeningdays();
$admins = get_all_admins();
$department_ids = get_admin_department($user_username);
$is_superadmin = is_superadmin($user_username);

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

//getlimits
$limits = get_limits_of("device_list");

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
?>


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
	
	<!-- searchbar -->
	<script type="text/javascript" src="js/searchbar.js"></script>
	
	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
	
	<!-- Toast -->
	<?php require_once("Controller/toast.php"); ?>
</head>
<body>
	<style>
		.collapsed {
			display: none;
		}
	</style>
	<script>
		function modal_load(reservation_id, from, to, fn, ln, status, room_from, room_to, department, time_from, time_to) {
			var mymodal = $('#my_modal');
			var titel = "<?php echo translate('word_order'); ?> #" + reservation_id;
			mymodal.find('.modal-title').text(titel);

			var orders = <?php echo is_null($orders) ? "2" : json_encode($orders); ?>;

			$("#button_grey").show();
			$("#button_green").show();
			$("#button_yellow").show();
			$("#button_grey").html('<?php echo translate('word_back'); ?>');
			$("#button_red").hide();
			$("#button_yellow").html('<?php echo translate('word_editBig'); ?>');
			$("#button_yellow").attr("onclick", "order_extend(" + reservation_id + ", '" + from + "', '" + to + "', '" + time_from + "', '" + time_to + "', '" + room_from + "', '" + room_to + "')");

			if (orders == "2" || orders[reservation_id] == null) { //Error
				var user = "<?php echo translate('word_from'); ?>: " + fn + " " + ln + "<br>";
				var department = "<?php echo translate('word_department'); ?>: " + department + "<br>";
				var translate_pickup = "<?php echo translate('text_pickupReservation'); ?>";
                var translate_return = "<?php echo translate('text_returnReservation'); ?>";
                translate_pickup = translate(translate_pickup, [from, time_from, room_from]);
                translate_return = translate(translate_return, [to, time_to, room_to]);

				var error = '<?php echo translate('text_noDevices'); ?> <br><br>' ;

				var string = '<center>' + error + user + department + translate_pickup + translate_return + '</center>';
				$("#button_red").hide();
				$("#button_yellow").hide();
				$("#button_green").html('<?php echo translate('word_delete'); ?>');
				$("#button_green").attr("onclick", "order_remove(" + reservation_id + ")");
			} else {
				var time;
				if (status == 2){ //Collectable
					$("#button_red").html('<?php echo translate('status_3'); ?>');
					$("#button_red").show();
					$("#button_red").attr("onclick", "order_pickup(" + reservation_id + ")");
					$("#button_green").html('<?php echo translate('word_cancel'); ?>');
					$("#button_green").attr("onclick", "order_cancel(" + reservation_id + ")");
					var translate_pickup = "<?php echo translate('text_pickupReservation'); ?>";
					var translate_return = "<?php echo translate('text_returnReservation'); ?>";
					time = translate(translate_pickup, [from, time_from, room_from]) + "<br>";
					time += translate(translate_return, [to, time_to, room_to]) + "<br>";
				} else if (status == 3){ //Retrieved
					$("#button_green").html('<?php echo translate('word_downgrade'); ?>');
					$("#button_green").attr("onclick", "order_back(" + reservation_id + ")");
					$("#button_red").html('<?php echo translate('word_return'); ?>');
					$("#button_red").attr("onclick", "order_retour(" + reservation_id + ")");
					$("#button_red").show();
					var translate_return = "<?php echo translate('text_returnReservation'); ?>";
					time = translate(translate_return, [to, time_to, room_to]) + "<br>";
				} else if (status == 1){ //request
					$("#button_red").html('<?php echo translate('word_confirm'); ?>');
					$("#button_red").show();
					$("#button_red").attr("onclick", "order_accept(" + reservation_id + ")");
					$("#button_green").html('<?php echo translate('word_cancel'); ?>');
					$("#button_green").attr("onclick", "order_cancel(" + reservation_id + ")");
					time = "<?php echo translate('word_period'); ?>: " + from + " <?php echo translate('word_to'); ?> " + to + "<br>";
					time += "<?php echo translate('word_pickupRoom'); ?>: " + room_from + ", <?php echo translate('word_returnRoom'); ?>: " + room_to + "<br>";
				} else if (status == 4 || status == 6){ //Completed or Cancelled
					$("#button_green").html('<?php echo translate('word_delete'); ?>');
					$("#button_green").attr("onclick", "order_remove(" + reservation_id + ")");
					$("#button_yellow").hide();
					time = "<?php echo translate('word_period'); ?>: " + from + " <?php echo translate('word_to'); ?> " + to + "<br>";
				}

				var d_ids = orders[reservation_id][0].split('|');
				var names = orders[reservation_id][1].split('|');
				var geraete = "<?php echo translate('word_devices'); ?>:<br>";
				for (var i = 0; i < d_ids.length; i++) {
					geraete += d_ids[i] + ", " + names[i] + "<br>";
				}

				var user = "<?php echo translate('word_from'); ?>: " + fn + " " + ln + "<br>";
				var department = "<?php echo translate('word_department'); ?>: " + department + "<br>";

				var string = '<center>' + user + department + time + '<br>' + geraete + '</center>';
			}
			mymodal.find('.modal-body').html(string);
			mymodal.modal('show');
		}
	</script>
	<?php

	setlocale(LC_ALL, "de_DE.utf8");

	$heute_datum = new DateTime();
	$heute_timestamp = date('Y-m-d', $heute_datum->getTimestamp());

	?>
		<div class="main">			
			<?php require_once 'navbar.php'; ?>	
			<br>
			<div class='row no-gutters text-center'>
				<div>
					<input type='text' class='w-100 search form-control' title='Bestellungen = #2<br>Geräte\nPersonen\n<?php echo translate('word_date'); ?> = 05.08.2023' placeholder='<?php echo translate('word_search'); ?>' />
				</div>
				<br>
				<br>
				<div class="table-responsive">
					<table class='table results collapse_me'>
						<thead>
							<tr>
								<th class='band' scope='col'><?php echo translate('word_number'); ?></th>
								<th class='band' scope='col'><?php echo translate('word_dateFrom'); ?></th>
								<th class='band' scope='col'><?php echo translate('word_dateTo'); ?></th>
								<th class='band' scope='col'><?php echo translate('word_status'); ?></th>
							</tr>
							<tr class='warning no-result' style="display:none;">
								<td colspan='4'><i class='fa fa-warning'></i><?php echo translate('text_noResult'); ?></td>
							</tr>
						</thead>
						<tbody>
	<?php
	$sql;

	if ($department_ids[0] == 0) { //is superadmin
		$sql = "SELECT DISTINCT reservations.reservation_id, date_from, date_to, status, fn, user.id, ln, departments.department_id, room_from, room_to FROM reservations, user, departments WHERE departments.department_id=reservations.department_id AND reservations.user_id=user.id AND status>3 AND status <7 AND user_id=user.id ORDER BY reservation_id DESC";
	} else {
		$departments_sql = "(reservations.department_id='" . $department_ids[0] . "'";
		for($i = 1; $i < count($department_ids); $i++) {
			$departments_sql .= " OR reservations.department_id='" . $department_ids[$i] . "'";
		}
		$departments_sql .= ")";
		$sql = "SELECT DISTINCT reservations.reservation_id, date_from, date_to, status, fn, user.id, ln, departments.department_id, room_from, room_to FROM reservations, user, departments WHERE departments.department_id=reservations.department_id AND reservations.user_id=user.id AND status>3 AND status <7 AND user_id=user.id AND " . $departments_sql . " ORDER BY reservation_id DESC";
	}

	if ($result = mysqli_query($link, $sql)) {
		if (mysqli_num_rows($result) > 0) {
			?>
				
							<?php while ($row = mysqli_fetch_array($result)) { ?>
								<tr>
									<td> <button type="button" class="btn rounded btn-outline-dark mr-1 mb-1" onclick="modal_load(<?php echo $row['reservation_id']; ?>,'<?php echo date_format(date_create($row['date_from']), 'd.m.Y'); ?>','<?php echo date_format(date_create($row['date_to']), 'd.m.Y'); ?>','<?php echo $row['fn']; ?>','<?php echo $row['ln']; ?>','<?php echo $row['status']; ?>','<?php echo $row['room_from']; ?>','<?php echo $row['room_to']; ?>','<?php echo $departments[$row['department_id']][get_language()]; ?>')">#<?php echo $row['reservation_id']; ?></button></td>
								<?php
								switch (!is_null($row['date_from'])) { //column from
									case true:
										echo "<td style='vertical-align: middle'>" . date_format(date_create($row['date_from']), 'd.m.Y') . "</td>";
										break;
									case false:
										echo "<td></td>";
										break;
								}

								switch (!is_null($row['date_to'])) { //column to
									case true:
										echo "<td style='vertical-align: middle'>" . date_format(date_create($row['date_to']), 'd.m.Y') . "</td>";
										break;
									case false:
										echo "<td></td>";
										break;
								}
								$status = get_history_status($row['status']);
								echo "<td style='vertical-align: middle'>" . $status . "</td>";

								/** hidden infos for searchbar **/
								echo "<td style='display:none;'>#" . $row['reservation_id'] . "</td>"; //reservation id
								echo "<td style='display:none;'>" . $row['fn'] . " " . $row['ln'] . "</td>"; //full name
								if(exists_and_not_empty($row['reservation_id'],$orders)){
									if(exists_and_not_empty(0,$orders[$row['reservation_id']])){ //devices
										echo "<td style='display:none;'>" . $orders[$row['reservation_id']][0] . "</td>"; //device ids
									}
								}
								echo "</tr>";
							}
							mysqli_free_result($result);
						} else { ?>
								<br>
								<br>
								<h5 style='text-align:center;'>Es gibt keine vergangenen Reservierungen</h5>
						<?php }
					} else {
						error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
						?>
							</tbody>
						</table>
					</div>
				</div>
	<!-- tablesort -->
	<script type="text/javascript" src="js/tablesort.js"></script>
</body>
<?php

echo $OUTPUT->footer();
mysqli_close($link);

//Functions PHP
function get_history_status($status_id)
{
	switch ($status_id) {
		case 4:
			$status = translate('status_4');
			break;
		case 5:
			$status = translate('status_5');
			break;
		case 6:
			$status = translate('status_6');
			break;
		default:
			$status = "Fehler";
			break;
	}
	return $status;
}
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
</script>