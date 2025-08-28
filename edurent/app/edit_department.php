<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

//check working
if (isEmpty($_GET['depart'])) {
    error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_department.php: _GET[depart] isEmpty {" . $_GET['depart'] . "}");
    echo "<script>window.location.href = 'admini';</script>";
    exit;
}

check_is_admin_of_department($user_username, $_GET['depart']);
$is_superadmin = is_superadmin($user_username);

$device_type = get_devicetype();

$department_name_de;
$department_name_en;
$department_announce1_de;
$department_announce1_en;
$department_mail;
$department_room;

$query = "SELECT department_de, department_en, announce1_de, announce1_en, mail, room FROM departments WHERE departments.department_id=?";
if ($stmt = mysqli_prepare($link, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $_GET['depart']);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_bind_result($stmt, $department_de, $department_en, $announce1_de, $announce1_en, $mail, $room);
            mysqli_stmt_fetch($stmt);

            $department_name_de = $department_de;
            $department_name_en = $department_en;
            $department_announce1_de = $announce1_de;
            $department_announce1_en = $announce1_en;
            $department_mail = $mail;
            $department_room = $room;
        } else {
            save_in_logs("ERROR: Kein Datensatz gefunden (" . $query . ") 44");
        }
    } else {
        save_in_logs("ERROR: " . mysqli_error($link));
        save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
    }
} else {
    save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
}
$stmt->close();

//not blocked from department
$not_blocked_devices = array();
$sql = "SELECT * FROM type_department, device_list, device_type WHERE department_id = ". $_GET['depart'] ." AND blocked = 0 AND type_department.type_id = device_type.device_type_id AND device_list.device_type_id=device_type.device_type_id";
if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_array($result)){
            $index = 0;
            if(array_key_exists($row['device_type_id'], $not_blocked_devices)) $index = count($not_blocked_devices[$row['device_type_id']]);
            $not_blocked_devices[$row['device_type_id']][$index]=$row['device_id'];
        }
        mysqli_free_result($result);
    }
}
else{
    error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
}

//get pickup days of department
$opening_days = array();
$sql = "SELECT * FROM rent_days WHERE d_id=" . $_GET['depart'] . " ORDER BY dayofweek";
if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_array($result)){
            $string = "weekday_long_" . $row['dayofweek'];
            $opening_days[$row['id']]["day"] = translate($string);
            $opening_days[$row['id']]["time"] = $row['time'];
            $opening_days[$row['id']]["dayofweek"] = $row['dayofweek'];
        }
    mysqli_free_result($result);
    }
}
else{
    error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
}

//get department name
$departments = array();
$sql = "SELECT * FROM departments";
if ($result = mysqli_query($link, $sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $departments['department_de'][$row['department_id']] = $row['department_de'];
            $departments['department_en'][$row['department_id']] = $row['department_en'];
        }
        mysqli_free_result($result);
    }
} else {
    $error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
    error_to_superadmin(get_superadmins(), $mail, $error);
    sendamail($mail, $server_admin_mail, "Fehlermeldung Edurent", $error);
}

//get departments of devicetype
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
    <link rel="stylesheet" href="style-css/ahover.css">
    <link rel="stylesheet" href="style-css/accessability.css">
    <link rel="stylesheet" href="style-css/navbar.css">
    <link rel="stylesheet" href="style-css/departments.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    
    <!-- Toast -->
    <?php require_once("Controller/toast.php"); ?>
    <style>
        input[type="text"],
        textarea {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 10px;
        }
    </style>

