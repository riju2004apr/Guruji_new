<?php
$mysqli = new mysqli("localhost", "root", "", "guruji");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>