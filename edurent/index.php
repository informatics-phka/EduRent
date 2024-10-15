<?php
require "app/init.php";

$controller = 'index'; 

$arr = getURL();

$arr[0] = preg_replace("/[.].*/", "", $arr[0]);

$filename = "app/".$arr[0].".php";

if(file_exists($filename)) //open page
{
    require $filename;
}else{
    require "app/" . $controller . ".php";
}
require_once("Controller/banner.php");

function getURL() //get url from get
{
    $url = $_GET['url'] ?? 'index';
    $url = filter_var($url,FILTER_SANITIZE_URL);
    $arr = explode("/", $url);
    return $arr;
}