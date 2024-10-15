<?php
    //require
    require_once("mailer.php");

    //Send error as log and mail
    function error_to_superadmin($superadmin_mails, $mail, $msg, $firstname = "Server",$lastname = "",$console = true){
        save_in_logs($msg, $firstname, $lastname, $console);
        for ($i=0; $i < count($superadmin_mails); $i++) { 
            //sendamail($mail, $superadmin_mails[$i], "Fehlermeldung Edurent", $msg);
        }
    }

    //Custom Logsystem

    //TODO: add color to log
    function save_in_logs($msg, $firstname = "Server",$lastname = "",$print_in_console = true)
    {
        if(is_array($msg)) $msg = json_encode($msg);
        $msg=str_replace("\"","'", $msg);
        $log_filename = "log";
        if (!file_exists($log_filename))
        {
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename.'/log_' . date('Y-m-d') . '.log';
        if($print_in_console){
            if($firstname == "Server") echo "<script>console.log(\"Log: " . $msg . " (" . $firstname . ")\" );</script>";
            else echo "<script>console.log(\"Log: " . $msg . " (" . $firstname . " " . $lastname . ")\" );</script>";
        }
        if($firstname == "Server") file_put_contents($log_file_data, date('H:i:s') . " - "  . strval($msg) .  " (" . $firstname . ")\n", FILE_APPEND);
        else file_put_contents($log_file_data, date('H:i:s') . " - "  . strval($msg) .  " (" . $firstname . " " . $lastname . ")\n", FILE_APPEND);
    }