<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_superadmin($user_username);

//get data
$departments = get_departmentnames();
$admins = get_all_admins();
$users = get_all_user();
$non_admin = array_diff_key($users, $admins);

save_in_logs($non_admin);

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
	
	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
	
	<!-- Toast -->
	<?php require_once("Controller/toast.php"); ?>
</head>
<body>
	<div class="main">
		<h3 class="text-center"><?php echo translate('text_createAdmin'); ?></h3>

		<form class="needs-validation" id="form" name="form" action="admins.php" method="post">
			<div class="mb-3">
			<label for="user" class="form-label"><?php echo translate('word_user'); ?></label>
			<select class="form-select" id="user" name="user" aria-label=".form-select-lg example" required>
				<option value=""><?php echo translate('word_none3'); ?></option>
				<?php
				for ($i = 0; $i < count($non_admin); $i++) {
					if (array_keys($non_admin)[$i] != $unassigned_institute) echo "<option value='" . array_keys($non_admin)[$i] . "'>" . $non_admin[array_keys($non_admin)[$i]]['fn'] . " " . $non_admin[array_keys($non_admin)[$i]]['ln'] . "</option>";
				}

				?>
			</select>
			<br>

			<label for="department" class="form-label"><?php echo translate('word_department'); ?></label>
			<div id="checks">
				<?php
				for ($i = 0; $i < count($departments); $i++) {
					if (array_keys($departments)[$i] == $unassigned_institute) {
						continue;
					}
					echo "<div class='form-check form-switch'>";
					echo "<input class='form-check-input' type='checkbox' role='switch' name='switch_" . array_keys($departments)[$i] . "'>";

					if (get_language() == "de") echo "<label class='form-check-label' for='switch_" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]]['de'] . "</label>";
					else echo "<label class='form-check-label' for='switch_" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]]['en'] . "</label>";
					echo "</div>";
				}
				?>
			</div>
			<br>

			<!-- hidden values -->
			<input type="hidden" class="form-control" id="a_id" name="a_id" value=''>
			<input type="hidden" class="form-control" id="reason" name="reason" value="create">
		</form>

		<!-- Buttons -->
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-secondary btn-block' href='admini'>
					<i class="fas fa-arrow-left mr-2"></i>
					<?php echo translate('word_back'); ?>
				</a>
			</div>
			<div class='col-md-6 mb-3'>
				<button type="button" class="btn btn-success btn-block" disabled onclick='document.form.submit()'><?php echo translate('word_confirm'); ?></button>
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

			$("#user").change(function(e) {
				if ($(this).val() != "") {
					$('button').prop('disabled', false);
				} else {
					$('button').prop('disabled', true);
				}
			});
		</script>
	</div>
</body>
<?php
echo $OUTPUT->footer();
?>