<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_superadmin($user_username);

//check working
if (isEmpty($_GET['u_id'])) {
	$SESSION->toasttext = "Fehler beim Aufrufen von edit_admins.php";
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_admins.php: _GET[u_id] isEmpty {" . $_GET['u_id'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

//get data
$admins = get_all_admins();
$departments = get_departmentnames();
$is_superadmin = is_superadmin($user_username);

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
	<link rel="stylesheet" href="style-css/accessability.css">
	<link rel="stylesheet" href="style-css/navbar.css">
	
	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

	<!-- Select2 -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	
	<!-- Toast -->
	<?php require_once("Controller/toast.php"); ?>
</head>
<body>
<<<<<<< Updated upstream

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
        <link rel="stylesheet" href="style-css/accessability.css">
		
		<!-- Font Awesome -->
    	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    	
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>
	</head>
	<div class="main">
=======
	<div class="main">
		<?php require_once 'navbar.php'; ?>	
		</br>
>>>>>>> Stashed changes
		<h3 class="text-center">
			<?php echo translate('word_admin'); ?> '<?php echo $admins[$_GET['u_id']]['fn']; ?> <?php echo $admins[$_GET['u_id']]['ln']; ?>' <?php echo translate('word_edit'); ?>
		</h3>

		<form action="admins.php" method="post">
			<label for="department" class="form-label"><?php echo translate('word_department'); ?></label>
			<div id="checks">
				<?php
				for ($i = 0; $i < count($departments); $i++) {
                    if (array_keys($departments)[$i] == $unassigned_institute) {
                        continue;
                    }
					echo "<div class='form-check form-switch'>";
					if (in_array(array_keys($departments)[$i],$admins[$_GET['u_id']]['departments'])) echo "<input class='form-check-input' type='checkbox' role='switch' checked name='switch_" . array_keys($departments)[$i] . "'>";
					else echo "<input class='form-check-input' type='checkbox' role='switch' name='switch_" . array_keys($departments)[$i] . "'>";

					if (get_language() == "de") echo "<label class='form-check-label' for='switch_" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]]['de'] . "</label>";
					else echo "<label class='form-check-label' for='switch_" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]]['en'] . "</label>";
					echo "</div>";
				}
				?>
			</div>
			<br>

			<!-- hidden values -->
			<input type="hidden" id="reason" name="reason" value="edit">
			<input type="hidden" id="user" name="user" value="<?php echo $_GET['u_id']; ?>">

			<!-- Buttons -->
            <div class='row justify-content-center'>
                <div class='col-md-6 mb-3'>
                    <a class='btn btn-secondary btn-block' href='admins'>
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?php echo translate('word_back'); ?>
                    </a>
                </div>
                <div class='col-md-6 mb-3'>
                    <button type='submit' id="submit" class='btn btn-success btn-block rounded mr-1 mb-1'>
                        <i class="fas fa-save mr-2"></i>
                        <?php echo translate('word_save'); ?>
                    </button>
                </div>
            </div>
		</form>
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-danger btn-block rounded' href='admins.php?remove_id=<?php echo $_GET["u_id"]; ?>'>
					<i class="fas fa-trash-alt mr-2"></i>
					<?php echo translate('word_delete'); ?>
				</a>
			</div>
		</div>
	</div>

	<script>
		//checkbox controll
		var all = '<?php echo $all_institutes; ?>';
		var none = '<?php echo $unassigned_institute; ?>';

		$("#checks :checkbox").change(function(e) {
			if ($(this).is(":checked") && ($(this).attr("name").includes(none) || $(this).attr("name").includes(all))) {
				$('input:checkbox').not(this).prop('checked', false);
			} else {
				var search = 'input:checkbox[name*=' + none + ']';
				$(search).not(this).prop('checked', false);

				var search = 'input:checkbox[name*=' + all + ']';
				$(search).not(this).prop('checked', false);
			}
		});
	</script>
	<?php
	echo $OUTPUT->footer();
	?>
</body>