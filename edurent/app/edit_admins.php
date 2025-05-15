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

// define navbar
$menuItems = [
    ['label' => translate('word_reservations'), 'href' => 'admini', 'visible' => true],
    ['label' => translate('word_orderHistory'), 'href' => 'orderhistory', 'visible' => true],
    ['label' => translate('word_departments'), 'href' => 'departments', 'visible' => true],
    ['label' => translate('word_faq'), 'href' => 'faq', 'visible' => true],
    ['label' => translate('word_admins'), 'href' => 'admins', 'visible' => $is_superadmin],
    ['label' => translate('word_logs'), 'href' => 'logs', 'visible' => $is_superadmin],
    ['label' => translate('word_settings'), 'href' => 'update_settings', 'visible' => $is_superadmin],
];

$menuItemsHtml = '';
foreach ($menuItems as $item) {
    if ($item['visible']) {
        $menuItemsHtml .= '<li class="nav-item">';
        $menuItemsHtml .= '<a class="nav-link" href="' . htmlspecialchars($item['href']) . '">' . htmlspecialchars($item['label']) . '</a>';
        $menuItemsHtml .= '</li>';
    }
}

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
	<div class="main">
		<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav ms-auto" id="navbarMenu">
                        <?= $menuItemsHtml ?>
                    </ul>
                </div>
            </div>
        </nav>
		</br>
		<h3 class="text-center">
			<?php echo translate('word_admin'); ?> '<?php echo $admins[$_GET['u_id']]['fn']; ?> <?php echo $admins[$_GET['u_id']]['ln']; ?>' <?php echo translate('word_edit'); ?>
		</h3>

		<form action="admins.php" method="post">
			<label for="department_select" class="form-label"><?php echo translate('word_department'); ?></label>
			<select id="department_select" class="form-control js-example-basic-multiple" name="states[]" multiple="multiple" required>
				<?php
				foreach ($departments as $key => $value) {
					if ($key == $unassigned_institute) {
						continue;
					}
					if (get_language() == "de") {
						if (in_array($key,$admins[$_GET['u_id']]['departments'])) echo "<option selected value='" . $key . "'>" . $value['de'] . "</option>";
						else echo "<option value='" . $key . "'>" . $value['de'] . "</option>";
					} else {
						if (in_array($key,$admins[$_GET['u_id']]['departments'])) echo "<option selected value='" . $key . "'>" . $value['en'] . "</option>";
						else echo "<option value='" . $key . "'>" . $value['en'] . "</option>";
					}
				}
				?>
			</select>
			</br>
			</br>

			<!-- hidden values -->
			<input type="hidden" id="reason" name="reason" value="edit">
			<input type="hidden" id="user" name="user" value="<?php echo $_GET['u_id']; ?>">

			<!-- Buttons -->
            <div class='row justify-content-center'>
                <div class='col-md-6 mb-3'>
                    <button type='submit' id="submit" class='btn btn-success btn-block rounded mr-1 mb-1'>
                        <i class="fas fa-save mr-2"></i>
                        <?php echo translate('word_save'); ?>
                    </button>
                </div>
		</form>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-danger btn-block rounded' href='admins.php?remove_id=<?php echo $_GET["u_id"]; ?>'>
					<i class="fas fa-trash-alt mr-2"></i>
					<?php echo translate('word_delete'); ?>
				</a>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', () => {
			// display current page in navbar
			const links = document.querySelectorAll('#navbarMenu .nav-link');
			const currentPath = window.location.pathname.toLowerCase()
				.replace(/^\/edurent\//, '')
				.replace(/\.php$/, '');

			links.forEach(link => {
				const linkPath = link.getAttribute('href').toLowerCase();

				if (currentPath == linkPath) {
					link.classList.add('active');
				} else {
					link.classList.remove('active');
				}
			});
		});

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

		//Select2 for department
		$(document).ready(function() {
			$('.js-example-basic-multiple').select2({
				placeholder: "Institut auswählen",
				allowClear: true
			});
		});
	</script>
	<?php
	echo $OUTPUT->footer();
	?>
</body>