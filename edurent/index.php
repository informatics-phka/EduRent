<?php
require "app/init.php";

$controller = 'index'; 

$arr = getURL();

$arr[count($arr)-1] = preg_replace("/[.].*/", "", $arr[count($arr)-1]);

$filename = "app/".$arr[0].".php";

if(file_exists($filename)) //open page
{
    require $filename;
}else{
    http_response_code(404);
    echo "404 Not Found: " . htmlspecialchars($filename);
    exit;
}
require_once("Controller/banner.php");

function getURL() //get url from get
{
    $url = $_GET['url'] ?? 'index';
    $url = filter_var($url,FILTER_SANITIZE_URL);
    $arr = explode("/", $url);
    return $arr;
}