</head>
<body>
    <?php 
        //if remove
        if(exists_and_not_empty('remove_id', $_GET)){
            $remove_type = $_GET['remove_id'];
            $sql = "DELETE FROM device_type WHERE device_type_id='" . $remove_type . "'";
            if (mysqli_query($link, $sql)) {
                $text = "INFO: Der Gerätetyp '" . $device_type[$_GET['depart']][$remove_type]['name'] . "' wurde erfolgreich gelöscht";
                save_in_logs($text, $user_firstname, $user_lastname, false);
                sendToast($text);
            } else {
                $error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
                error_to_superadmin(get_superadmins(), $mail, $error);
            }

            unset($days);
        }

        //get limits
        $limits = get_limits_of("departments");

        $type = array();
        $sql = "SELECT * FROM device_type WHERE home_department = " . $_GET['depart'];
        if ($result = mysqli_query($link, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $type[$row['device_type_id']] = $row['device_type_name'];
                }
                mysqli_free_result($result);
            }
        } else {
            $error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
            error_to_superadmin(get_superadmins(), $mail, $error);
            sendamail($mail, $server_admin_mail, "Fehlermeldung Edurent", $error);
        }
    ?>
    <div class="main">
            <?php require_once 'navbar.php'; ?>
            <br>
        <?php if(count($not_blocked_devices)>0 && count($opening_days) == 0){?>
            <div class="alert alert-danger" ng-show="pickup.error">
                <?php echo translate('text_noPickupDayConfigured'); ?>
            </div>
        <?php }?>
        <h3>
            '<?php if (get_language() == "de") {
                    echo $department_name_de;
                } else {
                    echo $department_name_en;
                } ?>' <?php echo translate('word_edit'); ?>
        </h3>
        <form action="departments.php" method="post">
            <div class="form-group">
                <label for="department_de"><?php echo translate('text_departmentGer'); ?></label>
                <input type="text" class="form-control rounded" id="department_de" name="department_de" value="<?php echo $department_name_de; ?>" placeholder='Institut für Informatik und digitale Bildung' maxlength='<?php echo $limits['department_de']; ?>'>
                <div class="error error_text"></div>
            </div>

            <div class="form-group">
                <label for="department_en"><?php echo translate('text_departmentEngError'); ?></label>
                <input type="text" class="form-control rounded" id="department_en" name="department_en" value="<?php echo $department_name_en; ?>" placeholder='Institute for Computer Science and Digital Education' maxlength='<?php echo $limits['department_en']; ?>'>
                <div class="error error_text"></div>
            </div>

            <div class="form-group">
                <label for="mail"><?php echo translate('text_mail'); ?></label>
                <input class="form-control rounded" type="text" id="mail" name="mail" value="<?php echo $department_mail; ?>" placeholder="technikausleihe@ph-karlsruhe.de" maxlength='<?php echo $limits['mail']; ?>'>
                <div class="error error_text"></div>
            </div>

            <div class="form-group">
                <label for="room"><?php echo translate('text_room'); ?></label>
                <input class="form-control rounded" type="text" id="room" name="room" value="<?php echo $department_room; ?>" placeholder="2.B112" maxlength='<?php echo $limits['room']; ?>'>
                <div class="error error_text"></div>
            </div>

            <div class="form-group">
                <label for="announce1_de"><?php echo translate('text_announce1Ger'); ?></label>
                <textarea rows='2' class="form-control rounded" type="text" id="announce1_de" name="announce1_de" maxlength='<?php echo $limits['announce1_de']; ?>'><?php echo $department_announce1_de; ?></textarea>
            </div>

            <div class="form-group">
                <label for="announce1_en"><?php echo translate('text_announce1Eng'); ?></label>
                <textarea rows='2' class="form-control rounded" type="text" id="announce1_en" name="announce1_en" maxlength='<?php echo $limits['announce1_en']; ?>'><?php echo $department_announce1_en; ?></textarea>
            </div>

            <?php
            $department_id = isset($_GET['depart']) ? intval($_GET['depart']) : 0; // Get department_id

            // get default max loan duration
            $query = "SELECT max_loan_duration FROM server LIMIT 1";
            $result = mysqli_query($link, $query);
            $row = mysqli_fetch_assoc($result);
            $max_loan_duration = isset($row['max_loan_duration']) ? intval($row['max_loan_duration']) : 14;


            // get max loan duration of department
            $query = "SELECT max_loan_duration FROM department_settings WHERE department_id = ?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, "i", $department_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $db_duration);

            if (mysqli_stmt_fetch($stmt)) {
                $max_loan_duration = intval($db_duration);
            }

            mysqli_stmt_close($stmt);
            ?>

            <!-- max loan duration for the department -->
            <div class="mb-3">
                <label for="max_loan_duration" class="form-label"><?php echo translate('text_maxLoanDureation'); ?>:</label>
                <input type="number" class="form-control" id="max_loan_duration" name="max_loan_duration" value="<?php echo $max_loan_duration; ?>">
            </div>


            <!-- hidden values -->
            <input type="hidden" id="reason" name="reason" value="edit">
            <input type="hidden" id="department_id" name="department_id" value=<?php echo $_GET['depart']; ?>>

            <!-- Buttons -->
            <div class='row justify-content-center'>
                <div class='col-md-6 mb-3'>
                    <a class='btn btn-secondary btn-block' href='departments'>
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
            <?php if ($_GET['depart'] != $unassigned_institute && $_GET['depart'] != $all_institutes) {
                if (is_superadmin($user_username)) { ?>
                    <div class='col-md-6 mb-3'>
                        <a class='btn btn-danger btn-block rounded' href='departments.php?remove_id=<?php echo $_GET["depart"]; ?>'>
                            <i class="fas fa-trash-alt mr-2"></i>
                            <?php echo translate('word_delete'); ?>
                        </a>
                    </div>
                <?php } ?>
                <div class='col-md-6 mb-3'>
                    <a class='btn btn-outline-dark btn-block rounded' href='days.php?depart=<?php echo $_GET['depart'] ?>'>
                        <i class="fas fa-calendar mr-2"></i>
                        <?php echo translate('word_pickupDays'); ?>
                    </a>
                </div>
            </div>
            <?php } ?>
        <br>
        <!-- Devicelist -->
        <h3><?php echo translate('word_devices'); ?></h3>
        <?php if (!($_GET['depart'] == $all_institutes || $_GET['depart'] == $unassigned_institute)) {
            echo "<a class='department' href='add_type.php?depart=". $_GET['depart'] . "'><i class='fas fa-plus'></i> " . translate('word_add') . "</a>";
        }
        ?>
        <input type="text" id="departmentSearch" class="form-control department" placeholder="Eintrag suchen...">	
        <div id="departmentLinks">
            <?php
            for ($i = 0; $i < count($type); $i++) {
                echo "<a class='department' href='edit_type.php?type=" . array_keys($type)[$i] . "'>" . $type[array_keys($type)[$i]] . "</a>";
            }
            if (count($type) == 0) { ?>
                <a>
                    <a class='department'>Keine Gerätetypen für das Institut gefunden</a>
                </a>
            <?php } ?>
        </div>
    </div>
