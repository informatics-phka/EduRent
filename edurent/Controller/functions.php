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

?>