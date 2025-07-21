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


if(exists_and_not_empty('org', $_GET)){ //was fetched
		/** timestamp to collection period **/
		$reservation_id = $_GET['org'];

		/** get the infos from reservation **/
		$query = mysqli_query($link, "SELECT DISTINCT email, date_from, reservations.department_id, room_from FROM user, reservations, departments WHERE departments.department_id=reservations.department_id AND user.id=user_id AND reservation_id=" . $reservation_id);
		$row = mysqli_fetch_array($query);

		$abholbar_timestamp = strtotime($row['date_from']);
		$abholbar_wochentag = date("N", $abholbar_timestamp) % 7; //7 = 0
		$abholbar_uhrzeit;

		$days = array_keys($pickupdays[$row['department_id']]);
		for ($i = 0; $i < count($days); $i++) {
			if ($abholbar_wochentag == $pickupdays[$row['department_id']][$days[$i]]['dayofweek']) {
				$abholbar_uhrzeit = $pickupdays[$row['department_id']][$days[$i]]['time'];
				break;
			}
		}

		$sql = "UPDATE reservations SET status=? WHERE reservation_id=?";
		$stmt = mysqli_prepare($link, $sql);
		$status = 2;
		mysqli_stmt_bind_param($stmt, "ii", $status, $reservation_id);

		if (mysqli_stmt_execute($stmt)) {
			$text = translate('toast_confirm', ["a" => $reservation_id]);
			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
			$SESSION->toasttext = $text;
			session_write_close();

			$sql = "SELECT device_type_name 
					FROM devices_of_reservations 
					JOIN device_list ON devices_of_reservations.device_id = device_list.device_id 
					JOIN device_type ON device_list.device_type_id = device_type.device_type_id 
					WHERE reservation_id = ?";
			$stmt = mysqli_prepare($link, $sql);
			mysqli_stmt_bind_param($stmt, "i", $reservation_id);
			
			$array = array();
			if (mysqli_stmt_execute($stmt)) {
				$result = mysqli_stmt_get_result($stmt);
				while ($row2 = mysqli_fetch_assoc($result)) {
					$array[] = $row2['device_type_name'];
				}
				mysqli_free_result($result);
			} else {
				throw new Exception("ERROR: Could not execute: " . $sql . ": " . mysqli_error($link));
			}

			$amount = array_count_values($array);
			$devices = "";
			foreach ($amount as $device => $count) {
				$devices .= ($devices ? ", " : "") . $count . "x " . $device;
			}

			require_once("Controller/ICS.php");
			$ics_file_contents = createEventICS($row, $abholbar_uhrzeit, $departments, $devices);

			$messagetext = "Ihre Reservierungsanfrage #" . $reservation_id . " wurde bestätigt.<br /><br />
			Sie können die Reservierung am " . date_format(date_create($row['date_from']), 'd.m.Y') . " " . $abholbar_uhrzeit . " im Raum " . $row['room_from'] . " abholen.<br />
			Bringen Sie diese Mail als Bestätigung Ihrer Identität mit.<br /><br />
			Bei Fragen bezüglich Ihrer Reservierung wenden Sie sich bitte an: " . $departments[$row['department_id']]['mail'] . "<br /><br />
			Mit freundlichen Grüßen<br />Ihr Edurent-Team";

			sendamail($mail, $row['email'], "Reservierungsbestätigung #" . $reservation_id, $messagetext, $ics_file_contents);

			echo "<script>window.location.href = 'admini';</script>";
		} else {
			$error = "ERROR: Could not execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	if (exists_and_not_empty('zu', $_GET)) { // downgrade to ...
		$reservation_id = intval($_GET['zu']);

		$sql = "UPDATE reservations SET status = ? WHERE reservation_id = ?";
		$stmt = mysqli_prepare($link, $sql);
		
		$status = 2;
		mysqli_stmt_bind_param($stmt, "ii", $status, $reservation_id);

		if (mysqli_stmt_execute($stmt)) {
			$text = "Reservierungsanfrage #" . $reservation_id . " wurde zurückgestuft.";

			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
			$SESSION->toasttext = $text;
			session_write_close();

			echo "<script>window.location.href = 'admini';</script>";
		} else {
			$error = "ERROR: Could not execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
}


	if(exists_and_not_empty('abh', $_GET)){ //collected
		try {
			/** compare old and new devices and recognize differences **/
			if(!$_GET['new']) throw new Exception("GET(new) is empty");
			if(!$_GET['new_typs']) throw new Exception("GET(new_typs) is empty");
			if(!$_GET['new_tags']) throw new Exception("GET(new_tags) is empty");

			$new_devices = explode(",", $_GET['new']);
			$old_devices = explode("|", $orders[$_GET['abh']][0]);
			$typs = explode(",", $_GET['new_typs']);
			$tags = explode(",", $_GET['new_tags']);
			$old_tags = explode("|", $orders[$_GET['abh']][2]);
			$old_typs = explode("|", $orders[$_GET['abh']][3]);
			$reservation_id = $_GET['abh'];

			$fehlt = array();
			$zuviel = array();
			$array_key_list = array();

			if(count($new_devices) != count($old_devices)) throw new Exception("count of new devices and old devices is not equal");

			for($i = 0; $i < count($new_devices); $i++) {
				if($new_devices[$i] != $old_devices[$i]) {
					array_push($fehlt, $new_devices[$i]);
					array_push($zuviel, $old_devices[$i]);
					array_push($array_key_list, $i);
				}
			}

			if(!(count($zuviel) == count($fehlt))) throw new Exception("array fehlt and array zuviel are not the same length");
			$keys = array_keys($array_key_list);
			/** device change **/
			if(count($zuviel) > 0) {
				for ($i = 0; $i < count($keys); $i++) {
					//get new device id
					$sql = "SELECT device_id FROM device_list, device_type WHERE device_list.device_tag = " . $tags[$array_key_list[$keys[$i]]] . " AND device_list.device_type_id = device_type.device_type_id AND device_type.device_type_indicator =  '" . $typs[$array_key_list[$keys[$i]]] . "'";
					$query = mysqli_query($link, $sql);
					$row = mysqli_fetch_array($query);
					if (!$row) {
						$SESSION->toasttext = "Das Gerät " . $tags[$array_key_list[$keys[$i]]] . " konnte nicht gefunden werden.";
						session_write_close();
						throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
					$new_id = $row['device_id'];

					//get old id
					$sql = "SELECT devices_of_reservations.id FROM device_list, device_type, devices_of_reservations WHERE device_list.device_tag = " . $old_tags[$array_key_list[$i]] . " AND device_list.device_type_id = device_type.device_type_id AND devices_of_reservations.device_id = device_list.device_id AND device_type.device_type_indicator =  '" . $old_typs[$array_key_list[$i]] . "' AND devices_of_reservations.reservation_id=" . $reservation_id . " LIMIT 1";
					$query = mysqli_query($link, $sql);
					$row = mysqli_fetch_array($query);
					if (!$row) {
						$SESSION->toasttext = "Das Gerät " . $tags[$array_key_list[$keys[$i]]] . " konnte nicht gefunden werden.";
						session_write_close();
						throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
					$old_id = $row['id'];

					//Update
					$sql = "UPDATE devices_of_reservations SET devices_of_reservations.device_id= '" . $new_id . "' WHERE devices_of_reservations.id='" . $old_id . "' AND devices_of_reservations.reservation_id=" . $reservation_id . " LIMIT 1";
					if (!mysqli_query($link, $sql)) {
						$SESSION->toasttext = "Das Gerät " . $tags[$array_key_list[$keys[$i]]] . " konnte nicht gefunden werden.";
						session_write_close();
						throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
				}
			}

			/** get the infos from reservation **/
			$query = mysqli_query($link, "SELECT DISTINCT email, date_to, time_to, department_id, room_to FROM user, reservations WHERE user.id=user_id AND reservation_id=" . $reservation_id);
			$row = mysqli_fetch_array($query);

			$return = date('d.m.Y', strtotime($row['date_to'])) . " " . $row['time_to'];

			/** status order **/
			if (isset($_GET['abh']) && is_numeric($_GET['abh'])) {
				$reservation_id = intval($_GET['abh']);

				$sql = "UPDATE reservations SET status = ?, date_from = NOW() WHERE reservation_id = ?";
				$stmt = mysqli_prepare($link, $sql);
				$status = 3;
				mysqli_stmt_bind_param($stmt, "ii", $status, $reservation_id);

				if (mysqli_stmt_execute($stmt)) {
					$text = "Reservierungsanfrage #" . $reservation_id . " wurde abgeholt.";
					save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
					$SESSION->toasttext = $text;
					session_write_close();

					$array = array();
					$sql = "SELECT device_type_name 
							FROM devices_of_reservations 
							JOIN device_list ON devices_of_reservations.device_id = device_list.device_id 
							JOIN device_type ON device_list.device_type_id = device_type.device_type_id 
							WHERE reservation_id = ?";
					$stmt = mysqli_prepare($link, $sql);
					mysqli_stmt_bind_param($stmt, "i", $reservation_id);

					if (mysqli_stmt_execute($stmt)) {
						$result = mysqli_stmt_get_result($stmt);
						while ($row2 = mysqli_fetch_assoc($result)) {
							$array[] = $row2['device_type_name'];
						}
						mysqli_free_result($result);
					} else {
						$SESSION->toasttext = "Geräte konnten nicht geladen werden.";
						session_write_close();
						throw new Exception("ERROR: Could not execute SELECT: " . mysqli_error($link));
					}

					$amount = array_count_values($array);
					$devices = "";
					foreach ($amount as $name => $count) {
						$devices .= ($devices ? ", " : "") . $count . "x " . $name;
					}

					require_once("Controller/ICS.php");
					$ics_file_contents = createEventICS($row, $return, $departments, $devices, true);

					$messagetext = "Sie haben Ihre Reservierung #" . $reservation_id . " abgeholt.<br /><br />
					Bitte bringen Sie Ihre Reservierung am " . $return . " im Raum " . $row['room_to'] . " zurück.<br /><br />
					Bei Fragen bezüglich Ihrer Reservierung wenden Sie sich bitte an: " . $departments[$row['department_id']]['mail'] . "<br /><br />
					Mit freundlichen Grüßen<br />Ihr Edurent-Team";

					sendamail($mail, $row['email'], "Reservierung #" . $reservation_id . " wurde abgeholt", $messagetext, $ics_file_contents);

					$SESSION->toasttext = "Die Reservierung wurde abgeholt";
					session_write_close();
				} else {
					throw new Exception("ERROR: Could not execute UPDATE: " . mysqli_error($link));
				}
			}

		}
		catch (exception $e) {
			error_to_superadmin(get_superadmins(), $mail, "ERROR: in 409 admini: " . $e->getMessage());			
		}
		echo "<script>window.location.href = 'admini';</script>";
	}

	if (exists_and_not_empty('cancel', $_GET) && is_numeric($_GET['cancel'])) { // deleted
		$reservation_id = intval($_GET['cancel']);

		$sql = "UPDATE reservations SET status = ? WHERE reservation_id = ?";
		$stmt = mysqli_prepare($link, $sql);
		$status = 6;
		mysqli_stmt_bind_param($stmt, "ii", $status, $reservation_id);

		if (mysqli_stmt_execute($stmt)) {
			$text = "Reservierungsanfrage #" . $reservation_id . " wurde von einem Admin storniert.";

			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
			$SESSION->toasttext = $text;
			session_write_close();

			echo "<script>window.location.href = 'admini';</script>";
		} else {
			$error = "ERROR: Could not execute UPDATE: " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}


	if (exists_and_not_empty('extend', $_GET) && is_numeric($_GET['extend'])) { //edited
		try {
			$reservation_id = intval($_GET['extend']);

			// --- 1. Get admin and dapartment data ---
			$sql = "SELECT email, mail, departments.department_id 
					FROM user 
					JOIN reservations ON id = user_id 
					JOIN departments ON reservations.department_id = departments.department_id 
					WHERE reservation_id = ?";
			if ($stmt = mysqli_prepare($link, $sql)) {
				mysqli_stmt_bind_param($stmt, "i", $reservation_id);
				mysqli_stmt_execute($stmt);
				$result = mysqli_stmt_get_result($stmt);
				$row = mysqli_fetch_assoc($result);
				if (!$row) throw new Exception("Reservation not found.");
				$email_user = $row['email'];
				$department_mail = $row['mail'];
				$department_id = $row['department_id'];
			} else {
				throw new Exception("Prepare failed: " . mysqli_error($link));
			}

			// --- 2. update reservation informations ---
			$sql = "UPDATE reservations 
					SET date_from = ?, date_to = ?, room_to = ?, room_from = ?, time_from = ?, time_to = ? 
					WHERE reservation_id = ?";
			$stmt = mysqli_prepare($link, $sql);
			mysqli_stmt_bind_param($stmt, "ssssssi",
				$_GET['date_from'], $_GET['date_to'],
				$_GET['room_to'], $_GET['room_from'],
				$_GET['time_from'], $_GET['time_to'],
				$reservation_id
			);
			if (!mysqli_stmt_execute($stmt)) {
				throw new Exception("UPDATE failed: " . mysqli_error($link));
			}

			// --- 3. delete all devices from reservation ---
			$sql = "DELETE FROM devices_of_reservations WHERE reservation_id = ?";
			$stmt = mysqli_prepare($link, $sql);
			mysqli_stmt_bind_param($stmt, "i", $reservation_id);
			mysqli_stmt_execute($stmt);

			// --- 4. add devices of reservation ---
			$new_values = array_filter(explode("|", $_GET['values']));
			$new_indicators = array_filter(explode("|", $_GET['indicators']));
			for ($i = 0; $i < count($new_indicators); $i++) {
				$tag = $new_values[$i];
				$indicator = $new_indicators[$i];

				$sql = "SELECT device_id 
						FROM device_list 
						JOIN device_type ON device_list.device_type_id = device_type.device_type_id 
						WHERE device_list.device_tag = ? AND device_type.device_type_indicator = ?";
				$stmt = mysqli_prepare($link, $sql);
				mysqli_stmt_bind_param($stmt, "ss", $tag, $indicator);
				mysqli_stmt_execute($stmt);
				$result = mysqli_stmt_get_result($stmt);
				$row = mysqli_fetch_assoc($result);
				if (!$row) throw new Exception("Device not found for tag $tag and indicator $indicator.");
				$device_id = $row['device_id'];

				$sql = "INSERT INTO devices_of_reservations (reservation_id, device_id) VALUES (?, ?)";
				$stmt = mysqli_prepare($link, $sql);
				mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $device_id);
				mysqli_stmt_execute($stmt);
			}

			// --- 5. add devices of reservation ---
			require_once("Controller/basic.php");
			$types = explode("|", $_GET['types']);
			$amounts = explode("|", $_GET['amounts']);
			$reservated = get_active_reservations();
			$not_blocked_devices = get_notblockeddevices();
			$id_by_name = get_idbyname();

			$not_blocked_devices = available_devices($reservated, $department_id, $not_blocked_devices, $_GET['date_from'], $_GET['date_to']);
			for ($i = 0; $i < count($types); $i++) {
				$amount = intval($amounts[$i]);
				if ($amount < 1) continue;

				$type = $types[$i];
				if (!isset($id_by_name[$type])) continue;
				$device_type_id = $id_by_name[$type];

				if (!isset($not_blocked_devices[$device_type_id])) continue;
				$device_list = $not_blocked_devices[$device_type_id];
				$device_keys = array_keys($device_list);

				for ($u = 0; $u < $amount && $u < count($device_keys); $u++) {
					$d_id = $device_list[$device_keys[$u]];
					$stmt = mysqli_prepare($link, "INSERT INTO devices_of_reservations (reservation_id, device_id) VALUES (?, ?)");
					mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $d_id);
					mysqli_stmt_execute($stmt);
				}
			}

			// --- 6. save in logs and set toast message ---
			save_in_logs("INFO: Reservierungsanfrage #$reservation_id wurde von einem Benutzer bearbeitet.", $user_firstname, $user_lastname, false);
			$SESSION->toasttext = "Reservierungsanfrage #$reservation_id wurde bearbeitet.";
			session_write_close();

			// --- 7. send mail ---
			$messagetext = "Ihre Reservierung mit der ID #$reservation_id wurde von einem Admin bearbeitet.<br /><br />
			Folgende Informationen sind Ihrer Reservierung zugehörig:<br /><br />
			Bei Fragen bezüglich Ihrer Reservierung wenden Sie sich bitte an: $department_mail<br /><br />
			Mit freundlichen Grüßen<br />Ihr Edurent-Team";

			sendamail($mail, $email_user, "Edurent - #$reservation_id wurde bearbeitet", $messagetext);
			echo "<script>window.location.href = 'admini';</script>";
		}
		catch (Exception $e) {
			error_to_superadmin(get_superadmins(), $mail, "ERROR in admini: " . $e->getMessage());
		}
	}


	if (exists_and_not_empty('ret', $_GET) && is_numeric($_GET['ret'])) { // retour
    	$reservation_id = intval($_GET['ret']);

		$sql = "UPDATE reservations SET status = ?, date_to = NOW() WHERE reservation_id = ?";
		if ($stmt = mysqli_prepare($link, $sql)) {
			$status = 4;
			mysqli_stmt_bind_param($stmt, "ii", $status, $reservation_id);

			if (mysqli_stmt_execute($stmt)) {
				$text = "Reservierungsanfrage #$reservation_id wurde zurückgegeben.";

				save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
				$SESSION->toasttext = $text;
				session_write_close();

				echo "<script>window.location.href = 'admini';</script>";
			} else {
				$error = "ERROR: Could not execute UPDATE: " . mysqli_error($link);
				error_to_superadmin(get_superadmins(), $mail, $error);
			}
		} else {
			$error = "ERROR: Could not prepare statement: " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	if ($is_superadmin) { //Show the active reservations
		$sql = "
			SELECT DISTINCT 
				reservations.reservation_id, 
				reservations.date_from, 
				reservations.date_to, 
				reservations.status, 
				user.fn, 
				user.id, 
				user.ln, 
				departments.department_id, 
				reservations.room_from, 
				reservations.room_to, 
				reservations.time_from, 
				reservations.time_to
			FROM reservations
			JOIN user ON reservations.user_id = user.id
			JOIN departments ON reservations.department_id = departments.department_id
			WHERE (reservations.status < 4 OR reservations.status > 6)
			ORDER BY reservations.reservation_id
		";
	} else {
		$ids = $department_ids;

		if (count($ids) > 0) {
			$placeholders = implode(',', array_fill(0, count($ids), '?'));

			$sql = "
				SELECT DISTINCT 
					reservations.reservation_id, 
					reservations.date_from, 
					reservations.date_to, 
					reservations.status, 
					user.fn, 
					user.id, 
					user.ln, 
					departments.department_id, 
					reservations.room_from, 
					reservations.room_to, 
					reservations.time_from, 
					reservations.time_to
				FROM reservations
				JOIN user ON reservations.user_id = user.id
				JOIN departments ON reservations.department_id = departments.department_id
				WHERE (reservations.status < 4 OR reservations.status > 6)
				AND reservations.department_id IN ($placeholders)
				ORDER BY reservations.reservation_id
			";
		}
		else{
			sendToast("Keine gültige Abteilung angegeben.");
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
			<div class='table-responsive'>
				<table class='table results'>
					<thead>
						<tr>
							<th class='band' scope='col'>
								<?php echo translate('word_number'); ?>
							</th>
							<th class='band' scope='col'>
								<?php echo translate('word_status'); ?>
							</th>
							<th class='band' scope='col'>
								<?php echo translate('word_deadline'); ?>
							</th>
						</tr>
						<tr class='warning no-result' style="display:none;">
							<td colspan='3'><i class='fa fa-warning'></i><?php echo translate('text_noResult'); ?></td>
						</tr>
					</thead>
					<tbody>
	<?php

	if ($result = mysqli_query($link, $sql)) {
		if (mysqli_num_rows($result) > 0) {
	?>		
						<?php
						while ($row = mysqli_fetch_array($result)) {
						?>
						<tr>
							<?php
							$abholbar_timestamp = strtotime($row['date_from']);
							$abholbar_wochentag = date("N", $abholbar_timestamp) % 7; //7 = 0
							$abholbar_uhrzeit;

							$days = array_keys($pickupdays[$row['department_id']]);
							for ($i = 0; $i < count($days); $i++) {
								if ($abholbar_wochentag == $pickupdays[$row['department_id']][$days[$i]]['dayofweek']) {
									$abholbar_uhrzeit = $pickupdays[$row['department_id']][$days[$i]]['time'];
									break;
								}
							}

							$rueckgabe_timestamp = strtotime($row['date_to']);
							$rueckgabe_wochentag = date("N", $rueckgabe_timestamp) % 7; //7 = 0
							$rueckgabe_uhrzeit;

							$days = array_keys($pickupdays[$row['department_id']]);
							for ($i = 0; $i < count($days); $i++) {
								if ($rueckgabe_wochentag == $pickupdays[$row['department_id']][$days[$i]]['dayofweek']) {
									$rueckgabe_uhrzeit = $pickupdays[$row['department_id']][$days[$i]]['time'];
									break;
								}
							}

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
								case 7:
									$status = translate('status_7');
									$button = "btn-danger";
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
							<td>
								<form method="POST" action="view_reservation" style="display:inline;">
									<input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
									<button type="submit" class="btn rounded <?php echo $button; ?> mr-1 mb-1">
										#<?php echo $row['reservation_id']; ?>
									</button>
								</form>
							</td>
							<td style="vertical-align: middle">
								<?php echo $status; ?>
							</td>

							<?php
							switch ($row['status']) {
								case 1: //pickup 
									echo "<td></td>";
									break;
								case 2: //pickup 
									echo "<td style='vertical-align: middle'>" . date_format(date_create($row['date_from']), 'd.m.Y') . "</td>";
									break;
								case 3: //request 
									echo "<td style='vertical-align: middle'>" . date_format(date_create($row['date_to']), 'd.m.Y') . "</td>";
									break;
								case 7: //error 
									echo "<td style='vertical-align: middle'></td>";
									break;
							}
							?>
						</tr>
						<?php
						}
						mysqli_free_result($result);
						?>
					</tbody>
				</table>
			</div>

			<?php } else { ?>

			<div style="text-align:center; width:100%; max-width: 80ch; margin: 0 auto;">
				<h3 style='text-align:center;'>
					Es gibt keine offenen Reservierungen
				</h3>
			</div>

			<?php
		}
	} else {
		error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
	}
			?>
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
<script>
	var added = 0;
	
	function order_extend2(reservation_id) {
		from = document.getElementById("date_from").value;
		time_from = document.getElementById("time_from").value;
		room_from = document.getElementById("room_from").value;

		to = document.getElementById("date_to").value;
		time_to = document.getElementById("time_to").value;
		room_to = document.getElementById("room_to").value;

		//get device infos
		var types = "";
		var amounts = "";
		var values = "";
		var indicators = "";
		for (var i = 0; i <= added; i++) {
			if(!document.getElementById("type_" + i)) continue;
			var value = document.getElementById("type_" + i).value;
			if(value != ""){
				if(value == undefined){
					values += document.getElementById("device_" + i).value + "|";
					indicators += document.getElementById("indicator_" + i).innerHTML + "|";
				}
				else{
					types += document.getElementById("type_" + i).value + "|";
					amounts += document.getElementById("amount_" + i).value + "|";
				}
			}
		}

		location.href = "?extend=" + reservation_id + "&date_from=" + from + "&date_to=" + to + "&room_from=" + room_from + "&room_to=" + room_to + "&time_to=" + time_to + "&time_from=" + time_from + "&types=" + types + "&amounts=" + amounts + "&values=" + values + "&indicators=" + indicators;
	}
</script>