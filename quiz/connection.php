<?php
$connection = mysqli_connect("localhost", "root", "", "quiz");
if (!$connection) {
    die("DB Connection failed: " . mysqli_connect_error());
}
?>