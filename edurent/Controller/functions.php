<!DOCTYPE HTML>

<body>
  <head>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
  </head>
</body>

<?php
//require
require_once("mailer.php");

/* check if the user is logged in */
function check_logged_in($name)
{
  require_login(); //no longer seems to work
  if ($name == "Gast" || !isset($name)) {
    redirect(get_login_url());
  }
}

function is_superadmin($user_username){
  $admin_department_list = get_admin_department($user_username);
  if(is_array($admin_department_list)){
    if(in_array(0, $admin_department_list)) return true;
  }
  return false;
}

function isEmpty($s)
{
  return $s == '';
}

function exists_and_not_empty($key, $array)
{
  if(array_key_exists($key, $array)){
    if(!isEmpty($array[$key])){
      return true;
    }
  }
  return false;
}

function obj_exists_and_not_empty($key, $array)
{
  if(property_exists($array, $key)){
    if(!isEmpty($array->$key)){
      return true;
    }
  }
  return false;
}

function not_set($key, $array)
{
  if(array_key_exists($key, $array)){
    if(isEmpty($array[$key])){
      return true;
    }
  }
  else{
    return true;
  }
  return false;
}

function get_superadmins(){
  global $link;
  $sql = "SELECT user.email FROM admins, user WHERE admins.u_id=user.id AND admins.department=0";
    if($result = mysqli_query($link, $sql)){
      if(mysqli_num_rows($result) > 0){
        $admins = array();
        while($row = mysqli_fetch_array($result)){
          $admins[count($admins)] = $row['email'];
        }
        mysqli_free_result($result);
        return $admins;
      }
    }
    else{
      save_in_logs("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
    }
}

function check_superadmin($username){
  global $SESSION;
  if (!is_superadmin($username)) {
    $SESSION->toasttext = "Du bist keine Superadmin";
    save_in_logs("WARNING: " . $username . " tried to log in as a superadmin at: " . $_SERVER["REQUEST_URI"]);
    echo "<script>window.location.href = 'admini';</script>";
    exit;
  } 
  if(obj_exists_and_not_empty('toasttext', $SESSION)){
    if ($SESSION->toasttext == "Du hast keine Adminrechte.") unset($SESSION->toasttext);
  }
}

function check_is_admin($username){
  global $SESSION;
  if (!is_admin($username)) {
    $SESSION->toasttext = "Du hast keine Adminrechte.";
    echo "<script>window.location.href = 'index';</script>";
    exit;
  } 
  if(obj_exists_and_not_empty('toasttext', $SESSION)){
    if ($SESSION->toasttext == "Du hast keine Adminrechte.") unset($SESSION->toasttext);
  }
}

function check_is_admin_of_department($username, $department){
  global $SESSION;
  if(!is_superadmin($username)){
    if (!is_admin_of_department($username, $department)) {
      $SESSION->toasttext = "Du hast nicht die benötigen Adminrechte.";
      echo "<script>window.location.href = 'index';</script>";
      exit;
    } 
  }
  if(obj_exists_and_not_empty('toasttext', $SESSION)){
    if ($SESSION->toasttext == "Du hast keine Adminrechte.") unset($SESSION->toasttext);
  }
}

function createEventICS($row, $zeitinfo, $departments, $devices, $isReturn = false) {
    try {
        //pickup(date_from) or return(date_to)
        $date_field = $isReturn ? $row['date_to'] : $row['date_from'];

        $date = date_create($date_field);
        if (!$date) {
            save_in_logs('ERROR: Ungültiges Datum: ' . $date_field);
            $date = new DateTime('today', new DateTimeZone('Europe/Berlin'));
        }
        $date_formatted = date_format($date, 'd.m.Y');

        if ($isReturn) {
            //split Time and Date
            $parts = explode(" ", $zeitinfo, 2);
            if (count($parts) < 2) {
                save_in_logs('ERROR: Ungültiges Rückgabe-Zeitformat: ' . $zeitinfo);
                $times = ['08:00', '20:00'];
            } else {
                $times = explode("-", $parts[1]);
                if (count($times) !== 2) {
                    save_in_logs('ERROR: Ungültiges Rückgabe-Zeitformat (nach Split): ' . $zeitinfo);
                    $times = ['08:00', '20:00'];
                }
            }
        } else {
            //split Time
            $times = explode("-", $zeitinfo);
            if (count($times) !== 2) {
                save_in_logs('ERROR: Ungültiges Abhol-Zeitformat: ' . $zeitinfo);
                $times = ['08:00', '20:00'];
            }
        }

        //create new DateTime
        $time_start = DateTime::createFromFormat(
            'd.m.Y H:i',
            trim($date_formatted) . ' ' . trim($times[0]),
            new DateTimeZone("Europe/Berlin")
        );
        $time_end = DateTime::createFromFormat(
            'd.m.Y H:i',
            trim($date_formatted) . ' ' . trim($times[1]),
            new DateTimeZone("Europe/Berlin")
        );

        if (!$time_start || !$time_end) {
            save_in_logs('ERROR: Fehler beim Erstellen der DateTime-Objekte. Fallback auf Standardzeiten.');
            $time_start = new DateTime('today 08:00', new DateTimeZone('Europe/Berlin'));
            $time_end = new DateTime('today 20:00', new DateTimeZone('Europe/Berlin'));
        }

        //convert into UTC
        $time_start->setTimezone(new DateTimeZone("UTC"));
        $time_end->setTimezone(new DateTimeZone("UTC"));

        //ICS settings
        $properties = array(
            'summary' => ($isReturn ? 'Geräterückgabe - ' : 'Geräteabholung - ') . ($departments[$row['department_id']]['de'] ?? 'Unbekannt'),
            'description' => 'Folgende Geräte werden ' . ($isReturn ? 'zurückgegeben' : 'abgeholt') . ': ' . $devices,
            'location' => 'Raum: ' . ($isReturn ? ($row['room_to'] ?? 'Unbekannt') : ($row['room_from'] ?? 'Unbekannt')),
            'dtstart' => $time_start->format('Y-m-d H:i'),
            'dtend' => $time_end->format('Y-m-d H:i')
        );

        $ics = new ICS($properties);
        return $ics->to_string();

    } catch (Exception $e) {
        save_in_logs('ERROR: Exception beim Erstellen des ICS-Events: ' . $e->getMessage());
        return false;
    }
}



?>