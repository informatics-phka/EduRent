<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_superadmin($user_username);
$is_superadmin = is_superadmin($user_username);


//get data
$departments = get_departmentnames();
$admins = get_all_admins();
$users = get_all_user();
$non_admin = array_diff_key($users, $admins);

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
	<div class="main">
		<?php require_once 'navbar.php'; ?>	
		<br>
		<h3 class="text-center"><?php echo translate('text_createAdmin'); ?></h3>
		<form class="needs-validation" id="form" name="form" action="admins.php" method="post">
			<div class="mb-3">
			
			<!-- User selection -->
			<label for="user" class="form-label"><?php echo translate('word_user'); ?></label>
			<select class="form-control js-example-single-multiple" data-placeholder="Benutzer wählen" id="user" name="user" multiple="multiple" required>
				<option value=""><?php echo translate('word_none3'); ?></option>
				<?php
				for ($i = 0; $i < count($non_admin); $i++) {
					if (array_keys($non_admin)[$i] != $unassigned_institute) echo "<option value='" . array_keys($non_admin)[$i] . "'>" . $non_admin[array_keys($non_admin)[$i]]['fn'] . " " . $non_admin[array_keys($non_admin)[$i]]['ln'] . "</option>";
				}
				?>
			</select>
			
			<br>
			<br>

			<!-- Department selection -->
			<label for="department_select" class="form-label"><?php echo translate('word_department'); ?></label>
			<select class="form-control js-example-basic-multiple" 
					data-placeholder="Institut auswählen" 
					id="department_select"  
					name="states[]" 
					multiple="multiple" 
					required>
				<?php
				
				foreach ($departments as $key => $value) {
					if ($key == $unassigned_institute) {
						continue;
					}
					if (get_language() == "de") {
						echo "<option value='" . $key . "'>" . $value['de'] . "</option>";
					} else {
						echo "<option value='" . $key . "'>" . $value['en'] . "</option>";
					}
				}
				?>				
			</select>

			<script>
			document.addEventListener("DOMContentLoaded", function() {
				const $select = $('#department_select');
				const allValue = "0";
				const noneValue = "-1";

				// Select2 initialization
				$select.select2({
					placeholder: "Institut auswählen",
					width: '100%',
					closeOnSelect: false,
					minimumResultsForSearch: Infinity
				});

				$select.on('change', function () {
					let selected = $(this).val() || [];

					// Logic for "Alle Institute"
					if (selected.includes(allValue) && selected.length > 1) {
						$select.val([allValue]).trigger('change.select2');
						showToast('Es wurde „Alle Institute“ ausgewählt. Andere Optionen wurden entfernt.');
					}
				});
			});
			</script>

			<br>
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

			//Select2 general settings
			$(document).ready(function() {
				$('.js-example-basic-multiple').select2({
					placeholder: $(this).data('placeholder'),
					allowClear: true,
					width: '100%'
				});
			});
			//Select2 for single selection
			$('.js-example-single-multiple').select2({
				placeholder: $(this).data('placeholder'),
				allowClear: true,
				maximumSelectionLength: 1,
				width: '100%'
			});
		</script>
	</div>
</body>
<?php
echo $OUTPUT->footer();
?>