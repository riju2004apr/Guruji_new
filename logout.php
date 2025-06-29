<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: ../user/index.php");
exit;
?>