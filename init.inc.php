<?php

//Inhalte
if ($_REQUEST['path']) {
    $path = $_REQUEST['path'];
}else{
    $path = 'firstStart';
}
require_once './classes/ContentControl.php';
$contControllObj = new ContentControl($path);

//Datenbank
$database = 'kompetenzenbbsleer';
$localuser = 'kompetenzUser';
$localPass = 'bbsuser';
$host = 'localhost';
if (@$connectTest = mysql_connect($host, $localuser, $localPass)) {
    if (mysql_select_db($database, $connectTest)) {
        $installRequest = false;
        require_once './classes/DBSQL.php';
        $DBSQL = new DBSQL($host, $database, $localuser, $localPass);
        $DBSQL2 = new DBSQL($host, $database, $localuser, $localPass);
    }
}
