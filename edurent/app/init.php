<?php
    global $USER;
    
    require_once(dirname(__FILE__) . '/../../config.php');

    //page settings
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title("edurent");
    $PAGE->set_url("/edurent/index.php");
    echo $OUTPUT->header();

    //user infos
    $user_firstname = $USER->firstname;
    $user_lastname = $USER->lastname;
    $user_email = $USER->email;
    $user_username = $USER->username;

    session_start();

    require_once("Controller/db_connect.php");
    require_once("Controller/error.php");
    require_once("Controller/database.php");
    require_once("Controller/language.php");
    require_once("Controller/functions.php");
    require_once("Controller/config.php");
    require_once("Controller/modal.php");
    require_once("Controller/mailer.php");
    require_once("Controller/basic.php");
    require_once("Controller/Device_Select.php");

    function myErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        // $errstr may need to be escaped:
        $errstr = htmlspecialchars($errstr);

        switch ($errno) {
        case E_USER_ERROR:
            save_in_logs("<b>My ERROR</b> [$errno] $errstr<br />\n");
            save_in_logs("  Fatal error on line $errline in file $errfile");
            save_in_logs(", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n");
            save_in_logs("Aborting...<br />\n");
            exit(1);

        case E_USER_WARNING:
            save_in_logs("<b>My WARNING</b> [$errno] $errstr<br />\n");
            break;

        case E_USER_NOTICE:
            save_in_logs("<b>My NOTICE</b> [$errno] $errstr<br />\n");
            break;

        default:
            save_in_logs("Unknown error type: [$errno] $errstr<br />\n");
            break;
        }

        /* Don't execute PHP internal error handler */
        return true;
    }

    // set to the user defined error handler
    $old_error_handler = set_error_handler("myErrorHandler");

    //check login
    check_logged_in($user_firstname);