</body>
<script>
    var deparments_array = <?php echo is_null($departments) ? "2" : json_encode($departments); ?>;

    var old_de = '<?php echo $department_name_de; ?>';
    var old_eng = '<?php echo $department_name_en; ?>';

    //errorhandle
    const setError = (element, message) => {
        const inputControl = element.parentElement;
        const errorDisplay = inputControl.querySelector('.error');

        errorDisplay.innerText = message;
        inputControl.classList.add('error');
        inputControl.classList.remove('success')
    }

    const setSuccess = element => {
        const inputControl = element.parentElement;
        const errorDisplay = inputControl.querySelector('.error');

        errorDisplay.innerHTML = '&nbsp;';
        inputControl.classList.add('success');
        inputControl.classList.remove('error');
    };

    const name_de = document.getElementById('department_de');
    const inputHandler = function(e) {
        var error;
        if (!name_de.value) error = "<?php echo translate('text_departmentGerError'); ?>";
        else if (!isUnic(name_de.value, deparments_array["department_de"])) error = "<?php echo translate('text_departmentErrorUnic'); ?>";

        if (error) {
            setError(name_de, error);
            $('#submit').attr('disabled', 'disabled');
        } else {
            setSuccess(name_de);
            check();
        }
    }
    name_de.addEventListener('input', inputHandler);
    name_de.addEventListener('propertychange', inputHandler);

    const name_en = document.getElementById('department_en');
    const inputHandler2 = function(e) {
        var error;
        if (!name_en.value) error = "<?php echo translate('text_departmentEngError'); ?>";
        else if (!isUnic(name_en.value, deparments_array["department_en"])) error = "<?php echo translate('text_departmentErrorUnic'); ?>";

        if (error) {
            setError(name_en, error);
            $('#submit').attr('disabled', 'disabled');
        } else {
            setSuccess(name_en);
            check();
        }
    }
    name_en.addEventListener('input', inputHandler2);
    name_en.addEventListener('propertychange', inputHandler2);

    const mail = document.getElementById('mail');
    const inputHandler3 = function(e) {
        var error;
        if (!mail.value) error = "Bitte geben Sie eine Mail-Adresse ein";
        if (!validateEmail(mail.value)) error = "Bitte geben Sie eine valide Mail-Adresse ein";

        if (error) {
            setError(mail, error);
            $('#submit').attr('disabled', 'disabled');
        } else {
            setSuccess(mail);
            check();
        }
    }
    mail.addEventListener('input', inputHandler3);
    mail.addEventListener('propertychange', inputHandler3);

    const room = document.getElementById('room');
    const inputHandler4 = function(e) {
        var error;
        if (!room.value) error = "Bitte geben Sie einen Raum ein";

        if (error) {
            setError(room, error);
            $('#submit').attr('disabled', 'disabled');
        } else {
            setSuccess(room);
            check();
        }
    }
    room.addEventListener('input', inputHandler4);
    room.addEventListener('propertychange', inputHandler4);

    function check() {
        if (name_de.parentElement.querySelector('.error').innerText.length == 1 &&
            name_en.parentElement.querySelector('.error').innerText.length == 1 &&
            mail.parentElement.querySelector('.error').innerText.length == 1 &&
            room.parentElement.querySelector('.error').innerText.length == 1) {
            $('#submit').removeAttr('disabled');
        }
    }

    function validateEmail(email) {
        let res = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
        return res.test(email);
    }

    function isUnic(search, array) {
        if (search != old_de && search != old_eng) {
            const keys = Object.keys(array);
            for (let entry of keys) {
                if (array[entry].toLowerCase() == search.toLowerCase()) {
                    return false
                }
            }
        }
        return true
    }

    document.getElementById('departmentSearch').addEventListener('input', function () {
		const query = this.value.toLowerCase();
		const links = document.querySelectorAll('#departmentLinks .department');

		links.forEach(link => {
			const text = link.textContent.toLowerCase();
			link.style.display = text.includes(query) ? 'block' : 'none';
		});
	});
</script>

<?php
echo $OUTPUT->footer();
?>