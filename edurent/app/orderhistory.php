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

if (exists_and_not_empty('rem', $_GET) && is_numeric($_GET['rem'])) { // Delete reservation
		$reservation_id = intval($_GET['rem']);

		$sql = "DELETE FROM reservations WHERE reservation_id = ?";
		if ($stmt = mysqli_prepare($link, $sql)) {
			mysqli_stmt_bind_param($stmt, "i", $reservation_id);

			if (mysqli_stmt_execute($stmt)) {
				if (mysqli_stmt_affected_rows($stmt) > 0) {
					$text = "INFO: Reservierungshistorie #$reservation_id wurde gelöscht.";
					save_in_logs($text, $user_firstname, $user_lastname, false);
					$SESSION->toasttext = $text;
					session_write_close();

					echo "<script>window.location.href = 'orderhistory';</script>";
				} else {
					error_to_superadmin(get_superadmins(), $mail, "Reservierungshistorie #$reservation_id konnte nicht gelöscht werden.");
				}
			} else {
				$error = "ERROR: Could not execute DELETE: " . mysqli_error($link);
				error_to_superadmin(get_superadmins(), $mail, $error);
			}
		} else {
			$error = "ERROR: Could not prepare DELETE statement: " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}
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
	<div class="main">			
		<?php require_once 'navbar.php'; ?>	
		<br>
		<div class='row no-gutters'>
			<div>
				<input type='text' class='w-100 search form-control' title='Bestellungen = #2<br>Geräte\nPersonen\n<?php echo translate('word_date'); ?> = 05.08.2023' placeholder='<?php echo translate('word_search'); ?>' />
			</div>
			<br>
			<br>
			<div class="table-responsive">
				<table class='table results'>
					<thead>
						<tr>
							<th class='band' scope='col'>
									<?php echo translate('word_number'); ?>
							</th>
							<th class='band' scope='col'>
								<?php echo translate('word_dateFrom'); ?>
							</th>
							<th class='band' scope='col'>
								<?php echo translate('word_dateTo'); ?>
							</th>
							<th class='band' scope='col'>
								<?php echo translate('word_status'); ?>
							</th>
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
								<td> 
									<form method="POST" action="view_reservation" style="display:inline;">
										<input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
										<button type="submit" class="btn rounded btn-secondary mr-1 mb-1">
											#<?php echo $row['reservation_id']; ?>
										</button>
									</form>
								</td>
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
							<div style="text-align:center; width:100%; max-width: 80ch; margin: 0 auto;">
								<h3 style='text-align:center;'>
									Es gibt keine vergangenen Reservierungen
								</h3>
							</div>
						<?php }
					} else {
						error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
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