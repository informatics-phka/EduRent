<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_superadmin($user_username);

$is_superadmin = is_superadmin($user_username);
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
        <link rel="stylesheet" href="style-css/view_logs.css">
        <link rel="stylesheet" href="style-css/navbar.css">
		
        <!-- Font Awesome -->
    	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    	
        <!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>
	</head>
	<div class="main">
        <?php require_once 'navbar.php'; ?>	
        <br>
		<h3>View Log</h3>

		<!-- Search bar -->
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="<?php echo translate('word_search'); ?>..." id="searchInput">
			<select class="form-select" id="filterSelect">
                <option value=""><?php echo translate('text_all_errors'); ?></option>
                <option value="info">INFO</option>
                <option value="warning">WARNING</option>
                <option value="error">ERROR</option>
            </select>
        </div>
		<!-- Tabelle -->

		<div style="overflow-x:auto;">
            <table style="width: 100%;">
                <tr>
                    <th style="width: 10%;"><?php echo translate('word_time'); ?></th>
                    <th style="width: 60%;"><?php echo translate('word_report'); ?></th>
                    <th style="width: 4%;"></th>
                </tr>
		<?php
		$file = "log/" . $_GET['file'];
		if(!file_exists($file)) echo "<script>window.location.href = 'logs';</script>";
		if(filesize($file) == 0){
            echo "<script>window.location.href = 'logs.php?delete=" . $file . "';</script>";
        }
        $fh = fopen($file, 'r');
		$pageText = fread($fh, filesize($file));
		$zeile = explode("\n", $pageText);
			for($i = 0; $i < count($zeile); $i++){
				if(!exists_and_not_empty($i, $zeile)) continue;
				$zeile[$i] = explode(" - ", $zeile[$i]);

				if(!exists_and_not_empty(1, $zeile[$i])){
					$severity = 'error';
                    $symbol = '<i class="fas fa-circle"></i>';

					echo "<td>" . $zeile[$i][0] . "</td>";
					echo "<td>";
					echo "<span class='icon-container $severity'>$symbol</span>";
					echo "<span style='padding-left: 10px;'>";
					echo $zeile[$i][0] . "</span></td>";
					echo "<td><input type='checkbox' class='delete-checkbox' data-file='$file' data-line='$i'></td>";
					echo "</tr>";
					continue;
                } elseif (strpos($zeile[$i][1], 'INFO:') !== false) {
                    $severity = 'info';
                    $symbol = '<i class="fas fa-info-circle"></i>'; 
                } elseif (strpos($zeile[$i][1], 'WARNING:') !== false) {
                    $severity = 'warning';
                    $symbol = '<i class="fas fa-exclamation-triangle"></i>';
                } elseif (strpos($zeile[$i][1], 'ERROR:') !== false) {
                    $severity = 'error';
                    $symbol = '<i class="fas fa-times-circle"></i>';
                } else {
                    $severity = 'not set';
                    $symbol = '<i class="fas fa-circle"></i>';
                }

                echo "<tr class='$severity'>";
                echo "<td>" . $zeile[$i][0] . "</td>";
                echo "<td>";
                echo "<span class='icon-container $severity'>$symbol</span>";
                echo "<span style='padding-left: 10px;'>";
                echo $zeile[$i][1] . "</span></td>";
                echo "<td><input type='checkbox' class='delete-checkbox' data-file='$file' data-line='$i'></td>";
                echo "</tr>";
			}
		?>
			</table>
		</div>

		</br>
		<!-- Buttons -->
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<button type='button' class='btn btn-warning btn-block' id='deleteSelected'>
					<i class="fas fa-trash mr-2"></i>
                    <?php echo translate('text_delete_row'); ?>
				</button>
			</div>
			<div class='col-md-6 mb-3'>
				<button type='button' class='btn btn-danger btn-block' onclick=window.location.href='logs.php?delete=<?php echo $file; ?>'>
					<i class="fas fa-exclamation-triangle mr-2"></i> 
					<?php echo translate('word_delete'); ?>
				</button>
			</div>
		</div>
	</div>
</body>
<script>
$(document).ready(function() {
    $("#deleteSelected").click(function() {
        if(confirm("<?php echo translate('text_confirm_delete'); ?>")) {
            $(".delete-checkbox:checked").each(function() {
                var file = $(this).data("file");
                var line = $(this).data("line");
                $.ajax({
                    url: '../Controller/delete_line.php',
                    type: 'POST',
                    data: { file: file, line: line },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });
        }
    });

	// Suchfunktion
    $("#searchInput").keyup(function() {
        var searchText = $("#searchInput").val().toLowerCase();
        $("table tr:gt(0)").each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(searchText) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });

    // Filter nach Fehlerarten
	$("#filterSelect").change(function() {
        var selectedSeverity = $(this).val();
        if (selectedSeverity === "") {
            $("table tr:gt(0)").show();
        } else {
            $("table tr:gt(0)").hide();
            $("table tr." + selectedSeverity).show();
        }
    });
});

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
</script>
<?php
echo $OUTPUT->footer();
?>