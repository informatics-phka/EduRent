<!DOCTYPE HTML>
<?php
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

check_superadmin($user_username);
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
        
        <!-- -->
        <link rel="stylesheet" href="style-css/ahover.css">
        <script src="js/clickablerow.js"></script>
        
        <!-- Font Awesome -->
    	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    	
        <!-- Toast -->
        <?php require_once("Controller/toast.php"); ?>
        <style>
            a,
            a:hover,
            a:focus,
            a:active {
                text-decoration: none;
                color: inherit;
            }

            .log-link {
                display: block;
                border: 2px solid #000000;
                border-radius: 10px;
                padding: 5px 15px;
                margin-bottom: 10px;
                transition: background-color 0.3s ease;
            }

            .log-link:hover {
                background-color: #f0f0f0;
            }
        </style>
    </head>

    <?php
    if (exists_and_not_empty('delete', $_GET)) {
        unlink($_GET['delete']);
        $SESSION->toasttext = "Die Log-Datei wurde gelöscht";
        echo "<script>window.location.href = 'logs';</script>";
        exit();
    }
    ?>

	<div class="main">
        <h3 style="text-align: center;">Logs</h3>

        <?php
        $files = scandir('./log');
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
        ?>
            <a href='view_logs.php?file=<?php echo $file; ?>' class="log-link">
                <?php echo $file; ?>
            </a>
        <?php } ?>

        <br>
        <!-- Buttons -->
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-secondary btn-block' href='admini'>
					<i class="fas fa-arrow-left mr-2"></i>
					<?php echo translate('word_back'); ?>
				</a>
			</div>
		</div>
    </div>
</body>
<?php
echo $OUTPUT->footer();
?>
