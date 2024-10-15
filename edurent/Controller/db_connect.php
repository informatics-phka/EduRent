<?php


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "HOST";
$username = "USERNAME";
$password = "PASSWORD";
$databaseName = "DATABASE_NAME";

$link = mysqli_connect($host, $username, $password,$databaseName );

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
