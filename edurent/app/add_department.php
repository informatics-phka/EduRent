<!DOCTYPE HTML>
<?php
    ini_set('display_errors', '1');     
    ini_set('display_startup_errors', '1');     
    error_reporting(E_ALL);

    check_superadmin($user_username);

    //get data
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

    //get limits
    $limits = get_limits_of("departments");
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
        
        <!-- Font Awesome -->
    	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
        
        <!-- Bootstrap Validator-->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.bootstrapvalidator/0.5.2/css/bootstrapValidator.min.css" />
        <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery.bootstrapvalidator/0.5.2/js/bootstrapValidator.min.js"></script>
    </head>
    <div class="main">
        <h3 class="text-center"><?php echo translate('text_createDepartment'); ?></h3>
        <form method="post" action="departments.php">
            <div class="input-control">
                <label for="department_de"><?php echo translate('text_departmentGer'); ?></label>
                <input type="text" class="form-control" id="department_de" name="department_de" placeholder='Institut für Informatik und digitale Bildung' maxlength='<?php echo $limits['department_de']; ?>'>

                <div class="error error_text"></div>
            </div>

            <div class="input-control">
                <label for="department_en"><?php echo translate('text_departmentEng'); ?></label>
                <input type="text" class="form-control" id="department_en" name="department_en" placeholder='Institute for Computer Science and Digital Education' maxlength='<?php echo $limits['department_en']; ?>'>

                <div class="error error_text"></div>
            </div>

            <div class="input-control">
                <label for="mail"><?php echo translate('text_mail'); ?></label>
                <input class="form-control rounded" type="text" id="mail" name="mail" placeholder="technikausleihe@ph-karlsruhe.de" maxlength='<?php echo $limits['mail']; ?>'>

                <div class="error error_text"></div>
            </div>

            <div class="input-control">
                <label for="room"><?php echo translate('text_room'); ?></label>
                <input class="form-control rounded" type="text" id="room" name="room" placeholder="2.B112" maxlength='<?php echo $limits['room']; ?>'>

                <div class="error error_text"></div>
            </div>

            <label for="announce1_de"><?php echo translate('text_announce1Ger'); ?></label>
            <textarea rows='2' class="form-control rounded" type="text" id="announce1_de" name="announce1_de" maxlength='<?php echo $limits['announce1_de']; ?>'></textarea>
            <br>

            <label for="announce1_en"><?php echo translate('text_announce1Eng'); ?></label>
            <textarea rows='2' class="form-control rounded" type="text" id="announce1_en" name="announce1_en" maxlength='<?php echo $limits['announce1_en']; ?>'></textarea>
            <br>

            <!-- hidden values -->
            <input type="hidden" id="reason" name="reason" value="create">

            <!-- Buttons -->
            <div class='row no-gutters' style='text-align:center;'>
                <div class='col'>
                    <button type="submit" id="submit" class="btn btn-success rounded mr-1 mb-1"><?php echo translate('word_confirm'); ?></button>
                </div>
            </form>
            </div>
            <!-- Buttons -->
            <div class='row justify-content-center'>
                <div class='col-md-6 mb-3'>
                    <a class='btn btn-secondary btn-block' href='departments'>
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?php echo translate('word_back'); ?>
                    </a>
                </div>
            </div>
    </div>
    <script>
        var deparments_array = <?php echo is_null($departments) ? "2" : json_encode($departments); ?>;

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
            if (!name_en.value) error = "<?php echo translate('text_departmentGerError');?>";
            else if (!isUnic(name_en.value, deparments_array["department_de"])) error = "<?php echo translate('text_departmentErrorUnic'); ?>";

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

        setError(name_de, "<?php echo translate('text_departmentGerError'); ?>");
        setError(name_en, "<?php echo translate('text_departmentGerError'); ?>");
        setError(mail, "Bitte geben Sie eine Mail-Adresse ein");
        setError(room, "Bitte geben Sie einen Raum ein");
        $('#submit').attr('disabled', 'disabled');

        function check() {
            if (name_de.parentElement.querySelector('.error').innerText.length == 1 &&
                name_en.parentElement.querySelector('.error').innerText.length == 1 &&
                mail.parentElement.querySelector('.error').innerText.length == 1 &&
                room.parentElement.querySelector('.error').innerText .length == 1) {
                $('#submit').removeAttr('disabled');
            }
        }

        function validateEmail(email) {
            let res = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
            return res.test(email);
        }

        function isUnic(search, array) {
            const keys = Object.keys(array);
            for (let entry of keys) {
                if (array[entry].toLowerCase() == search.toLowerCase()) {
                    return false
                }
            }
            return true
        }
    </script>
</body>
<?php
echo $OUTPUT->footer();
?>