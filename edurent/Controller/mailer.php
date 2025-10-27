<?php
  require_once("config.php");
  require_once("db_connect.php");

  //get department mails
  $mails;
  $sql= "SELECT * FROM departments";
  if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
      while($row = mysqli_fetch_array($result)){
        $mails[$row['department_id']]['mail'] = $row['mail'];
      }
      mysqli_free_result($result);
    }
  }
  else{
    error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
  }

  /** PHPMailer **/
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\SMTP;

  require_once './PHPMailer/src/PHPMailer.php';
  require_once './PHPMailer/src/SMTP.php';

  $mail = new PHPMailer(true);
  //$mail->SMTPDebug = 3;

  try {
    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->SMTPDebug  = SMTP::DEBUG_OFF;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'cafile' => __DIR__ . '/cacert.pem',
        ],
    ];
    $mail->isSMTP();                                            //Send using SMTP
    $mail->CharSet    = 'UTF-8';
    $mail->Host       = 'HOST';                   //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'USERNAME';               //SMTP username
    $mail->Password   = 'PASSWORD';                             //SMTP password
    $mail->SMTPSecure = 'tls';                                  //Enable implicit TLS encryption
    $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    $mail->setFrom($mails[$all_institutes]['mail'], 'Edurent Technikausleihe');
    $mail->addReplyTo($mails[$all_institutes]['mail'], 'Edurent Technikausleihe');
    $mail->isHTML(true);
  } catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
  }

  function sendamail($mail,$adress,$subject,$message,$ical=null) {
    global $link;
    if(!isset($mail)){
      error_to_superadmin(get_superadmins(), $mail, "PHPMailer: mail not set:<br>adress: " . $adress . "<br>subject: " . $subject . "<br>message: " . $message);
    }
    else if(!isset($adress)){
      error_to_superadmin(get_superadmins(), $mail, "PHPMailer: adress not set:<br>adress: " . $adress . "<br>subject: " . $subject . "<br>message: " . $message);
    }
    else if(!isset($subject)){
      error_to_superadmin(get_superadmins(), $mail, "PHPMailer: subject not set:<br>adress: " . $adress . "<br>subject: " . $subject . "<br>message: " . $message);
    }
    else if(!isset($message)){
      error_to_superadmin(get_superadmins(), $mail, "PHPMailer: message not set:<br>adress: " . $adress . "<br>subject: " . $subject . "<br>message: " . $message);
    }
    else {
      if($ical != null){
        $mail->AddStringAttachment("$ical", "Termin.ics", "base64", "text/calendar; charset=utf-8; method=REQUEST");
      }
      $mail->addAddress($adress);
      $mail->Subject  = $subject;
      $mail->Body     = $message;

      if($mail->send()){
        $mail->ClearAddresses();
      }else{
        error_to_superadmin(get_superadmins(), $mail, "PHPMailer: " . $mail->ErrorInfo);
      }
    }
  }
