<?php
session_start();
session_destroy();
header('Location: /findywearce/pages/login.php');
exit();
?>