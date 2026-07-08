<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "global_study";
$conn = mysqli_connect("localhost", "root", "", "global_study");

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}


?>
